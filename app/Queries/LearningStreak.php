<?php

namespace App\Queries;

use App\Models\ExamAttempt;
use App\Models\ExerciseAttempt;
use App\Models\LessonProgress;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LearningStreak
{
    /** Learning days start at 00:00 UTC and come from completions and submitted attempts. */
    public function current(User $student, ?CarbonImmutable $at = null): array
    {
        $at = ($at ?? now()->toImmutable())->utc();
        $days = $this->days($at, $student);

        $expected = $at->startOfDay();
        $length = 0;
        $start = null;

        foreach (DB::query()->fromSub($days, 'days')->orderByDesc('learning_day')->cursor() as $day) {
            if ($length === 0 && $day->learning_day === $expected->subDay()->toDateString()) {
                $expected = $expected->subDay();
            }

            if ($day->learning_day !== $expected->toDateString()) {
                break;
            }

            $start = $day->learning_day;
            $length++;
            $expected = $expected->subDay();
        }

        return ['days' => $length, 'started_on' => $start];
    }

    public function days(CarbonImmutable $at, ?User $student = null, ?CarbonImmutable $from = null): Builder
    {
        $learningDays = fn (Builder $query, string $column) => $query
            ->when($student !== null, fn (Builder $query) => $query->whereBelongsTo($student, 'student'))
            ->where($column, $from === null ? '<=' : '<', $at->utc())
            ->when($from !== null, fn (Builder $query) => $query->where($column, '>=', $from->utc()))
            ->selectRaw("student_id, DATE({$column}) AS learning_day");

        return $learningDays(LessonProgress::query(), 'completed_at')
            ->union($learningDays(ExerciseAttempt::query(), 'submitted_at'))
            ->union($learningDays(ExamAttempt::query(), 'submitted_at'));
    }
}
