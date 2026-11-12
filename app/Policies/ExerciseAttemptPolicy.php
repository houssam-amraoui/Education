<?php

namespace App\Policies;

use App\Models\ExerciseAttempt;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class ExerciseAttemptPolicy
{
    public function access(User $user, ExerciseAttempt $attempt): Response
    {
        if ($attempt->student_id !== $user->getKey()) {
            return Response::denyAsNotFound();
        }

        return Gate::forUser($user)->inspect('attempt', $attempt->exercise);
    }
}
