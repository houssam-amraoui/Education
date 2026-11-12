<?php

namespace App\Queries;

use App\Models\ExamAttempt;
use App\Models\ExerciseAttempt;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class ManagedStudentProgress
{
    public function __construct(
        private StudentProgress $progress,
        private ExerciseAttemptHistory $exercises,
        private ExamAttemptHistory $exams,
    ) {}

    public function completion(User $manager, User $student, ?int $levelId = null): array
    {
        Gate::forUser($manager)->authorize('viewStudentProgress', $student);

        return $this->progress->completion($student, $levelId);
    }

    public function history(User $manager, User $student, ?int $levelId = null, int $perPage = 20): LengthAwarePaginator
    {
        Gate::forUser($manager)->authorize('viewStudentProgress', $student);

        return $this->progress->history($student, $levelId, $perPage);
    }

    public function review(User $manager, User $student, ExerciseAttempt|ExamAttempt $attempt): array
    {
        Gate::forUser($manager)->authorize('viewStudentProgress', $student);

        return $attempt instanceof ExerciseAttempt
            ? $this->exercises->result($student, $attempt)
            : $this->exams->result($student, $attempt);
    }
}
