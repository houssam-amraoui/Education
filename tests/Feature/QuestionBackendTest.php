<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Level;
use App\Models\Question;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Queries\VisibleQuestions;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_mutate_questions_only_under_their_bound_owner(): void
    {
        $unit = $this->unit();
        $exercise = $unit->exercises()->create(['title' => 'Exercise', 'instructions' => 'Answer.']);
        $other = $unit->exercises()->create(['title' => 'Other exercise', 'instructions' => 'Answer.']);
        $exam = $unit->exams()->create(['type' => Exam::INTERACTIVE, 'title' => 'Exam']);
        $document = $unit->exams()->create(['type' => Exam::DOCUMENT, 'title' => 'Document']);
        $store = route('admin.curriculum.exercises.questions.store', $exercise);

        $this->postJson($store)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson($store, $this->definition())->assertForbidden();
        $this->signInAsTeacher();

        $invalid = $this->definition();
        $invalid['answer']['option_id'] = 'foreign';
        $this->postJson($store, $invalid)->assertUnprocessable()->assertJsonValidationErrors('answer.option_id');

        $first = $this->postJson($store, $this->definition() + ['exercise_id' => $other->id, 'position' => 8])
            ->assertCreated()->assertJsonPath('exercise_id', $exercise->id)->assertJsonPath('position', 1)
            ->assertJsonMissingPath('answer')->json('id');
        $second = $this->postJson($store, $this->definition())->assertCreated()->assertJsonPath('position', 2)->json('id');
        $third = $this->postJson($store, $this->definition())->assertCreated()->assertJsonPath('position', 3)->json('id');

        $examStore = route('admin.curriculum.exams.questions.store', $exam);
        $examQuestion = $this->postJson($examStore, $this->definition())
            ->assertCreated()->assertJsonPath('exam_id', $exam->id)->assertJsonPath('position', 1)->json('id');
        $this->postJson(route('admin.curriculum.exams.questions.store', $document), $this->definition())->assertNotFound();
        $this->putJson(route('admin.curriculum.exams.questions.update', [$exam, $first]), $this->definition())->assertNotFound();
        $this->putJson(route('admin.curriculum.exams.questions.update', [$exam, $examQuestion]), $this->definition())->assertOk();

        $reorder = route('admin.curriculum.exercises.questions.reorder', [$exercise, $third]);
        $this->patchJson($reorder, ['position' => 4])->assertUnprocessable()->assertJsonValidationErrors('position');
        $this->patchJson($reorder, ['position' => 1])->assertNoContent();
        $this->assertSame([$third, $first, $second], $exercise->questions()->ordered()->pluck('id')->all());

        $this->deleteJson(route('admin.curriculum.exercises.questions.destroy', [$other, $first]))->assertNotFound();
        $this->deleteJson(route('admin.curriculum.exercises.questions.destroy', [$exercise, $first]))->assertNoContent();
        $this->assertSame([1, 2], $exercise->questions()->ordered()->pluck('position')->all());
        $this->assertSame($examQuestion, $exam->questions()->sole()->id);

        $this->putJson(route('admin.curriculum.exercises.questions.update', [$exercise, $second]), [
            'type' => 'true_false', 'prompt' => 'This is passive.', 'points' => 4,
            'content' => [], 'answer' => ['value' => '0'],
        ])->assertOk()->assertJsonPath('type', 'true_false');
        $this->assertSame(['value' => false], Question::findOrFail($second)->answer);
        $this->assertNull(Question::findOrFail($second)->explanation);
    }

    public function test_learner_queries_apply_parent_visibility_and_project_only_display_fields(): void
    {
        $unit = $this->unit();
        $exercise = $unit->exercises()->create(['title' => 'Exercise', 'instructions' => 'Answer.']);
        $registeredExam = $unit->exams()->create(['type' => Exam::INTERACTIVE, 'title' => 'Exam', 'access' => 'registered']);
        $data = $this->definition();
        // Even a stale content field inserted outside the authoring path must stay private.
        $data['content']['options'][0]['correct'] = true;
        $exercise->questions()->make($data)->forceFill(['position' => 2])->save();
        $first = $exercise->questions()->make($data)->forceFill(['position' => 1]);
        $first->save();
        $registeredExam->questions()->make($data)->forceFill(['position' => 1])->save();

        $questions = new VisibleQuestions;
        $this->assertTrue($questions->forExercise($exercise->id)->isEmpty());
        $exercise->forceFill(['status' => 'published'])->save();
        $registeredExam->forceFill(['status' => 'published'])->save();
        $visible = $questions->forExercise($exercise->id);
        $this->assertSame([1, 2], $visible->pluck('position')->all());
        $this->assertSame([
            'id' => $first->id, 'type' => 'single_choice', 'prompt' => $data['prompt'],
            'points' => 2, 'position' => 1, 'content' => $this->definition()['content'],
        ], $visible->first());
        $this->assertTrue($questions->forExam($registeredExam->id)->isEmpty());
        $student = User::factory()->hasAttached(Role::where('key', Role::STUDENT)->sole())->create();
        $this->assertCount(1, $questions->forExam($registeredExam->id, $student));
        $unit->forceFill(['status' => 'draft'])->save();
        $this->assertTrue($questions->forExercise($exercise->id)->isEmpty());
        $this->assertTrue($questions->forExam($registeredExam->id, $student)->isEmpty());
    }

    public function test_authoring_validates_the_type_specific_definition_before_persisting_it(): void
    {
        $exercise = $this->unit()->exercises()->create(['title' => 'Exercise', 'instructions' => 'Answer.']);
        $this->signInAsTeacher();
        $store = route('admin.curriculum.exercises.questions.store', $exercise);
        $items = $this->definition()['content']['options'];
        $right = [['id' => 'a', 'label' => 'A'], ['id' => 'b', 'label' => 'B']];

        foreach ([
            ['multiple_selection', ['options' => $items], ['option_ids' => ['passive']],
                ['option_ids' => ['passive', 'foreign']], 'answer.option_ids'],
            ['matching', ['left' => $items, 'right' => $right], [
                'pairs' => [['left_id' => 'active', 'right_id' => 'a'], ['left_id' => 'passive', 'right_id' => 'b']],
            ], ['pairs' => [['left_id' => 'active', 'right_id' => 'a']]], 'answer.pairs'],
            ['fill_in_the_blanks', ['slots' => [['id' => 'verb', 'label' => 'Verb']]], [
                'slots' => [['id' => 'verb', 'accepted' => ['is', 'was']]],
            ], ['slots' => [['id' => 'foreign', 'accepted' => ['is']]]], 'answer.slots'],
            ['short_answer', [], ['accepted' => ['The school was built.']], ['accepted' => ["\u{00A0}"]], 'answer.accepted.0'],
            ['ordering', ['items' => $items], ['item_ids' => ['active', 'passive']],
                ['item_ids' => ['active']], 'answer.item_ids'],
        ] as [$type, $content, $answer, $invalidAnswer, $error]) {
            $data = ['type' => $type, 'content' => $content, 'prompt' => 'English ___ spoken here.', 'points' => 2];
            $this->postJson($store, $data + ['answer' => $invalidAnswer])
                ->assertUnprocessable()->assertJsonValidationErrors($error);
            $id = $this->postJson($store, $data + ['answer' => $answer])->assertCreated()->json('id');
            $this->assertSame($answer, Question::findOrFail($id)->answer);
        }

        $this->assertDatabaseCount('questions', 5);
    }

    public function test_database_enforces_one_owner_interactive_exam_type_and_unique_positions(): void
    {
        $unit = $this->unit();
        $exercise = $unit->exercises()->create(['title' => 'Exercise', 'instructions' => 'Answer.']);
        $exam = $unit->exams()->create(['type' => Exam::INTERACTIVE, 'title' => 'Exam']);
        $document = $unit->exams()->create(['type' => Exam::DOCUMENT, 'title' => 'Document']);
        $exercise->questions()->make($this->definition())->forceFill(['position' => 1])->save();

        foreach ([
            [],
            ['exercise_id' => $exercise->id, 'exam_id' => $exam->id],
            ['exam_id' => $document->id],
            ['exam_id' => $document->id, 'exam_type' => Exam::DOCUMENT],
            ['exercise_id' => $exercise->id, 'position' => 1],
        ] as $owner) {
            try {
                DB::transaction(fn () => (new Question($this->definition()))->forceFill($owner)->save());
                $this->fail('The database accepted an invalid question owner or position.');
            } catch (QueryException $exception) {
                $this->assertSame('23', substr($exception->getCode(), 0, 2));
            }
        }
    }

    private function definition(): array
    {
        return [
            'type' => 'single_choice',
            'prompt' => 'Choose the passive sentence.',
            'content' => ['options' => [
                ['id' => 'passive', 'label' => 'The school was built.'],
                ['id' => 'active', 'label' => 'They built the school.'],
            ]],
            'answer' => ['option_id' => 'passive'],
            'points' => 2,
            'explanation' => 'Use was and the past participle.',
        ];
    }

    private function unit(): Unit
    {
        $level = Level::create(['title' => 'Level']);
        $level->forceFill(['status' => 'published'])->save();
        $unit = $level->units()->create(['title' => 'Unit']);
        $unit->forceFill(['status' => 'published'])->save();

        return $unit;
    }

    private function signInAsTeacher(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::where('key', Role::TEACHER)->sole());
        $this->actingAs($teacher);
    }
}
