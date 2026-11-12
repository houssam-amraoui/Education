<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasCurriculumOrder
{
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $position = $this->qualifyColumn('position');

        // Unplaced records follow the explicit order in creation order.
        $query->orderByRaw("CASE WHEN {$position} IS NULL THEN 1 ELSE 0 END")
            ->orderBy($position)
            ->orderBy($this->getQualifiedKeyName());
    }
}
