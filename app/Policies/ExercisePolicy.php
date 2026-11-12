<?php

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExercisePolicy extends CurriculumPolicy
{
    public function view(?User $user, Exercise $exercise): Response
    {
        return Exercise::visibleToPublic()->whereKey($exercise->getKey())->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
