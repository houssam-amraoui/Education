<?php

namespace App\Queries;

use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class ContinueLearning
{
    /** @param list<int> $selectedLevelIds */
    public function resume(User $student, array $selectedLevelIds): ?LessonProgress
    {
        return $this->recentViews($student, $selectedLevelIds)->whereNull('completed_at')->first();
    }

    /** @param list<int> $selectedLevelIds */
    public function recentLessons(User $student, array $selectedLevelIds, int $limit = 5): Collection
    {
        return $this->recentViews($student, $selectedLevelIds)->limit($limit)->get();
    }

    private function recentViews(User $student, array $selectedLevelIds): Builder
    {
        Gate::forUser($student)->authorize('manageLearningProfile', $student);

        return LessonProgress::where('student_id', $student->getKey())
            ->whereNotNull('last_viewed_at')
            ->whereHas('lesson', fn (Builder $lesson) => $lesson->visibleToPublic()
                ->whereHas('category.unit', fn (Builder $unit) => $unit->whereIn('level_id', $selectedLevelIds)))
            ->with('lesson.category.unit.level')
            ->orderByDesc('last_viewed_at')
            ->orderByDesc('id');
    }
}
