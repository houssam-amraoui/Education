<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Support\Ability;

class CurriculumPolicy
{
    public function manage(User $actor): bool
    {
        return $actor->hasAbility(Ability::EnterAdministration)
            && $actor->roles()->whereIn('key', [Role::TEACHER, Role::ADMINISTRATOR])->exists();
    }
}
