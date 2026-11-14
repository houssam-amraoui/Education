<?php

namespace Tests\Feature;

use App\Models\AccessRule;
use App\Models\Level;
use App\Models\PointCost;
use App\Models\PointTransaction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccessPagesTest extends TestCase
{
    use RefreshDatabase;

    private const HTML = ['Accept' => 'text/html'];

    public function test_manager_can_find_create_and_edit_rules_from_the_content_editor(): void
    {
        $level = Level::forceCreate(['title' => 'English studies', 'status' => 'published']);
        $index = route('admin.access-rules.index');
        $store = route('admin.access-rules.store');
        $scoped = route('admin.access-rules.index', ['type' => 'level', 'target' => $level->id]);
        $create = route('admin.access-rules.create', ['type' => 'level', 'target' => $level->id]);
        $this->get($index, self::HTML)->assertRedirect(route('account.login.show'));
        $this->actingAs($this->user())->get($index, self::HTML)->assertForbidden();

        $this->actingAs($this->user(Role::TEACHER));
        $this->get(route('admin.curriculum.levels.edit', $level), self::HTML)->assertSee($scoped);
        $this->get($scoped, self::HTML)->assertOk()->assertSee('No access rules yet')->assertSee($create);
        $this->get($create, self::HTML)->assertOk()->assertSee('value="level:'.$level->id.'" selected', false);
        $input = ['target' => 'level:'.$level->id, 'requirement' => 'level'];
        $this->from($create)->post($store, $input, self::HTML)->assertRedirect($create)->assertSessionHasErrors('required_level_id');
        $this->get($create, self::HTML)->assertSee('aria-invalid="true"', false);
        $this->post($store, $input + ['required_level_id' => $level->id], self::HTML)->assertStatus(303);
        $rule = AccessRule::sole();
        $edit = route('admin.access-rules.edit', $rule);
        $this->get($index.'?search=English', self::HTML)->assertOk()->assertSee('English studies')->assertSee($edit);
        $this->get($index.'?search=missing', self::HTML)->assertOk()->assertSee('No matching records');
        $this->get($edit, self::HTML)->assertOk()->assertSee('Edit access rule');
        $this->put(route('admin.access-rules.update', $rule), array_replace($input, ['requirement' => 'public']), self::HTML)
            ->assertStatus(303)->assertRedirect($edit);
        $this->assertSame('public', $rule->refresh()->requirement);
    }

    public function test_requirement_page_returns_from_sign_in_and_learning_goals(): void
    {
        $level = Level::forceCreate(['title' => 'Advanced English', 'status' => 'published']);
        $rule = AccessRule::create(['target_type' => 'level', 'target_id' => $level->id, 'requirement' => 'level', 'required_level_id' => $level->id]);
        $destination = route('curriculum.level', $level);
        $requirements = route('access.requirements', ['level', $level->id]);
        $student = $this->user();

        $this->get($destination, self::HTML)->assertNotFound()->assertSee('Content access requirements')->assertSee('Sign in');
        $this->get(route('account.login.show', ['return_to' => $requirements]), self::HTML)->assertOk();
        $this->post(route('account.login'), ['email' => $student->email, 'password' => 'password'], self::HTML)
            ->assertStatus(303)->assertRedirect($requirements);
        $goals = route('account.learning-goals.show', ['return_to' => $requirements]);
        $this->get($requirements, self::HTML)->assertOk()->assertSee($goals);
        $this->get($goals, self::HTML)->assertOk()->assertSee('name="return_to"', false);
        $this->put(route('account.learning-goals.update'), ['level_ids' => [$level->id], 'return_to' => $requirements], self::HTML)
            ->assertRedirect($requirements);
        $this->get($requirements, self::HTML)->assertRedirect($destination);
        $this->get(route('curriculum.paths'), self::HTML)->assertOk()->assertSee(__('library.access.level'));
        $level->forceFill(['status' => 'archived'])->save();
        $this->get($requirements, self::HTML)->assertNotFound()->assertDontSee($level->title);
        $this->assertNotNull($rule->fresh());
    }

    public function test_points_unlock_uses_reviewed_cost_and_rechecks_access_after_spending(): void
    {
        $level = Level::forceCreate(['title' => 'English', 'status' => 'published']);
        $unit = $level->units()->forceCreate(['title' => 'Unit', 'status' => 'published']);
        $exercise = $unit->exercises()->forceCreate(['title' => 'Grammar practice', 'instructions' => 'Practise grammar.', 'status' => 'published']);
        AccessRule::create(['target_type' => 'exercise', 'target_id' => $exercise->id, 'requirement' => 'points']);
        $cost = PointCost::create(['target_type' => 'exercise', 'target_id' => $exercise->id, 'points' => 5, 'enabled' => true]);
        $parameters = ['exercise', $exercise->id];
        $destination = route('curriculum.exercises.instructions', $exercise);
        $show = route('access.unlock.show', $parameters);
        $spend = route('access.unlock.store', $parameters);
        $student = $this->user();

        $this->get($show, self::HTML)->assertRedirect(route('account.login.show'));
        $this->post($spend, ['points' => 5], self::HTML)->assertRedirect(route('account.login.show'));
        $this->actingAs($this->user(Role::TEACHER))->get($show, self::HTML)->assertForbidden();
        $this->post($spend, ['points' => 5], self::HTML)->assertForbidden();
        $this->actingAs($student)->get($destination, self::HTML)->assertNotFound()->assertSee($show);
        $this->get(route('account.rewards.index'), self::HTML)->assertOk()->assertSee($exercise->title);
        $this->get($show, self::HTML)->assertOk()->assertSee('Insufficient point balance.');
        $this->post($spend, ['points' => 5], self::HTML)->assertRedirect($show)->assertSessionHasErrors('points');
        $this->assertDatabaseCount('point_transactions', 0);
        PointTransaction::create(['student_id' => $student->id, 'source_key' => 'earned', 'amount' => 10, 'recorded_at' => now()]);
        $this->post($spend, [], self::HTML)->assertRedirect($show)->assertSessionHasErrors('points');
        $cost->update(['points' => 6]);
        $this->post($spend, ['points' => 5], self::HTML)->assertRedirect($show)->assertSessionHasErrors('points');
        $this->assertDatabaseCount('point_unlocks', 0);
        $this->get($show, self::HTML)->assertOk()->assertSee('Unlock for 6 points');
        $this->post($spend, ['points' => 6], self::HTML)->assertRedirect($show);
        $this->get($show, self::HTML)->assertOk()->assertSee('Open unlocked content')->assertSee('4 points')->assertSee($destination);
        $this->get($destination, self::HTML)->assertOk();
        $this->post($spend, ['points' => 6], self::HTML)->assertRedirect($show);
        $this->assertDatabaseCount('point_transactions', 2);
        $this->assertDatabaseCount('entitlements', 1);
        $this->actingAs($this->user())->get($destination, self::HTML)->assertNotFound();
        $this->get($show, self::HTML)->assertOk()->assertDontSee('Open unlocked content');
    }

    public function test_unlock_page_distinguishes_existing_access_from_unavailable_spending(): void
    {
        $parameters = ['ask_teacher', 0];
        $show = route('access.unlock.show', $parameters);
        $spend = route('access.unlock.store', $parameters);
        $this->actingAs($this->user())->get($show, self::HTML)->assertOk()
            ->assertSee('You already have access to this destination.')
            ->assertDontSee('Open unlocked content')->assertDontSee($spend);

        AccessRule::create(['target_type' => 'ask_teacher', 'target_id' => 0, 'requirement' => 'points']);
        $this->get($show, self::HTML)->assertOk()
            ->assertSee('This points unlock is currently unavailable.')
            ->assertSee(route('access.requirements', $parameters))->assertDontSee($spend);
        $this->post($spend, ['points' => 5], self::HTML)->assertRedirect($show)->assertSessionHasErrors('points');
        $this->assertDatabaseCount('point_transactions', 0);
        $this->assertDatabaseCount('entitlements', 0);
    }

    private function user(string $role = Role::STUDENT): User
    {
        return User::factory()->hasAttached(Role::where('key', $role)->sole())->create();
    }

    public function test_ask_teacher_unlock_returns_to_the_signed_lesson_context(): void
    {
        $level = Level::forceCreate(['title' => 'English', 'status' => 'published']);
        $unit = $level->units()->forceCreate(['title' => 'Unit', 'status' => 'published']);
        $category = $unit->categories()->forceCreate(['title' => 'Grammar', 'status' => 'published']);
        $lesson = $category->lessons()->forceCreate(['title' => 'Verb forms', 'status' => 'published']);
        $student = $this->user();
        AccessRule::create(['target_type' => 'ask_teacher', 'target_id' => 0, 'requirement' => 'points']);
        PointCost::create(['target_type' => 'ask_teacher', 'target_id' => 0, 'points' => 5, 'enabled' => true]);
        PointTransaction::create(['student_id' => $student->id, 'source_key' => 'earned', 'amount' => 5, 'recorded_at' => now()]);
        $source = URL::signedRoute('account.messages.source', ['type' => 'lesson', 'source' => $lesson->id]);
        $parameters = ['ask_teacher', 0];

        $this->actingAs($student)->get($source, self::HTML)->assertRedirect(route('access.requirements', $parameters));
        $this->post(route('access.unlock.store', $parameters), ['points' => 5], self::HTML)->assertStatus(303);
        $this->get(route('access.unlock.show', $parameters), self::HTML)->assertOk()->assertSee($source);
        $this->get($source, self::HTML)->assertOk()->assertSee($lesson->title);
    }
}
