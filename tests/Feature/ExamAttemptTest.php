<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptItem;
use App\Models\Level;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use App\Support\QuestionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExamAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_owns_and_snapshots_ordered_questions_and_server_timing(): void
    {
        $this->freezeSecond();
        $exam = $this->exam(30);
        $url = route('curriculum.exams.attempts.store', $exam);
        $this->postJson($url)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson($url)->assertForbidden();
        $student = $this->student();
        $this->actingAs($student)->postJson($url)->assertUnprocessable()->assertJsonValidationErrors('exam');
        $question = $this->question($exam, 2);
        $this->question($exam, 1);

        $id = $this->postJson($url, ['student_id' => 999, 'deadline_at' => null, 'possible_points' => 999])
            ->assertCreated()
            ->assertJsonPath('started_at', now()->toISOString())
            ->assertJsonPath('deadline_at', now()->addMinutes(30)->toISOString())
            ->assertJsonPath('questions.0.prompt', 'Question 1')
            ->assertJsonPath('questions.1.prompt', 'Question 2')
            ->assertJsonPath('possible_points', 6)
            ->assertJsonMissingPath('questions.0.answer_key')
            ->assertJsonMissingPath('questions.0.explanation')
            ->json('id');

        $question->delete();
        $attempt = ExamAttempt::with('items')->findOrFail($id);
        $this->assertTrue($attempt->student->is($student));
        $this->assertSame(['Question 1', 'Question 2'], $attempt->items->pluck('prompt')->all());
        $this->assertSame(['value' => true], $attempt->items[1]->answer_key);
        $exam->unit->level->forceFill(['status' => 'draft'])->save();
        $this->postJson($url)->assertNotFound();
        $document = $this->exam();
        $document->forceFill(['type' => Exam::DOCUMENT])->save();
        $this->postJson(route('curriculum.exams.attempts.store', $document))->assertNotFound();
    }

    public function test_submission_grades_snapshot_and_replays_the_saved_score_after_deadline(): void
    {
        $this->freezeSecond();
        $exam = $this->exam(1);
        $question = $this->question($exam);
        $this->actingAs($this->student());
        $started = $this->postJson(route('curriculum.exams.attempts.store', $exam))->assertCreated()->json();
        $item = $started['questions'][0]['id'];
        $url = route('curriculum.exams.attempts.submit', [$exam, $started['id']]);
        $this->postJson($url, ['answers' => [$item => 'true', 9999 => true]])
            ->assertUnprocessable()->assertJsonValidationErrors(['answers', "answers.{$item}"]);
        $this->assertDatabaseHas('exam_attempt_items', ['id' => $item, 'submitted_answer' => null, 'awarded_points' => null]);
        $question->update(['answer' => ['value' => false], 'points' => 10]);
        $exam->update(['duration_minutes' => 10]);

        $this->travel(59)->seconds();
        $result = $this->postJson($url, ['answers' => [$item => true], 'awarded_points' => 999])
            ->assertOk()->assertJsonPath('status', 'completed')->assertJsonPath('awarded_points', 3)->json();
        $this->travel(10)->minutes();
        $this->postJson($url, ['answers' => [$item => false]])->assertOk()->assertExactJson($result);
        $this->assertDatabaseHas('exam_attempt_items', ['id' => $item, 'correct' => true, 'awarded_points' => 3]);
    }

    public function test_deadline_is_exclusive_and_expired_outcomes_have_no_answers_or_corrections(): void
    {
        $this->freezeSecond();
        $exam = $this->exam(1);
        $this->question($exam);
        $this->actingAs($this->student());
        $started = $this->postJson(route('curriculum.exams.attempts.store', $exam))->assertCreated()->json();
        $url = route('curriculum.exams.attempts.submit', [$exam, $started['id']]);
        $this->travel(1)->minute();
        $result = $this->postJson($url, ['answers' => [$started['questions'][0]['id'] => true]])
            ->assertOk()->assertJsonPath('status', 'expired')->assertJsonPath('expired_at', $started['deadline_at'])
            ->assertJsonPath('submitted_at', null)->assertJsonPath('awarded_points', null)->json();
        $this->travel(1)->minute();
        $this->postJson($url, ['answers' => []])->assertOk()->assertExactJson($result);
        $this->assertDatabaseHas('exam_attempt_items', ['exam_attempt_id' => $started['id'], 'submitted_answer' => null]);
        $this->getJson(route('curriculum.exams.attempts.show', [$exam, $started['id']]))
            ->assertOk()->assertJsonPath('status', 'expired')->assertJsonPath('questions', []);
    }

    public function test_submission_rolls_back_earlier_item_writes_when_finalization_fails(): void
    {
        $exam = $this->exam();
        $this->question($exam, 1);
        $this->question($exam, 2);
        $this->actingAs($this->student());
        $attempt = $this->postJson(route('curriculum.exams.attempts.store', $exam))->assertCreated()->json();
        [$first, $second] = array_column($attempt['questions'], 'id');

        ExamAttemptItem::updating(function (ExamAttemptItem $item): void {
            if ($item->position === 2) {
                throw ValidationException::withMessages(['answers' => 'Rejected by test.']);
            }
        });

        $this->postJson(route('curriculum.exams.attempts.submit', [$exam, $attempt['id']]), [
            'answers' => [$first => true, $second => true],
        ])->assertUnprocessable();
        $this->assertDatabaseHas('exam_attempts', ['id' => $attempt['id'], 'submitted_at' => null, 'awarded_points' => null]);
        $this->assertDatabaseHas('exam_attempt_items', ['id' => $first, 'submitted_answer' => null, 'awarded_points' => null]);
    }

    public function test_history_and_results_are_owned_and_scoped_to_the_exam(): void
    {
        $exam = $this->exam();
        $otherExam = $this->exam();
        $question = $this->question($exam);
        $student = $this->student();
        $this->actingAs($student);
        $store = route('curriculum.exams.attempts.store', $exam);
        $started = $this->postJson($store)->assertCreated()->assertJsonPath('deadline_at', null)->json();
        $show = route('curriculum.exams.attempts.show', [$exam, $started['id']]);
        $submit = route('curriculum.exams.attempts.submit', [$exam, $started['id']]);
        $history = route('curriculum.exams.attempts.index', $exam);
        $this->getJson($show)->assertNotFound();
        $this->getJson($history)->assertOk()->assertJsonPath('total', 0);
        $this->travel(1)->day();
        $this->postJson($submit, ['answers' => [$started['questions'][0]['id'] => false]])->assertOk();
        $question->delete();
        $this->getJson($show)->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('questions.0.submitted_answer', false)
            ->assertJsonPath('questions.0.correct_answer.value', true)
            ->assertJsonPath('questions.0.explanation', 'Sample explanation.')
            ->assertJsonPath('questions.0.awarded_points', 0);
        $this->question($exam);
        $retry = $this->postJson($store)->assertCreated()->json('id');
        $this->assertNotSame($started['id'], $retry);
        $this->getJson($history)->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $started['id']);
        $this->getJson(route('curriculum.exams.attempts.show', [$otherExam, $started['id']]))->assertNotFound();
        $this->actingAs($this->student())->getJson($history)->assertOk()->assertJsonPath('total', 0);
        $this->getJson($show)->assertNotFound();
        $this->postJson($submit, ['answers' => []])->assertNotFound();
        $exam->forceFill(['available_until' => now()])->save();
        $this->actingAs($student)->getJson($history)->assertNotFound();
        $this->getJson($show)->assertNotFound();
        $this->postJson($submit, ['answers' => []])->assertNotFound();
    }

    private function exam(?int $duration = null): Exam
    {
        $level = Level::create(['title' => 'Level'])->forceFill(['status' => 'published']);
        $level->save();
        $unit = $level->units()->create(['title' => 'Unit'])->forceFill(['status' => 'published']);
        $unit->save();

        return $unit->exams()->forceCreate([
            'type' => Exam::INTERACTIVE,
            'status' => 'published',
            'title' => 'Practice exam',
            'duration_minutes' => $duration,
        ]);
    }

    private function question(Exam $exam, int $position = 1): Question
    {
        return $exam->questions()->forceCreate([
            'position' => $position,
            'type' => QuestionType::TrueFalse,
            'prompt' => "Question {$position}",
            'content' => [],
            'answer' => ['value' => true],
            'explanation' => 'Sample explanation.',
            'points' => 3,
        ]);
    }

    private function student(): User
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::where('key', Role::STUDENT)->sole());

        return $student;
    }
}
