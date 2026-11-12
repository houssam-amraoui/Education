<?php

namespace App\Queries;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StudentProgress
{
    public function __construct(private AssessmentActivity $assessments) {}

    public function completion(User $student, ?int $levelId = null, ?int $unitId = null): array
    {
        Gate::forUser($student)->authorize('manageLearningProfile', $student);

        $completed = LessonProgress::query()
            ->where('student_id', $student->getKey())
            ->whereNotNull('completed_at')
            ->select('lesson_id');
        $counts = $this->lessons($levelId)
            ->when($unitId !== null, fn (Builder $lessons) => $lessons->whereRelation('category', 'unit_id', $unitId))
            ->leftJoinSub($completed, 'completed_lessons', 'completed_lessons.lesson_id', '=', 'lessons.id')
            ->toBase()
            ->selectRaw('COUNT(*) AS total, COUNT(completed_lessons.lesson_id) AS completed')
            ->first();

        $total = (int) $counts->total;
        $completed = (int) $counts->completed;

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total === 0 ? 0 : (int) round(100 * $completed / $total),
        ];
    }

    public function history(User $student, ?int $levelId = null, int $perPage = 20, bool $scoresOnly = false): LengthAwarePaginator
    {
        Gate::forUser($student)->authorize('manageLearningProfile', $student);

        return $this->activity($student, $levelId)
            ->when($scoresOnly, fn (QueryBuilder $query) => $query->whereNotNull('awarded_points'))
            ->orderByDesc('occurred_at')
            ->orderBy('event')
            ->orderByDesc('record_id')
            ->paginate($perPage)
            ->through(function (\stdClass $event): \stdClass {
                $event->record_id = (int) $event->record_id;
                $event->content_id = (int) $event->content_id;
                $event->possible_points = $event->possible_points === null ? null : (int) $event->possible_points;
                $event->awarded_points = $event->awarded_points === null ? null : (int) $event->awarded_points;

                return $event;
            });
    }

    public function assessmentStats(User $student, ?int $levelId = null): array
    {
        Gate::forUser($student)->authorize('manageLearningProfile', $student);

        $scores = $this->activity($student, $levelId)->where('possible_points', '>', 0)
            ->whereNotNull('awarded_points')
            ->selectRaw('COUNT(*) AS attempts, AVG(100.0 * awarded_points / possible_points) AS average')
            ->first();

        return [
            'exercises' => $this->assessments->exercises($student, $levelId)->distinct()->count('exercise_id'),
            'attempts' => (int) $scores->attempts,
            'average' => $scores->average === null ? null : (int) round($scores->average),
        ];
    }

    public function weeklyActivity(User $student, ?int $levelId = null): array
    {
        Gate::forUser($student)->authorize('manageLearningProfile', $student);

        $start = now()->utc()->startOfWeek();

        return $this->activity($student, $levelId)
            ->whereBetween('occurred_at', [$start, $start->copy()->endOfWeek()])
            ->selectRaw('DATE(occurred_at) AS day, COUNT(*) AS total')->groupByRaw('DATE(occurred_at)')
            ->pluck('total', 'day')->all();
    }

    private function activity(User $student, ?int $levelId): QueryBuilder
    {
        $lessons = LessonProgress::where('student_id', $student->getKey())
            ->whereIn('lesson_id', $this->lessons($levelId)->select('lessons.id'))
            ->toBase();
        $exercises = $this->assessments->exercises($student, $levelId)->toBase();
        $exams = $this->assessments->exams($student, $levelId)->toBase();

        $events = (clone $lessons)->whereNotNull('last_viewed_at')->selectRaw(
            'id AS record_id, lesson_id AS content_id, ? AS event, last_viewed_at AS occurred_at, NULL AS possible_points, NULL AS awarded_points',
            ['lesson_viewed'],
        )->unionAll($lessons->whereNotNull('completed_at')->selectRaw(
            'id, lesson_id, ?, completed_at, NULL, NULL', ['lesson_completed'],
        ))->unionAll($exercises->selectRaw(
            'id, exercise_id, ?, submitted_at, possible_points, awarded_points', ['exercise_completed'],
        ))->unionAll((clone $exams)->selectRaw(
            'id, exam_id, ?, started_at, NULL, NULL', ['exam_started'],
        ))->unionAll((clone $exams)->whereNotNull('submitted_at')->selectRaw(
            'id, exam_id, ?, submitted_at, possible_points, awarded_points', ['exam_completed'],
        ))->unionAll($exams->whereNotNull('expired_at')->selectRaw(
            'id, exam_id, ?, expired_at, NULL, NULL', ['exam_expired'],
        ));

        return DB::query()->fromSub($events, 'activity');
    }

    private function lessons(?int $levelId): Builder
    {
        return Lesson::visibleToPublic()
            ->when($levelId !== null, fn (Builder $lessons) => $lessons->whereRelation('category.unit', 'level_id', $levelId));
    }
}
