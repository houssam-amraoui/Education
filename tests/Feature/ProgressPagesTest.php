<?php

namespace Tests\Feature;

use App\Actions\Auth\SaveSelectedLevels;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExerciseAttempt;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProgressPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_replace_repeat_and_clear_only_their_own_path_selections(): void
    {
        $student = $this->user(Role::STUDENT);
        $other = $this->user(Role::STUDENT);
        $first = Level::forceCreate(['title' => 'First path', 'status' => 'published']);
        $second = Level::forceCreate(['title' => 'Second path', 'status' => 'published']);
        $third = Level::forceCreate(['title' => 'Third path', 'status' => 'published']);
        $student->selectedLevels()->attach($first);
        $other->selectedLevels()->attach($first);
        $url = route('account.learning-goals.update');
        $input = ['user_id' => $other->id, 'level_ids' => [(string) $second->id, (string) $third->id]];

        $this->putJson($url, $input)->assertUnauthorized();
        $this->actingAs($this->user(Role::ADMINISTRATOR))->putJson($url, $input)->assertForbidden();
        $this->actingAs($student)->put($url, $input)->assertStatus(303);
        $this->put($url, $input)->assertStatus(303);
        $this->assertEqualsCanonicalizing([$second->id, $third->id], $student->selectedLevels()->get()->modelKeys());

        $this->put($url)->assertStatus(303);
        $this->assertSame([], $student->selectedLevels()->get()->modelKeys());
        $this->assertSame([$first->id], $other->selectedLevels()->get()->modelKeys());
    }

    public function test_path_save_rechecks_publication_and_uniqueness_before_replacing_selections(): void
    {
        $student = $this->user(Role::STUDENT);
        $selected = Level::forceCreate(['title' => 'Saved path', 'status' => 'published']);
        $replacement = Level::forceCreate(['title' => 'New path', 'status' => 'published']);
        $unavailable = Level::forceCreate(['title' => 'Unavailable path', 'status' => 'archived']);
        $missingId = $unavailable->id + 1;
        $student->selectedLevels()->attach($selected);
        $save = app(SaveSelectedLevels::class);

        foreach ([
            [$replacement->id, $unavailable->id],
            [$replacement->id, $missingId],
            [$replacement->id, (string) $replacement->id],
        ] as $levelIds) {
            try {
                $save->handle($student, $levelIds);
                $this->fail('Expected the save to reject invalid path selections.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('level_ids', $exception->errors());
            }

            $this->assertSame([$selected->id], $student->selectedLevels()->get()->modelKeys());
        }
    }

    public function test_student_can_save_paths_resume_a_lesson_and_see_completion_and_history(): void
    {
        [$level, $lesson, $parameters] = $this->lesson();
        $student = $this->user(Role::STUDENT);
        $url = route('curriculum.lesson', $parameters);
        $complete = route('curriculum.lesson.complete', $parameters);

        $this->get($url)->assertOk()->assertDontSee('Mark as complete');
        $this->assertDatabaseCount('lesson_progress', 0);
        $this->postJson($complete)->assertUnauthorized();
        $this->get(route('account.progress.show'))->assertRedirect(route('account.login.show'));

        $this->actingAs($student)->get(route('account.home'))->assertOk()
            ->assertSee('No learning paths selected.')->assertSee('No scores yet.');
        $this->get(route('account.learning-goals.show'))->assertOk()->assertSee($level->title);
        $draft = Level::create(['title' => 'Draft path']);
        $this->putJson(route('account.learning-goals.update'), ['level_ids' => [$draft->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('level_ids.0');
        $this->from(route('account.learning-goals.show'))
            ->put(route('account.learning-goals.update'), ['level_ids' => [$level->id, $level->id]])
            ->assertRedirect(route('account.learning-goals.show'))->assertSessionHasErrors('level_ids.0');
        $this->get(route('account.learning-goals.show'))->assertOk()->assertSee('data-live-errors', false);
        $this->put(route('account.learning-goals.update'), ['level_ids' => [$level->id]])
            ->assertRedirect(route('account.home'));
        $this->assertDatabaseHas('level_user', ['level_id' => $level->id, 'user_id' => $student->id]);

        $this->get($url)->assertOk()->assertSee('Mark as complete');
        $this->get(route('account.home'))->assertOk()->assertSee('Continue learning')->assertSee($lesson->title);
        $this->post($complete)->assertRedirect($url);
        $this->get($url)->assertOk()->assertSee('Lesson marked as complete.')->assertDontSee('Mark as complete');
        $this->get(route('account.home'))->assertOk()->assertSee('No lesson to resume')->assertSee('1 of 1 lessons complete');
        $this->get(route('account.progress.show'))->assertOk()->assertSee($level->title);
        $this->get(route('account.progress.level', $level))->assertOk()->assertSee('Your units')->assertSee('100%');
        $this->get(route('account.progress.history'))->assertOk()->assertSee('Viewed')->assertSee('Completed')->assertSee($url, false);

        $level->forceFill(['status' => 'archived'])->save();
        $this->post($complete)->assertNotFound();
        $this->get(route('account.progress.level', $level))->assertNotFound();
        $this->get(route('account.progress.history'))->assertOk()->assertDontSee($lesson->title);
    }

    public function test_manager_activity_links_only_to_owned_finalized_visible_attempts(): void
    {
        [, $lesson] = $this->lesson();
        $student = $this->user(Role::STUDENT);
        $other = $this->user(Role::STUDENT);
        $manager = $this->user(Role::ADMINISTRATOR);
        $unit = $lesson->category->unit;
        $exercise = $unit->exercises()->create(['title' => 'Saved exercise', 'instructions' => 'Practice.']);
        $exam = $unit->exams()->create(['title' => 'Saved exam', 'type' => Exam::INTERACTIVE]);
        $exercise->forceFill(['status' => 'published'])->save();
        $exam->forceFill(['status' => 'published'])->save();
        $score = ['started_at' => now(), 'submitted_at' => now(), 'possible_points' => 12, 'awarded_points' => 9];
        $attempt = ExerciseAttempt::forceCreate($score + ['student_id' => $student->id, 'exercise_id' => $exercise->id]);
        $attempt->items()->forceCreate([
            'source_question_id' => 1, 'position' => 1, 'type' => 'short_answer', 'prompt' => 'Saved question',
            'content' => [], 'answer_key' => ['accepted' => ['expected answer']], 'submitted_answer' => 'learner answer',
            'explanation' => 'Saved explanation', 'correct' => false, 'points' => 3, 'awarded_points' => 0,
        ]);
        $examAttempt = ExamAttempt::forceCreate($score + ['student_id' => $student->id, 'exam_id' => $exam->id]);
        $overview = route('admin.accounts.users.progress', $student);
        $review = route('admin.accounts.users.progress.review', [$student, 'exercise', $attempt]);
        $examReview = route('admin.accounts.users.progress.review', [$student, 'exam', $examAttempt]);

        $this->actingAs($student)->get(route('account.home'))->assertOk()->assertSee('9 / 12')
            ->assertSee(route('curriculum.exercises.attempts.show', [$exercise, $attempt]), false);
        $this->get($overview)->assertForbidden();
        $this->actingAs($manager)->get(route('admin.accounts.users.index'))->assertOk()->assertSee($overview, false);
        $this->get($overview)->assertOk()->assertSee($student->email)->assertSee($review, false)->assertSee($examReview, false);
        $this->get($review)->assertOk()->assertSee('Saved exercise')->assertSee('Completed attempt')
            ->assertSee('Saved question')->assertSee('expected answer')->assertSee('learner answer')->assertSee('Saved explanation');
        $this->get($examReview)->assertOk()->assertSee('Saved exam');
        $this->get(route('admin.accounts.users.progress.review', [$other, 'exercise', $attempt]))->assertNotFound();

        $exam->forceFill(['status' => 'archived'])->save();
        $this->get($overview)->assertOk()->assertDontSee($examReview, false);
        $this->get($examReview)->assertNotFound();
    }

    private function lesson(): array
    {
        $level = Level::create(['title' => 'General English']);
        $level->forceFill(['status' => 'published'])->save();
        $unit = $level->units()->create(['title' => 'Grammar']);
        $unit->forceFill(['status' => 'published'])->save();
        $category = $unit->categories()->create(['title' => 'Verbs']);
        $lesson = $category->lessons()->create(['title' => 'Present tense', 'body' => '<p>Study the present tense.</p>']);
        $lesson->forceFill(['status' => 'published'])->save();

        return [$level, $lesson, [$level, $unit, $category, $lesson]];
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $role)->firstOrFail());

        return $user;
    }
}
