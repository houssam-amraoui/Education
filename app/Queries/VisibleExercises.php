<?php

namespace App\Queries;

use App\Models\Exercise;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VisibleExercises
{
    public function paginate(?int $levelId = null, int $perPage = 12, ?string $search = null): LengthAwarePaginator
    {
        return Exercise::visibleToPublic()
            ->when($levelId !== null, fn (Builder $query) => $query->whereRelation('unit', 'level_id', $levelId))
            ->when(filled($search), fn (Builder $query) => $query->whereLike('title', '%'.$search.'%'))
            ->with('unit.level')
            ->withCount('questions')
            ->orderBy('unit_id')
            ->ordered()
            ->paginate($perPage)
            ->appends(['level_id' => $levelId, 'search' => $search]);
    }

    public function findOrFail(int $exerciseId): Exercise
    {
        return Exercise::visibleToPublic()->with('unit.level')->findOrFail($exerciseId);
    }
}
