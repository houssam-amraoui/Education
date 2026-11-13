<?php

namespace App\Queries\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Conversation;
use App\Models\ExamAttempt;
use App\Models\Message;
use App\Models\ExerciseAttempt;
use App\Models\LessonProgress;
use App\Support\ReportingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class AnalyticsActivity
{
    public function during(Model $model, string $column, ReportingPeriod $period): Builder
    {
        $column = $model->qualifyColumn($column);

        return $model->newQuery()->where($column, '>=', $period->start)->where($column, '<', $period->end);
    }

    public function events(string $event, ReportingPeriod $period): Builder
    {
        return $this->during(new AnalyticsEvent, 'occurred_at', $period)->where('event', $event);
    }

    public function dailyActiveUsers(ReportingPeriod $period): array
    {
        return $this->studentActivity($period)->selectRaw('DATE(occurred_at) AS day, COUNT(DISTINCT student_id) AS total')
            ->groupByRaw('DATE(occurred_at)')->orderBy('day')->pluck('total', 'day')->map(fn ($total) => (int) $total)->all();
    }

    public function conversations(ReportingPeriod $period): Builder
    {
        return Conversation::query()->where(fn (Builder $query) => $query
            ->whereIn('id', $this->during(new Conversation, 'created_at', $period)->select('id'))
            ->orWhereIn('id', $this->during(new Message, 'created_at', $period)->select('conversation_id')));
    }

    public function studentActivity(ReportingPeriod $period): QueryBuilder
    {
        $events = $this->during(new AnalyticsEvent, 'occurred_at', $period)
            ->whereNotNull('student_id')->whereIn('event', [
                AnalyticsEvent::LESSON_OPEN, AnalyticsEvent::RESOURCE_OPEN, AnalyticsEvent::VIDEO_PLAYBACK,
            ])->select('student_id', 'occurred_at')->toBase();

        foreach ([
            [new LessonProgress, 'last_viewed_at'],
            [new LessonProgress, 'completed_at'],
            [new ExerciseAttempt, 'started_at'],
            [new ExerciseAttempt, 'submitted_at'],
            [new ExamAttempt, 'started_at'],
            [new ExamAttempt, 'submitted_at'],
        ] as [$model, $column]) {
            $events->unionAll($this->during($model, $column, $period)
                ->selectRaw("student_id, {$column} AS occurred_at")->toBase());
        }

        return DB::query()->fromSub($events, 'student_activity');
    }
}
