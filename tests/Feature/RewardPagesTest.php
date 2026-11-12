<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\PointCost;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Models\Role;
use App\Models\StudentAchievement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardPagesTest extends TestCase
{
    use RefreshDatabase;

    private const HTML = ['Accept' => 'text/html'];

    public function test_admin_pages_save_and_filter_rules_costs_and_milestones(): void
    {
        $index = route('admin.rewards.points');
        $this->get($index, self::HTML)->assertRedirect(route('account.login.show'));
        $this->actingAs($this->user(Role::TEACHER))->get($index, self::HTML)->assertForbidden();
        $this->actingAs($this->user(Role::ADMINISTRATOR));
        $this->get($index, self::HTML)->assertOk()->assertSee('No points rules yet')
            ->assertSee('href="'.route('admin.rewards.rules.create').'"', false);

        $create = route('admin.rewards.rules.create');
        $this->get($create, self::HTML)->assertOk()->assertSee('Qualifying activity');
        $rules = 'admin.reward-configuration.point-rules.';
        $rule = ['name' => 'Lesson points', 'event' => 'lesson_completion', 'points' => 5, 'enabled' => 1];
        $this->from($create)->post(route($rules.'store'), $rule + ['streak_days' => 2], self::HTML)
            ->assertRedirect($create)->assertSessionHasErrors('streak_days');
        $this->post(route($rules.'store'), $rule, self::HTML)->assertRedirect($index);
        $saved = PointRule::sole();
        $edit = route('admin.rewards.rules.edit', $saved);
        $this->get($edit, self::HTML)->assertOk()->assertSee('Lesson points');
        $this->put(route($rules.'update', $saved), array_replace($rule, ['points' => 8]), self::HTML)->assertRedirect($index);

        $streak = route('admin.rewards.streak.index');
        $this->get($streak, self::HTML)->assertOk()->assertSee('Learning days start at midnight UTC.');
        $this->post(route($rules.'store'), array_replace($rule, [
            'name' => 'Three days', 'event' => 'streak_milestone', 'streak_days' => 3,
        ]), self::HTML)->assertRedirect(route('admin.rewards.streak.edit', PointRule::where('name', 'Three days')->sole()));
        $this->get(route('admin.rewards.streak.edit', $saved), self::HTML)->assertNotFound();

        $this->get(route('admin.rewards.costs.create'), self::HTML)->assertOk()->assertSee('Ask Teacher');
        $this->post(route('admin.reward-configuration.point-costs.store'), [
            'target_id' => 'ask_teacher:0', 'points' => 10, 'enabled' => 0,
        ], self::HTML)->assertRedirect($index);
        $this->get(route('admin.rewards.costs.edit', PointCost::sole()), self::HTML)->assertOk()->assertSee('Ask Teacher');
        $this->get($index, self::HTML)->assertOk()->assertSee('Earn 8 points')->assertSee('Spend 10 points');
        $this->get($index.'?status=disabled&search=Teacher', self::HTML)->assertOk()
            ->assertSee('Spend 10 points')->assertDontSee('Lesson points');
    }

    public function test_student_pages_render_own_progress_awards_and_point_balance(): void
    {
        $admin = $this->user(Role::ADMINISTRATOR);
        $this->actingAs($admin);
        $index = route('admin.rewards.achievements.index');
        $this->get($index, self::HTML)->assertOk()->assertSee('No achievements yet');
        $this->get(route('admin.rewards.achievements.create'), self::HTML)->assertOk()->assertSee('Achievement criterion');
        $definition = [
            'name' => 'First steps', 'description' => 'Complete one lesson.', 'event' => 'lesson_completion',
            'required_count' => 1, 'enabled' => 1, 'reward_points' => 10,
        ];
        $this->post(route('admin.reward-configuration.achievements.store'), $definition, self::HTML)->assertRedirect($index);
        $badge = Achievement::sole();
        $this->get(route('admin.rewards.achievements.edit', $badge), self::HTML)->assertOk()->assertSee('First steps');
        $student = $this->user(Role::STUDENT);
        $other = $this->user(Role::STUDENT);
        $overview = route('account.rewards.index');
        $detail = route('account.rewards.show', $badge);

        $this->get($overview, self::HTML)->assertForbidden();
        $this->actingAs($student)->get($detail, self::HTML)->assertOk()->assertSee('0 of 1 completed lessons');
        PointTransaction::create([
            'student_id' => $student->id, 'amount' => 10, 'source_key' => 'achievement:'.$badge->id, 'recorded_at' => now(),
        ]);
        StudentAchievement::create([
            'student_id' => $student->id, 'achievement_id' => $badge->id, 'source_key' => 'lesson_completion:1', 'awarded_at' => now(),
        ]);
        $badge->update(['enabled' => false, 'reward_points' => 50]);
        $this->get($overview, self::HTML)->assertOk()->assertSee('10 pts')->assertSee('First steps')->assertSee('1 badge');
        $this->get($detail, self::HTML)->assertOk()->assertSee('1 of 1 completed lessons')->assertSee('Reward: 10 points');
        $this->get(route('account.home'), self::HTML)->assertOk()->assertSee('10 pts')
            ->assertSee('10 points earned this week')->assertSee('First steps')
            ->assertSee('href="'.$detail.'"', false);
        $this->actingAs($other)->get($detail, self::HTML)->assertNotFound();
        $this->get($overview, self::HTML)->assertOk()->assertSee('No achievements are available yet.')->assertSee('0 pts');
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $role)->sole());

        return $user;
    }
}
