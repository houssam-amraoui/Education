<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProfilePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['Accept' => 'text/html']);
        $this->withoutVite();
    }

    public function test_student_can_edit_persisted_profile_and_recover_from_validation_errors(): void
    {
        $student = $this->student();
        $other = User::factory()->create();
        $profile = route('account.profile.show');
        $update = route('account.profile.update');

        $this->get($profile)->assertRedirect(route('account.login.show'));
        $this->post(route('account.login'), ['email' => $student->email, 'password' => 'password'])->assertRedirect($profile);
        $this->get($profile)->assertOk()->assertSee($student->name)->assertSee($student->email)
            ->assertSee('name="_token"', false)->assertSee('No learning preferences saved yet.')
            ->assertDontSee('amina@example.test');

        $this->put($update, ['name' => 'My updated name', 'email' => $other->email])
            ->assertRedirect($profile)->assertSessionHasErrors('email');
        $this->get($profile)->assertSee('value="My updated name"', false)
            ->assertSee('aria-describedby="email-error"', false);
        $this->put($update, ['name' => 'My updated name', 'email' => $student->email, 'city' => 'Rabat'])
            ->assertStatus(303)->assertRedirect($profile);
        $this->get($profile)->assertSee('Profile saved.')->assertSee('value="Rabat"', false);
        $this->assertSame('My updated name', $student->fresh()->name);
    }

    public function test_preferences_show_empty_state_validate_and_survive_a_new_session(): void
    {
        $student = $this->student();
        $settings = route('account.preferences.show');
        $update = route('account.preferences.update');

        $this->actingAs($student)->get($settings)->assertOk()->assertSee('Choose a daily study goal')
            ->assertSee('Learning path selection is currently unavailable.')->assertDontSee('Exam preparation');
        $this->put($update, ['locale' => 'de', 'daily_study_goal_minutes' => '60', 'learning_focus' => 'Writing'])
            ->assertRedirect($settings)->assertSessionHasErrors(['locale', 'daily_study_goal_minutes']);
        $this->get($settings)->assertSee('value="Writing"', false)->assertSee('aria-describedby="locale-error"', false);

        $this->put($update, ['locale' => 'ar', 'daily_study_goal_minutes' => '30', 'learning_focus' => 'Writing'])
            ->assertStatus(303)->assertRedirect($settings);
        $this->get($settings)->assertSee('Settings saved.')->assertSee('lang="ar" dir="rtl"', false);

        $this->post(route('account.logout'))->assertRedirect(route('account.login.show'));
        $this->post(route('account.login'), ['email' => $student->email, 'password' => 'password'])->assertRedirect(route('account.home'));
        $this->get($settings)->assertSee('lang="ar" dir="rtl"', false)->assertSee('value="Writing"', false)
            ->assertSee('value="30" selected', false);
        $this->post(route('account.locale'), ['locale' => 'fr', 'return_to' => $settings])->assertRedirect($settings);
        $this->get($settings)->assertSee('lang="fr" dir="ltr"', false);
    }

    public function test_accounts_without_the_student_role_cannot_open_or_submit_the_forms(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('account.profile.show'))->assertForbidden()->assertSee('Access restricted');
        $this->get(route('account.preferences.show'))->assertForbidden();
        $this->put(route('account.profile.update'))->assertForbidden();
        $this->put(route('account.preferences.update'))->assertForbidden();
        $this->get(route('account.home'))->assertDontSee(route('account.profile.show'), false)
            ->assertDontSee(route('account.preferences.show'), false);
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', Role::STUDENT)->sole());

        return $user;
    }
}
