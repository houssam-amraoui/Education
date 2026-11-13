<?php

namespace App\Queries\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\ExamAttempt;
use App\Models\ExerciseAttempt;
use App\Models\LessonProgress;
use App\Queries\LearningStreak;
use App\Support\ReportingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Engagement
{
    public function __construct(private AnalyticsActivity $activity, private LearningStreak $streaks) {}

    public function forPeriod(ReportingPeriod $period): array
    {
        $days = $this->streaks->days($period->end, from: $period->start);
        $active = $this->activity->studentActivity($period);

        return [
            'resource_opens' => $this->activity->events(AnalyticsEvent::RESOURCE_OPEN, $period)->count(),
            'video_playbacks' => $this->activity->events(AnalyticsEvent::VIDEO_PLAYBACK, $period)->count(),
            'lesson_opens' => $this->lessonOpens($period),
            'lesson_completions' => $this->activity->during(new LessonProgress, 'completed_at', $period)->count(),
            'exercise_starts' => $this->activity->during(new ExerciseAttempt, 'started_at', $period)->count(),
            'exercise_completions' => $this->activity->during(new ExerciseAttempt, 'submitted_at', $period)->count(),
            'exercise_repeat_attempts' => $this->repeatExercises($period),
            'exam_attempts' => $this->activity->during(new ExamAttempt, 'started_at', $period)->count(),
            'exam_completions' => $this->activity->during(new ExamAttempt, 'submitted_at', $period)->count(),
            'learning_days' => DB::query()->fromSub($days, 'days')->count(),
            'students_with_learning_days' => DB::query()->fromSub($days, 'days')->distinct()->count('student_id'),
            'students_with_consecutive_learning_days' => $this->streakStudents($days),
            'daily_active_users' => $this->activity->dailyActiveUsers($period),
            'monthly_active_users' => $active->selectRaw('SUBSTR(occurred_at, 1, 7) AS month, COUNT(DISTINCT student_id) AS total')
                ->groupByRaw('SUBSTR(occurred_at, 1, 7)')->orderBy('month')->pluck('total', 'month')->map(fn ($total) => (int) $total)->all(),
        ];
    }

    private function lessonOpens(ReportingPeriod $period): int
    {
        $recorded = $this->activity->events(AnalyticsEvent::LESSON_OPEN, $period)->count();
        $legacy = $this->activity->during(new LessonProgress, 'last_viewed_at', $period)
            ->whereNotExists(AnalyticsEvent::query()->selectRaw('1')->where('event', AnalyticsEvent::LESSON_OPEN)
                ->whereColumn('student_id', 'lesson_progress.student_id')
                ->whereColumn('target_id', 'lesson_progress.lesson_id')
                ->whereRaw('event_day = DATE(lesson_progress.last_viewed_at)'))
            ->count();

        return $recorded + $legacy;
    }

    private function repeatExercises(ReportingPeriod $period): int
    {
        return $this->activity->during(new ExerciseAttempt, 'started_at', $period)
            ->whereExists(ExerciseAttempt::query()->from('exercise_attempts as earlier')->selectRaw('1')
                ->whereColumn('earlier.student_id', 'exercise_attempts.student_id')
                ->whereColumn('earlier.exercise_id', 'exercise_attempts.exercise_id')
                ->whereColumn('earlier.id', '<', 'exercise_attempts.id'))
            ->count();
    }

    private function streakStudents(Builder $days): int
    {
        return DB::query()->fromSub($days, 'current_day')
            ->joinSub($days, 'prior_day', fn ($join) => $join->on('prior_day.student_id', 'current_day.student_id')
                ->whereRaw('prior_day.learning_day = DATE_SUB(current_day.learning_day, INTERVAL 1 DAY)'))
            ->distinct()->count('current_day.student_id');
    }
}
