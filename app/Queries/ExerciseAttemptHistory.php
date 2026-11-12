<?php

namespace App\Queries;

use App\Models\Exercise;
use App\Models\ExerciseAttempt;
use App\Models\ExerciseAttemptItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class ExerciseAttemptHistory
{
    public function paginate(User $student, Exercise $exercise): LengthAwarePaginator
    {
        Gate::forUser($student)->authorize('attempt', $exercise);

        return $exercise->attempts()
            ->where('student_id', $student->getKey())
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (ExerciseAttempt $attempt): array => $attempt->summary());
    }

    public function result(User $student, ExerciseAttempt $attempt): array
    {
        Gate::forUser($student)->authorize('access', $attempt);
        abort_if($attempt->submitted_at === null, 404);

        return $attempt->summary() + [
            'questions' => $attempt->items()->get()->map(fn (ExerciseAttemptItem $item): array => $item->learnerQuestion() + [
                'submitted_answer' => $item->submitted_answer,
                'correct_answer' => $item->answer_key,
                'explanation' => $item->explanation,
                'correct' => $item->correct,
                'awarded_points' => $item->awarded_points,
            ]),
        ];
    }
}
