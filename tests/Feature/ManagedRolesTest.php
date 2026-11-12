<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Ability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagedRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_define_update_and_assign_a_role(): void
    {
        $administrator = $this->userWithRole($this->role(Role::ADMINISTRATOR));
        $student = $this->userWithRole($this->role(Role::STUDENT));
        $store = route('admin.accounts.roles.store');
        $this->postJson($store, [])->assertUnauthorized();
        $this->actingAs($student)->postJson($store, [])->assertForbidden();
        $this->actingAs($administrator)
            ->getJson(route('admin.accounts.roles.index'))->assertOk()->assertJsonCount(3, 'data');
        $this->postJson($store, ['name' => 'Support', 'abilities' => ['unknown']])
            ->assertUnprocessable()->assertJsonValidationErrors('abilities.0');

        $id = $this->postJson($store, [
            'name' => 'Support',
            'key' => Role::ADMINISTRATOR,
            'abilities' => [Ability::EnterAdministration->value],
        ])->assertCreated()->assertJsonPath('key', null)->json('id');
        $assign = route('admin.accounts.users.roles.update', $student);
        $this->putJson($assign, ['role_ids' => [$id, $id]])
            ->assertUnprocessable()->assertJsonValidationErrors('role_ids.0');
        $this->putJson($assign, ['role_ids' => [$id + 1000]])
            ->assertUnprocessable()->assertJsonValidationErrors('role_ids.0');
        $this->putJson($assign, ['role_ids' => [$id]])->assertNoContent();
        $this->putJson($assign, ['role_ids' => [$id]])->assertNoContent();
        $this->assertSame([$id], $student->roles()->pluck('roles.id')->all());
        $this->assertTrue($student->hasAbility(Ability::EnterAdministration));

        $this->putJson(route('admin.accounts.roles.update', $id), [
            'name' => 'Support',
            'abilities' => [],
        ])->assertOk()->assertJsonPath('abilities', []);
        $this->assertFalse($student->hasAbility(Ability::EnterAdministration));
    }

    public function test_delegated_manager_cannot_grant_or_control_privileges_they_lack(): void
    {
        $administratorRole = $this->role(Role::ADMINISTRATOR);
        $administrator = $this->userWithRole($administratorRole);
        $student = $this->userWithRole($this->role(Role::STUDENT));
        $role = Role::create(['name' => 'User manager']);
        foreach ([Ability::EnterAdministration, Ability::ManageUsers, Ability::AssignRoles] as $ability) {
            $role->abilities()->create(['ability' => $ability->value]);
        }
        $manager = $this->userWithRole($role);
        $this->actingAs($manager);

        $this->putJson(route('admin.accounts.users.update', $administrator), [
            'name' => 'Changed',
            'email' => 'changed@example.test',
        ])->assertForbidden();
        $this->putJson(route('admin.accounts.users.roles.update', $student), [
            'role_ids' => [$administratorRole->id],
        ])->assertForbidden();
        $this->postJson(route('admin.accounts.roles.store'), [
            'name' => 'Delegated',
            'abilities' => [],
        ])->assertForbidden();

        $editorRole = Role::create(['name' => 'Role editor']);
        foreach ([Ability::EnterAdministration, Ability::ManageRoles] as $ability) {
            $editorRole->abilities()->create(['ability' => $ability->value]);
        }
        $this->actingAs($this->userWithRole($editorRole))
            ->postJson(route('admin.accounts.roles.store'), [
                'name' => 'Excess authority',
                'abilities' => [Ability::AssignRoles->value],
            ])->assertForbidden();
        $this->putJson(route('admin.accounts.roles.update', $role), [
            'name' => 'Replaced',
            'abilities' => [],
        ])->assertForbidden();
        $this->postJson(route('admin.accounts.roles.store'), [
            'name' => 'Permitted',
            'abilities' => [Ability::EnterAdministration->value],
        ])->assertCreated();
        $this->assertSame(['student'], $student->roles()->pluck('key')->all());
    }

    public function test_protected_role_definitions_and_own_administrator_membership_cannot_be_changed(): void
    {
        $administrator = $this->userWithRole($this->role(Role::ADMINISTRATOR));
        $this->actingAs($administrator);

        foreach ([Role::STUDENT, Role::ADMINISTRATOR] as $key) {
            $this->putJson(route('admin.accounts.roles.update', $this->role($key)), [
                'name' => 'Changed',
                'abilities' => [Ability::EnterAdministration->value],
            ])->assertForbidden();
        }

        $this->putJson(route('admin.accounts.users.roles.update', $administrator), [
            'role_ids' => [],
        ])->assertForbidden();
        $this->assertTrue($administrator->isAdministrator());
    }

    private function role(string $key): Role
    {
        return Role::where('key', $key)->sole();
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
