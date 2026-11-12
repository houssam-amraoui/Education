<?php

namespace App\Queries;

use App\Models\Exercise;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VisibleExercises
{
    public function paginate(?int $levelId = null, int $perPage = 12): LengthAwarePaginator
    {
        return Exercise::visibleToPublic()
            ->when($levelId !== null, fn (Builder $query) => $query->whereRelation('unit', 'level_id', $levelId))
            ->with('unit.level')
            ->orderBy('unit_id')
            ->ordered()
            ->paginate($perPage)
            ->appends($levelId === null ? [] : ['level_id' => $levelId]);
    }

    public function findOrFail(int $exerciseId): Exercise
    {
        return Exercise::visibleToPublic()->with('unit.level')->findOrFail($exerciseId);
    }
}
