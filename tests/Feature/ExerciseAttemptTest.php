<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\ExerciseAttempt;
use App\Models\Level;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_snapshots_ordered_questions_for_the_signed_in_student(): void
    {
        $exercise = $this->exercise();
        $first = $this->question($exercise, 1);
        $second = $this->question($exercise, 2);
        $url = route('curriculum.exercises.attempts.store', $exercise);
        $this->postJson($url)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson($url)->assertForbidden();

        $student = $this->student();
        $this->actingAs($student);
        $id = $this->postJson($url, ['student_id' => 999, 'possible_points' => 99])
            ->assertCreated()
            ->assertJsonPath('possible_points', 6)
            ->assertJsonPath('questions.0.prompt', 'Question 1')
            ->assertJsonPath('questions.1.prompt', 'Question 2')
            ->assertJsonMissingPath('questions.0.answer_key')
            ->assertJsonMissingPath('questions.0.explanation')
            ->json('id');
        $first->update(['prompt' => 'Revised', 'points' => 9]);
        $second->delete();
        $attempt = ExerciseAttempt::with('items')->findOrFail($id);
        $this->assertTrue($attempt->student->is($student));
        $this->assertSame(['Question 1', 'Question 2'], $attempt->items->pluck('prompt')->all());
        $this->assertSame(['value' => true], $attempt->items[0]->answer_key);
        $this->assertSame([3, 3], $attempt->items->pluck('points')->all());
    }

    public function test_start_rejects_empty_exercises_and_hidden_ancestry(): void
    {
        $exercise = $this->exercise();
        $this->actingAs($this->student());
        $url = route('curriculum.exercises.attempts.store', $exercise);
        $this->postJson($url)->assertUnprocessable()->assertJsonValidationErrors('exercise');
        $this->question($exercise);
        $exercise->unit->level->forceFill(['status' => 'draft'])->save();
        $this->postJson($url)->assertNotFound();
        $this->assertDatabaseCount('exercise_attempts', 0);
    }

    public function test_submission_validates_snapshot_answers_and_finalizes_once(): void
    {
        $exercise = $this->exercise();
        $first = $this->question($exercise);
        $this->question($exercise, 2);
        $student = $this->student();
        $this->actingAs($student);
        $started = $this->postJson(route('curriculum.exercises.attempts.store', $exercise))->assertCreated()->json();
        [$one, $two] = array_column($started['questions'], 'id');
        $url = route('curriculum.exercises.attempts.submit', [$exercise, $started['id']]);

        $this->actingAs($this->student())->postJson($url, ['answers' => [$one => true, $two => true]])->assertNotFound();
        $this->actingAs($student);
        $this->postJson($url, ['answers' => [$one => true, 9999 => true]])
            ->assertUnprocessable()->assertJsonValidationErrors(['answers', "answers.{$two}"]);
        $this->postJson($url, ['answers' => [$one => true, $two => 'true']])
            ->assertUnprocessable()->assertJsonValidationErrors("answers.{$two}");
        $attempt = ExerciseAttempt::findOrFail($started['id']);
        $this->assertNull($attempt->submitted_at);
        $this->assertSame([null, null], $attempt->items()->pluck('submitted_answer')->all());

        $first->update(['answer' => ['value' => false], 'points' => 20]);
        $result = $this->postJson($url, [
            'answers' => [$one => true, $two => false],
            'awarded_points' => 999,
            'possible_points' => 999,
        ])->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('possible_points', 6)
            ->assertJsonPath('awarded_points', 3)
            ->json();
        $this->travel(1)->minute();
        $this->postJson($url, ['answers' => [$one => false, $two => false]])->assertOk()->assertExactJson($result);
        $this->assertSame([true, false], $attempt->items()->get()->pluck('submitted_answer')->all());
        $this->assertSame([3, 0], $attempt->items()->get()->pluck('awarded_points')->all());
        $this->assertDatabaseCount('exercise_attempts', 1);
        $this->assertDatabaseCount('exercise_attempt_items', 2);
    }

    public function test_history_and_results_are_owned_and_retry_captures_fresh_questions(): void
    {
        $exercise = $this->exercise();
        $question = $this->question($exercise);
        $store = route('curriculum.exercises.attempts.store', $exercise);
        $history = route('curriculum.exercises.attempts.index', $exercise);
        $student = $this->student();
        $this->actingAs($student);
        $started = $this->postJson($store)->assertCreated()->json();
        $result = route('curriculum.exercises.attempts.show', [$exercise, $started['id']]);
        $retry = route('curriculum.exercises.attempts.retry', [$exercise, $started['id']]);
        $submit = route('curriculum.exercises.attempts.submit', [$exercise, $started['id']]);
        $this->getJson($result)->assertNotFound();
        $this->postJson($retry)->assertNotFound();
        $this->getJson($history)->assertJsonCount(0, 'data');

        $this->postJson($submit, ['answers' => [$started['questions'][0]['id'] => false]])->assertOk();
        $question->update(['prompt' => 'Revised prompt', 'points' => 5, 'explanation' => 'Revised explanation.']);
        $this->getJson($result)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('questions.0.prompt', 'Question 1')
            ->assertJsonPath('questions.0.submitted_answer', false)
            ->assertJsonPath('questions.0.correct_answer.value', true)
            ->assertJsonPath('questions.0.correct', false)
            ->assertJsonPath('questions.0.explanation', 'Sample explanation.')
            ->assertJsonPath('questions.0.awarded_points', 0);

        $otherExercise = $this->exercise();
        $this->getJson(route('curriculum.exercises.attempts.show', [$otherExercise, $started['id']]))->assertNotFound();
        $this->actingAs($this->student());
        $this->getJson($history)->assertJsonCount(0, 'data');
        $this->getJson($result)->assertNotFound();
        $this->postJson($retry)->assertNotFound();

        $this->actingAs($student);
        $new = $this->postJson($retry)->assertCreated()
            ->assertJsonPath('questions.0.prompt', 'Revised prompt')
            ->assertJsonPath('possible_points', 5)
            ->assertJsonMissingPath('questions.0.explanation')
            ->json();
        $this->assertNotSame($started['id'], $new['id']);
        $this->assertNotSame($started['questions'][0]['id'], $new['questions'][0]['id']);
        $this->assertNull(ExerciseAttempt::findOrFail($new['id'])->submitted_at);
        $this->getJson($history)->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $started['id']);

        $exercise->forceFill(['status' => 'draft'])->save();
        $this->getJson($history)->assertNotFound();
        $this->getJson($result)->assertNotFound();
        $this->postJson($retry)->assertNotFound();
        $this->postJson($submit, ['answers' => []])->assertNotFound();
    }

    public function test_submission_rejects_malformed_or_foreign_identifiers_for_structured_answers(): void
    {
        $exercise = $this->exercise();
        $items = [['id' => 'a', 'label' => 'A'], ['id' => 'b', 'label' => 'B']];
        $exercise->questions()->forceCreateMany([
            ['position' => 1, 'type' => 'single_choice', 'content' => ['options' => $items], 'answer' => ['option_id' => 'b'], 'prompt' => 'Pick one', 'points' => 1],
            ['position' => 2, 'type' => 'multiple_selection', 'content' => ['options' => $items], 'answer' => ['option_ids' => ['a', 'b']], 'prompt' => 'Pick two', 'points' => 1],
            ['position' => 3, 'type' => 'ordering', 'content' => ['items' => $items], 'answer' => ['item_ids' => ['b', 'a']], 'prompt' => 'Order items', 'points' => 1],
            ['position' => 4, 'type' => 'matching', 'content' => ['left' => $items, 'right' => $items], 'answer' => ['pairs' => [
                ['left_id' => 'a', 'right_id' => 'b'],
                ['left_id' => 'b', 'right_id' => 'a'],
            ]], 'prompt' => 'Match items', 'points' => 1],
            ['position' => 5, 'type' => 'fill_in_the_blanks', 'content' => ['slots' => $items], 'answer' => ['slots' => [
                ['id' => 'a', 'accepted' => ['is']],
                ['id' => 'b', 'accepted' => ['was']],
            ]], 'prompt' => '___ ___', 'points' => 1],
            ['position' => 6, 'type' => 'short_answer', 'content' => [], 'answer' => ['accepted' => ['Correct']], 'prompt' => 'Write an answer', 'points' => 1],
        ]);

        $this->actingAs($this->student());
        $started = $this->postJson(route('curriculum.exercises.attempts.store', $exercise))->assertCreated()->json();
        [$single, $multiple, $order, $match, $blanks, $short] = array_column($started['questions'], 'id');
        $submit = route('curriculum.exercises.attempts.submit', [$exercise, $started['id']]);
        $this->postJson($submit, ['answers' => [
            $single => 'foreign',
            $multiple => ['a', 'a'],
            $order => ['b', ['a']],
            $match => ['a' => 'a', 'b' => 'a'],
            $blanks => ['a' => 'is', 'foreign' => 'was'],
            $short => ['Correct'],
        ]])->assertUnprocessable()->assertJsonValidationErrors([
            "answers.{$single}", "answers.{$multiple}", "answers.{$order}", "answers.{$match}", "answers.{$blanks}", "answers.{$short}",
        ]);

        $this->postJson($submit, ['answers' => [
            $single => 'b',
            $multiple => ['b', 'a'],
            $order => ['a', 'b'],
            $match => ['a' => 'b', 'b' => 'a'],
            $blanks => ['a' => ' IS ', 'b' => 'was'],
            $short => 'correct',
        ]])->assertOk()->assertJsonPath('awarded_points', 5);
    }

    private function exercise(): Exercise
    {
        $level = Level::create(['title' => 'Level']);
        $level->forceFill(['status' => 'published'])->save();
        $unit = $level->units()->create(['title' => 'Unit']);
        $unit->forceFill(['status' => 'published'])->save();
        $exercise = $unit->exercises()->create(['title' => 'Passive voice', 'instructions' => 'Answer each question.']);
        $exercise->forceFill(['status' => 'published'])->save();

        return $exercise;
    }

    private function question(Exercise $exercise, int $position = 1): Question
    {
        return $exercise->questions()->forceCreate([
            'position' => $position,
            'type' => 'true_false',
            'prompt' => 'Question '.$position,
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
