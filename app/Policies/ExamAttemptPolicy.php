<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class ExamAttemptPolicy
{
    public function access(User $user, ExamAttempt $attempt): Response
    {
        if ($attempt->student_id !== $user->getKey()) {
            return Response::denyAsNotFound();
        }

        return Gate::forUser($user)->inspect('attempt', $attempt->exam);
    }
}
