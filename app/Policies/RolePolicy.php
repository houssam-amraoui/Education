<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Support\Ability;

class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasAbility(Ability::EnterAdministration)
            && ($actor->hasAbility(Ability::ManageRoles) || $actor->hasAbility(Ability::AssignRoles));
    }

    public function create(User $actor): bool
    {
        return $actor->hasAbility(Ability::EnterAdministration) && $actor->hasAbility(Ability::ManageRoles);
    }

    public function update(User $actor, Role $role): bool
    {
        return $this->create($actor)
            && ! in_array($role->key, [Role::STUDENT, Role::ADMINISTRATOR], true)
            && $actor->canGrantAbilities($role->abilities()->pluck('ability'));
    }

    public function defineAbilities(User $actor, array $abilities): bool
    {
        return $this->create($actor) && $actor->canGrantAbilities($abilities);
    }
}
