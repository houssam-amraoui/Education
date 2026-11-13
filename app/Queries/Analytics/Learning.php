<?php

namespace App\Queries\Analytics;

use App\Models\ExamAttempt;
use App\Models\ExerciseAttempt;
use App\Models\Role;
use App\Models\User;
use App\Queries\StudentProgress;
use App\Support\ReportingPeriod;
use Illuminate\Database\Eloquent\Model;

class Learning
{
    public function __construct(private AnalyticsActivity $activity, private StudentProgress $progress) {}

    public function forPeriod(ReportingPeriod $period): array
    {
        return [
            'exercises' => $this->assessment(new ExerciseAttempt, $period),
            'exams' => $this->assessment(new ExamAttempt, $period),
            'progress' => $this->lessonProgress($period),
            'exercise_question_difficulty' => $this->difficulty(new ExerciseAttempt, 'exercise_attempt_items', 'exercise_attempt_id', $period),
            'exam_question_difficulty' => $this->difficulty(new ExamAttempt, 'exam_attempt_items', 'exam_attempt_id', $period),
        ];
    }

    private function assessment(Model $model, ReportingPeriod $period): array
    {
        $cohort = $this->activity->during($model, 'started_at', $period);
        $starts = $cohort->count();
        $completed = $cohort->where('submitted_at', '<', $period->end)->count();
        $scores = $this->activity->during($model, 'submitted_at', $period)
            ->where('possible_points', '>', 0)->whereNotNull('awarded_points')
            ->selectRaw('COUNT(*) AS scored, AVG(100.0 * awarded_points / possible_points) AS average')->first();

        return [
            'cohort_starts' => $starts,
            'cohort_completions' => $completed,
            'completion_rate' => $this->percentage($completed, $starts),
            'submissions' => $this->activity->during($model, 'submitted_at', $period)->count(),
            'scored_attempts' => (int) $scores->scored,
            'average_score' => $scores->average === null ? null : round((float) $scores->average, 2),
        ];
    }

    private function lessonProgress(ReportingPeriod $period): array
    {
        $students = User::query()->whereIn('id', $this->activity->studentActivity($period)->select('student_id'))
            ->whereRelation('roles', 'key', Role::STUDENT);
        $completed = 0;
        $eligible = 0;

        foreach ($students->lazyById() as $student) {
            $counts = $this->progress->completion($student, before: $period->end);
            $completed += $counts['completed'];
            $eligible += $counts['total'];
        }

        return [
            'completed' => $completed,
            'eligible' => $eligible,
            'percentage' => $this->percentage($completed, $eligible),
        ];
    }

    private function difficulty(Model $attempt, string $items, string $foreignKey, ReportingPeriod $period): array
    {
        return $this->activity->during($attempt, 'submitted_at', $period)
            ->join($items, "{$items}.{$foreignKey}", '=', $attempt->qualifyColumn('id'))
            ->whereNotNull('correct')->groupBy('source_question_id')
            ->selectRaw('source_question_id, COUNT(*) AS graded, SUM(CASE WHEN correct = 0 THEN 1 ELSE 0 END) AS incorrect')
            ->orderByRaw('1.0 * SUM(CASE WHEN correct = 0 THEN 1 ELSE 0 END) / COUNT(*) DESC')
            ->orderBy('source_question_id')->toBase()->get()->map(fn ($row) => [
                'question_id' => (int) $row->source_question_id,
                'graded' => (int) $row->graded,
                'incorrect' => (int) $row->incorrect,
                'incorrect_rate' => $this->percentage((int) $row->incorrect, (int) $row->graded),
            ])->all();
    }

    private function percentage(int $numerator, int $denominator): ?float
    {
        return $denominator === 0 ? null : round(100 * $numerator / $denominator, 2);
    }
}
