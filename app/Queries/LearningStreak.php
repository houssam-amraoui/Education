<?php

namespace App\Queries;

use App\Models\ExamAttempt;
use App\Models\ExerciseAttempt;
use App\Models\LessonProgress;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class LearningStreak
{
    /** Learning days start at 00:00 UTC and come from completions and submitted attempts. */
    public function current(User $student, ?CarbonImmutable $at = null): array
    {
        $at = ($at ?? now()->toImmutable())->utc();
        $days = LessonProgress::query()->whereBelongsTo($student, 'student')
            ->where('completed_at', '<=', $at)->selectRaw('DATE(completed_at) AS learning_day')
            ->union(ExerciseAttempt::query()->whereBelongsTo($student, 'student')
                ->where('submitted_at', '<=', $at)->selectRaw('DATE(submitted_at) AS learning_day'))
            ->union(ExamAttempt::query()->whereBelongsTo($student, 'student')
                ->where('submitted_at', '<=', $at)->selectRaw('DATE(submitted_at) AS learning_day'));

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
}
