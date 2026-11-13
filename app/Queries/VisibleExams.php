<?php

namespace App\Queries;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VisibleExams
{
    public function paginate(?User $user = null, ?int $levelId = null, int $perPage = 12, ?string $search = null, ?string $type = null, ?array $selectedLevelIds = null): LengthAwarePaginator
    {
        return Exam::visibleTo($user)
            ->when($levelId !== null, fn (Builder $query) => $query->whereRelation('unit', 'level_id', $levelId))
            ->when($selectedLevelIds !== null, fn (Builder $query) => $query->whereHas('unit',
                fn (Builder $units) => $units->whereIn('level_id', $selectedLevelIds)))
            ->when(filled($search), fn (Builder $query) => $query->whereLike('title', '%'.$search.'%'))
            ->when($type !== null, fn (Builder $query) => $query->where('type', $type))
            ->with('unit.level')
            ->orderBy('unit_id')
            ->ordered()
            ->paginate($perPage)
            ->appends($levelId === null ? [] : ['level_id' => $levelId]);
    }

    public function findOrFail(int $examId, ?User $user = null): Exam
    {
        return Exam::visibleTo($user)->with('unit.level')->findOrFail($examId);
    }
}
