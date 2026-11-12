<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['Accept' => 'text/html']);
        $this->withoutVite();
    }

    public function test_registration_and_session_forms_use_persisted_accounts(): void
    {
        $register = route('account.register.show');
        $login = route('account.login.show');
        $this->get($register)->assertOk()->assertSee('Create account')->assertSee('name="_token"', false)->assertDontSee('Sample data only');

        $identity = ['name' => 'Live Learner', 'email' => 'learner@example.test', 'password' => 'lesson-password'];
        $this->from($register)->post(route('account.register'), $identity)->assertRedirect($register)->assertSessionHasErrors('password');
        $this->get($register)->assertSee('value="learner@example.test"', false)->assertDontSee('lesson-password');
        $this->post(route('account.register'), $identity + ['password_confirmation' => 'lesson-password'])
            ->assertRedirect($login)->assertSessionHas('status');
        $user = User::where('email', $identity['email'])->sole();
        $this->assertSame(['student'], $user->roles()->pluck('key')->all());

        $this->from($login)->post(route('account.login'), ['email' => $user->email, 'password' => 'wrong'])
            ->assertRedirect($login)->assertSessionHasErrors('email');
        $this->get($login)->assertSee('aria-invalid="true"', false);
        $this->post(route('account.login'), ['email' => $user->email, 'password' => $identity['password']])
            ->assertRedirect(route('account.home'));
        $this->get(route('account.home'))->assertOk()->assertSee('Live Learner')->assertDontSee('Manage users');
        $this->post(route('account.logout'))->assertRedirect($login);
        $this->assertGuest();
    }

    public function test_login_returns_only_to_live_internal_pages_and_applies_destination_authorization(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $list = route('admin.accounts.users.index', ['search' => 'name']);
        $this->get($list)->assertRedirect(route('account.login.show'));
        $this->post(route('account.login'), ['email' => $admin->email, 'password' => 'password'])->assertRedirect($list);
        $this->post(route('account.logout'));

        $student = $this->userWithRole(Role::STUDENT);
        $this->get($list)->assertRedirect(route('account.login.show'));
        $this->post(route('account.login'), ['email' => $student->email, 'password' => 'password'])->assertRedirect($list);
        $this->get($list)->assertForbidden()->assertSee('Access restricted')->assertDontSee($admin->email);
        $this->post(route('account.logout'));

        foreach (['https://example.test/account', '//example.test/account', '/\\example.test/account', '/dashboard', '/account/logout'] as $destination) {
            $this->withSession(['url.intended' => $destination])
                ->post(route('account.login'), ['email' => $student->email, 'password' => 'password', 'return_to' => 'https://example.test'])
                ->assertRedirect(route('account.home'));
            $this->post(route('account.logout'));
        }
    }

    public function test_user_list_and_editor_show_persisted_values_and_save_identity_and_membership(): void
    {
        $this->actingAs($this->userWithRole(Role::ADMINISTRATOR));
        $list = route('admin.accounts.users.index');
        $this->get($list.'?search=no-such-user')->assertOk()->assertSee('No matching records');
        $create = route('admin.accounts.users.create');
        $this->get($create)->assertOk()->assertSee('New accounts start with the Student role.');
        $this->from($create)->post(route('admin.accounts.users.store'), [
            'name' => 'Persisted User', 'email' => 'persisted@example.test',
            'password' => 'account-password', 'password_confirmation' => 'account-password',
        ])->assertStatus(303);
        $user = User::where('email', 'persisted@example.test')->sole();
        $edit = route('admin.accounts.users.edit', $user);
        $this->get($list)->assertOk()->assertSee('Persisted User')->assertSee($edit)->assertDontSee('Amina Bennani');
        $this->get($edit)->assertOk()->assertSee('value="Persisted User"', false)->assertSee('Save roles');

        $this->from($edit)->put(route('admin.accounts.users.update', $user), [
            'name' => 'Renamed User', 'email' => $user->email, 'password' => '', 'password_confirmation' => '',
        ])->assertRedirect($edit)->assertSessionHasNoErrors();
        $this->assertSame('Renamed User', $user->refresh()->name);
        $this->assertTrue(Hash::check('account-password', $user->password));

        $role = Role::where('key', Role::TEACHER)->sole();
        $assign = route('admin.accounts.users.roles.update', $user);
        $this->from($edit)->put($assign, ['roles_present' => '1', 'role_ids' => [$role->id]])->assertRedirect($edit);
        $this->assertSame(['teacher'], $user->roles()->pluck('key')->all());
        $this->put($assign, ['roles_present' => '1'])->assertRedirect($edit);
        $this->assertCount(0, $user->roles);
    }

    public function test_role_pages_save_permissions_and_show_validation_and_access_errors(): void
    {
        $this->actingAs($this->userWithRole(Role::ADMINISTRATOR));
        $list = route('admin.accounts.roles.index');
        $create = route('admin.accounts.roles.create');
        $this->get($list)->assertOk()->assertSee('Administrator')->assertSee('Teacher');
        $this->get($create)->assertOk()->assertSee('Enter administration');
        $this->from($create)->post(route('admin.accounts.roles.store'), ['name' => 'Teacher', 'abilities_present' => '1'])
            ->assertRedirect($create)->assertSessionHasErrors('name');
        $this->get($create)->assertSee('aria-invalid="true"', false);

        $this->post(route('admin.accounts.roles.store'), ['name' => 'Moderator', 'abilities' => ['admin.enter']])->assertRedirect($list);
        $role = Role::where('name', 'Moderator')->sole();
        $edit = route('admin.accounts.roles.edit', $role);
        $this->get($edit)->assertOk()->assertSee('Moderator');
        $this->put(route('admin.accounts.roles.update', $role), ['name' => 'Moderator', 'abilities_present' => '1'])->assertRedirect($list);
        $this->assertCount(0, $role->abilities);

        $protected = Role::where('key', Role::STUDENT)->sole();
        $this->get(route('admin.accounts.roles.edit', $protected))->assertForbidden()->assertSee('Access restricted');
    }

    private function userWithRole(string $key): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $key)->sole());

        return $user;
    }
}
