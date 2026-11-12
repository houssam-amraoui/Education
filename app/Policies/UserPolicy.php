<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Support\Ability;
use Illuminate\Database\Eloquent\Collection;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasAbility(Ability::EnterAdministration) && $actor->hasAbility(Ability::ManageUsers);
    }

    public function create(User $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function update(User $actor, User $user): bool
    {
        return $this->viewAny($actor)
            && (! $user->isAdministrator() || $actor->isAdministrator())
            && $actor->canGrantAbilities($user->abilityQuery()->pluck('ability'));
    }

    public function assignRoles(User $actor, User $user, ?Collection $roles = null): bool
    {
        if (! $actor->hasAbility(Ability::AssignRoles) || ! $this->update($actor, $user)) {
            return false;
        }

        if ($roles === null) {
            return true;
        }

        $includesAdministrator = $roles->contains('key', Role::ADMINISTRATOR);
        if ($includesAdministrator && ! $actor->isAdministrator()) {
            return false;
        }

        if ($actor->is($user) && $actor->isAdministrator() && ! $includesAdministrator) {
            return false;
        }

        return $actor->canGrantAbilities($roles->flatMap(fn (Role $role) => $role->abilities->pluck('ability')));
    }
}
