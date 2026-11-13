<?php

namespace App\Policies;

use App\Models\Exercise;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExercisePolicy extends CurriculumPolicy
{
    public function attempt(User $user, Exercise $exercise): Response
    {
        if (! $user->roles()->where('key', Role::STUDENT)->exists()) {
            return Response::deny();
        }

        return $this->view($user, $exercise);
    }

    public function view(?User $user, Exercise $exercise): Response
    {
        return Exercise::visibleTo($user)->whereKey($exercise->getKey())->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
