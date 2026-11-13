<?php

namespace App\Queries;

use App\Models\ExamAttempt;
use App\Models\Exercise;
use App\Models\ExerciseAttempt;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Gate;

class LearningRecommendations
{
    private const REVIEW_THRESHOLD = 70;

    /** @return Collection<int, Lesson> */
    public function nextLessons(User $student, ?Lesson $source = null, int $limit = 5): Collection
    {
        Gate::forUser($student)->authorize('manageLearningProfile', $student);

        $levels = $this->selectedLevels($student);
        $eligible = Lesson::visibleTo($student)
            ->whereHas('category.unit', fn (Builder $unit) => $unit->whereIn('level_id', $levels));

        $sourceId = $source !== null
            ? (clone $eligible)->whereKey($source->getKey())->value('id')
            : $this->latestActiveLesson($student, $eligible);

        $candidates = $eligible->select('lessons.*')
            ->leftJoin('lesson_progress as learner_progress', fn (JoinClause $join) => $join
                ->on('learner_progress.lesson_id', '=', 'lessons.id')
                ->where('learner_progress.student_id', $student->getKey()))
            ->whereNull('learner_progress.completed_at');

        if ($sourceId !== null) {
            $candidates->where('lessons.id', '<>', $sourceId)
                ->leftJoin('lesson_next_lesson as configured_next', fn (JoinClause $join) => $join
                    ->on('configured_next.next_lesson_id', '=', 'lessons.id')
                    ->where('configured_next.lesson_id', $sourceId))
                ->orderByRaw('CASE WHEN configured_next.next_lesson_id IS NULL THEN 1 ELSE 0 END');
        }

        return $candidates
            ->orderByRaw('CASE WHEN learner_progress.last_viewed_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('learner_progress.last_viewed_at')
            ->ordered()->with('category.unit.level')->limit($limit)->get();
    }

    private function latestActiveLesson(User $student, Builder $eligible): ?int
    {
        return LessonProgress::where('student_id', $student->getKey())
            ->whereIn('lesson_id', (clone $eligible)->select('lessons.id'))
            ->where(fn (Builder $progress) => $progress->whereNotNull('last_viewed_at')->orWhereNotNull('completed_at'))
            ->orderByRaw('CASE WHEN completed_at IS NOT NULL AND (last_viewed_at IS NULL OR completed_at > last_viewed_at) THEN completed_at ELSE last_viewed_at END DESC')
            ->orderByDesc('id')
            ->value('lesson_id');
    }

    /** @return Collection<int, Exercise> */
    public function exercises(User $student, int $limit = 5): Collection
    {
        Gate::forUser($student)->authorize('manageLearningProfile', $student);

        $latestScore = ExerciseAttempt::query()->select('id')
            ->whereColumn('exercise_id', 'exercises.id')
            ->where('student_id', $student->getKey())
            ->whereNotNull('submitted_at')->whereNotNull('awarded_points')->where('possible_points', '>', 0)
            ->orderByDesc('submitted_at')->orderByDesc('id')->limit(1);

        return Exercise::visibleTo($student)
            ->whereHas('unit', fn (Builder $unit) => $unit->whereIn('level_id', $this->selectedLevels($student)))
            ->has('questions')
            ->select('exercises.*')
            ->selectRaw("CASE WHEN latest_score.id IS NULL THEN 'pending' ELSE 'retry' END AS recommendation_reason")
            ->leftJoin('exercise_attempts as latest_score', fn (JoinClause $join) => $join
                ->where('latest_score.id', '=', $latestScore))
            ->where(fn (Builder $exercise) => $exercise->whereNull('latest_score.id')
                ->orWhereRaw('100.0 * latest_score.awarded_points < ? * latest_score.possible_points', [self::REVIEW_THRESHOLD]))
            ->orderByRaw('CASE WHEN latest_score.id IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('100.0 * latest_score.awarded_points / latest_score.possible_points')
            ->orderBy('exercises.unit_id')->ordered()->with('unit.level')->limit($limit)->get();
    }

    /** @return Collection<int, Lesson> */
    public function examReview(User $student, ExamAttempt $attempt, int $limit = 5): Collection
    {
        Gate::forUser($student)->authorize('manageLearningProfile', $student);

        $result = app(AssessmentActivity::class)->exams($student)->with('exam')->findOrFail($attempt->getKey());
        if ($result->submitted_at === null || $result->expired_at !== null || $result->awarded_points === null
            || $result->possible_points <= 0 || $result->awarded_points * 100 >= $result->possible_points * self::REVIEW_THRESHOLD) {
            return new Collection;
        }

        $links = $result->exam->prerequisiteLessons();
        $prerequisites = $links->newPivotQuery()->select('prerequisite_lesson_id');
        $configured = (clone $prerequisites)->whereColumn('prerequisite_lesson_id', 'lessons.id');

        return Lesson::visibleTo($student)
            ->select('lessons.*')
            ->where(fn (Builder $lesson) => $lesson->whereIn('lessons.id', $prerequisites)
                ->orWhereRelation('category', 'unit_id', $result->exam->unit_id))
            ->orderByDesc($configured)
            ->ordered()->with('category.unit.level')->limit($limit)->get();
    }

    private function selectedLevels(User $student): Builder
    {
        return $student->selectedLevels()->visibleTo($student)->select('levels.id')->getQuery();
    }
}
