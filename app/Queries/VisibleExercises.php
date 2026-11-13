<?php

namespace App\Queries;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VisibleExercises
{
    public function paginate(?int $levelId = null, int $perPage = 12, ?string $search = null, ?User $viewer = null, bool $freeOnly = false): LengthAwarePaginator
    {
        return Exercise::visibleTo($viewer)
            ->when($freeOnly, fn (Builder $query) => $query->visibleToPublic())
            ->when($levelId !== null, fn (Builder $query) => $query->whereRelation('unit', 'level_id', $levelId))
            ->when(filled($search), fn (Builder $query) => $query->whereLike('title', '%'.$search.'%'))
            ->with('unit.level')
            ->withCount('questions')
            ->orderBy('unit_id')
            ->ordered()
            ->paginate($perPage)
            ->appends(['level_id' => $levelId, 'search' => $search]);
    }

    public function findOrFail(int $exerciseId, ?User $viewer = null): Exercise
    {
        return Exercise::visibleTo($viewer)->with('unit.level')->findOrFail($exerciseId);
    }
}
