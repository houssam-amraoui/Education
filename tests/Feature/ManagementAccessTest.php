<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Ability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ManagementAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_entry_requires_a_persisted_role_with_the_ability(): void
    {
        $this->assertFalse(Gate::allows(Ability::EnterAdministration->value));

        $user = User::factory()->create();
        $gate = Gate::forUser($user);
        $user->roles()->attach(Role::where('key', Role::STUDENT)->sole());
        $this->assertFalse($gate->allows(Ability::EnterAdministration->value));

        $teacher = Role::where('key', Role::TEACHER)->sole();
        $user->roles()->attach($teacher);
        $this->assertTrue($gate->allows(Ability::EnterAdministration->value));
        $this->assertFalse($user->hasAbility(Ability::ManageUsers));

        $user->roles()->detach($teacher);
        $user->roles()->attach(Role::where('key', Role::ADMINISTRATOR)->sole());
        $this->assertTrue($gate->allows(Ability::EnterAdministration->value));
        $this->assertTrue($user->hasAbility(Ability::ManageUsers));
    }
}
