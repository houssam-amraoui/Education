<?php

namespace Tests\Feature;

use App\Actions\Rewards\UnlockWithPoints;
use App\Models\Level;
use App\Models\PointCost;
use App\Models\PointTransaction;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Queries\LearningStreak;
use App\Queries\PointBalance;
use App\Support\RewardTarget;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RewardBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrators_manage_validated_rules_achievements_and_costs(): void
    {
        $rule = ['name' => 'Practice', 'event' => 'exercise_result', 'points' => 15, 'enabled' => true];
        $rules = 'admin.reward-configuration.point-rules.';
        $this->postJson(route($rules.'store'), $rule)->assertUnauthorized();
        $this->actingAs($this->user(Role::TEACHER))->postJson(route($rules.'store'), $rule)->assertForbidden();
        $this->actingAs($this->user(Role::ADMINISTRATOR));
        $this->postJson(route($rules.'store'), $rule)->assertUnprocessable()->assertJsonValidationErrors('allow_retries');
        $id = $this->postJson(route($rules.'store'), $rule + ['allow_retries' => true, 'minimum_score_percent' => 50])
            ->assertCreated()->json('id');

        $rule['event'] = 'streak_milestone';
        $this->putJson(route($rules.'update', $id), $rule + ['streak_days' => 3])
            ->assertOk()->assertJsonPath('allow_retries', false)->assertJsonPath('minimum_score_percent', null);

        $achievements = 'admin.reward-configuration.achievements.';
        $badge = [
            'name' => 'First steps', 'description' => 'Complete a lesson', 'event' => 'lesson_completion',
            'required_count' => 1, 'enabled' => true,
        ];
        $this->postJson(route($achievements.'store'), $badge + ['minimum_score_percent' => 10])
            ->assertUnprocessable()->assertJsonValidationErrors('minimum_score_percent');
        $id = $this->postJson(route($achievements.'store'), $badge)->assertCreated()->json('id');
        $badge['enabled'] = false;
        $this->putJson(route($achievements.'update', $id), $badge)->assertOk()->assertJsonPath('enabled', false);

        $costs = 'admin.reward-configuration.point-costs.';
        $cost = ['target_type' => 'ask_teacher', 'target_id' => 0, 'points' => 30, 'enabled' => true];
        $this->postJson(route($costs.'store'), array_replace($cost, ['target_id' => 12]))
            ->assertUnprocessable()->assertJsonValidationErrors('target_id');
        $id = $this->postJson(route($costs.'store'), $cost)->assertCreated()->json('id');
        $this->postJson(route($costs.'store'), $cost)->assertUnprocessable()->assertJsonValidationErrors('target_id');
        $this->putJson(route($costs.'update', $id), ['points' => 15, 'enabled' => false])
            ->assertOk()->assertJsonPath('points', 15)->assertJsonPath('enabled', false);
    }

    public function test_lesson_replays_preserve_ledger_and_achievement_awards_across_midnight(): void
    {
        $this->actingAs($this->user(Role::ADMINISTRATOR));
        $ruleUrl = route('admin.reward-configuration.point-rules.store');
        $this->postJson($ruleUrl, ['name' => 'Lesson', 'event' => 'lesson_completion', 'points' => 3, 'enabled' => true])->assertCreated();
        $this->postJson($ruleUrl, ['name' => 'Disabled', 'event' => 'lesson_completion', 'points' => 99, 'enabled' => false])->assertCreated();
        $this->postJson($ruleUrl, ['name' => 'Streak', 'event' => 'streak_milestone', 'points' => 5, 'enabled' => true, 'streak_days' => 2])->assertCreated();
        $this->postJson(route('admin.reward-configuration.achievements.store'), [
            'name' => 'Two days', 'description' => 'Learn on two consecutive days', 'event' => 'streak_milestone',
            'required_count' => 2, 'enabled' => true, 'reward_points' => 7,
        ])->assertCreated();

        $student = $this->user(Role::STUDENT);
        $this->actingAs($student);
        $unit = $this->unit();
        $category = $unit->categories()->create(['title' => 'Grammar']);
        $urls = [];
        foreach (['First lesson', 'Second lesson', 'Third lesson'] as $title) {
            $lesson = $category->lessons()->create(['title' => $title]);
            $lesson->forceFill(['status' => 'published'])->save();
            $urls[] = route('curriculum.lesson.complete', [$unit->level, $unit, $category, $lesson]);
        }

        $this->travelTo(Carbon::parse('2026-11-12 23:59:59 UTC'));
        $this->post($urls[0])->assertRedirect();
        $this->travel(1)->second();
        $this->post($urls[0])->assertRedirect();
        $this->assertSame(3, app(PointBalance::class)->for($student));
        $this->post($urls[1])->assertRedirect();
        $this->post($urls[2])->assertRedirect();
        $this->assertSame(21, app(PointBalance::class)->for($student));
        $this->assertSame(0, app(PointBalance::class)->for($this->user(Role::STUDENT)));
        $this->assertDatabaseCount('student_achievements', 1);
        $this->assertSame(['days' => 2, 'started_on' => '2026-11-12'], app(LearningStreak::class)->current($student));
        $this->travel(1)->day();
        $this->assertSame(2, app(LearningStreak::class)->current($student)['days']);
        $this->travel(1)->day();
        $this->assertSame(0, app(LearningStreak::class)->current($student)['days']);

        $entry = PointTransaction::query()->orderBy('id')->firstOrFail();
        $this->assertLedgerWriteRejected(fn () => PointTransaction::whereKey($entry->id)->update(['amount' => 999]));
        $this->assertLedgerWriteRejected(fn () => PointTransaction::whereKey($entry->id)->delete());
        $this->expectException(UniqueConstraintViolationException::class);
        PointTransaction::create($entry->only(['student_id', 'amount', 'source_key', 'recorded_at']));
    }

    public function test_assessment_awards_use_final_scores_and_explicit_retry_eligibility(): void
    {
        $this->actingAs($this->user(Role::ADMINISTRATOR));
        $url = route('admin.reward-configuration.point-rules.store');
        $base = ['name' => 'Practice', 'points' => 4, 'enabled' => true];
        $this->postJson($url, $base + ['event' => 'exercise_result', 'allow_retries' => false, 'minimum_score_percent' => 100])->assertCreated();
        $this->postJson($url, $base + ['event' => 'exercise_result', 'allow_retries' => true, 'minimum_score_percent' => 100])->assertCreated();
        $this->postJson($url, $base + ['event' => 'exam_completion', 'allow_retries' => false])->assertCreated();

        $student = $this->user(Role::STUDENT);
        $this->actingAs($student);
        $unit = $this->unit();
        $exercise = $unit->exercises()->create(['title' => 'Practice', 'instructions' => 'Answer the question.']);
        $exam = $unit->exams()->create([
            'title' => 'Exam', 'instructions' => 'Answer the question.', 'type' => 'interactive', 'duration_minutes' => 1,
        ]);
        foreach ([$exercise, $exam] as $assessment) {
            $assessment->forceFill(['status' => 'published'])->save();
            $assessment->questions()->forceCreate([
                'position' => 1, 'type' => 'true_false', 'prompt' => 'True?', 'content' => [],
                'answer' => ['value' => true], 'points' => 3,
            ]);
        }

        $prefix = 'curriculum.exercises.attempts.';
        $start = $this->postJson(route($prefix.'store', $exercise))->assertCreated()->json();
        $this->postJson(route($prefix.'submit', [$exercise, $start['id']]), [
            'answers' => [$start['questions'][0]['id'] => false],
        ])->assertOk();
        $this->assertSame(0, app(PointBalance::class)->for($student));

        $retry = $this->postJson(route($prefix.'retry', [$exercise, $start['id']]))->assertCreated()->json();
        $submit = route($prefix.'submit', [$exercise, $retry['id']]);
        $answers = ['answers' => [$retry['questions'][0]['id'] => true]];
        $this->postJson($submit, $answers)->assertOk();
        $this->postJson($submit, $answers)->assertOk();
        $this->assertSame(4, app(PointBalance::class)->for($student));

        $prefix = 'curriculum.exams.attempts.';
        $start = $this->postJson(route($prefix.'store', $exam))->assertCreated()->json();
        $this->travel(1)->minute();
        $this->postJson(route($prefix.'submit', [$exam, $start['id']]), [
            'answers' => [$start['questions'][0]['id'] => true],
        ])->assertOk()->assertJsonPath('status', 'expired');
        $this->assertSame(4, app(PointBalance::class)->for($student));

        $start = $this->postJson(route($prefix.'store', $exam))->assertCreated()->json();
        $this->postJson(route($prefix.'submit', [$exam, $start['id']]), [
            'answers' => [$start['questions'][0]['id'] => true],
        ])->assertOk()->assertJsonPath('status', 'completed');
        $this->assertSame(8, app(PointBalance::class)->for($student));
    }

    public function test_unlock_replays_original_debit_and_never_uses_another_students_balance(): void
    {
        $student = $this->user(Role::STUDENT);
        $other = $this->user(Role::STUDENT);
        $cost = PointCost::create(['target_type' => RewardTarget::AskTeacher, 'target_id' => 0, 'points' => 7, 'enabled' => true]);
        PointTransaction::create(['student_id' => $student->id, 'amount' => 10, 'source_key' => 'earned', 'recorded_at' => now()]);
        $unlock = app(UnlockWithPoints::class);

        try {
            $unlock->handle($other, RewardTarget::AskTeacher, 0);
            $this->fail('Expected an insufficient balance error.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('points', $exception->errors());
        }
        $this->assertDatabaseCount('point_unlocks', 0);
        $this->assertDatabaseCount('point_transactions', 1);

        $record = $unlock->handle($student, RewardTarget::AskTeacher, 0);
        $cost->update(['points' => 99, 'enabled' => false]);
        $this->assertTrue($record->is($unlock->handle($student, RewardTarget::AskTeacher, 0)));
        $this->assertSame(-7, $record->pointTransaction->amount);
        $this->assertSame(3, app(PointBalance::class)->for($student));
        $this->assertDatabaseCount('point_unlocks', 1);
        $this->assertDatabaseCount('point_transactions', 2);
    }

    private function assertLedgerWriteRejected(callable $write): void
    {
        try {
            DB::transaction($write);
            $this->fail('Expected the ledger to reject mutation.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Point transactions are immutable', $exception->getMessage());
        }
    }

    private function unit(): Unit
    {
        $level = Level::create(['title' => 'Level']);
        $level->forceFill(['status' => 'published'])->save();
        $unit = $level->units()->create(['title' => 'Unit']);
        $unit->forceFill(['status' => 'published'])->save();

        return $unit;
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $role)->sole());

        return $user;
    }
}
