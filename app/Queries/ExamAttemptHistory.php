<?php

namespace App\Queries;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ExamAttemptHistory
{
    public function paginate(User $student, Exam $exam): LengthAwarePaginator
    {
        Gate::forUser($student)->authorize('attempt', $exam);

        return $exam->attempts()
            ->where('student_id', $student->getKey())
            ->where(fn (Builder $attempts) => $attempts->whereNotNull('submitted_at')->orWhereNotNull('expired_at'))
            ->orderByRaw('COALESCE(submitted_at, expired_at) DESC')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (ExamAttempt $attempt): array => $attempt->summary());
    }

    public function result(User $student, ExamAttempt $attempt): array
    {
        Gate::forUser($student)->authorize('access', $attempt);
        abort_if($attempt->submitted_at === null && $attempt->expired_at === null, 404);

        return $attempt->summary() + [
            'questions' => $attempt->submitted_at === null ? [] : $attempt->items()->get()
                ->map(fn (ExamAttemptItem $item): array => $item->learnerQuestion() + [
                    'submitted_answer' => $item->submitted_answer,
                    'correct_answer' => $item->answer_key,
                    'explanation' => $item->explanation,
                    'correct' => $item->correct,
                    'awarded_points' => $item->awarded_points,
                ]),
        ];
    }
}
