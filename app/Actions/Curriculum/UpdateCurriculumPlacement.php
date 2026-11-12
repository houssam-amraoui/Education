<?php

namespace App\Actions\Curriculum;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class UpdateCurriculumPlacement
{
    public function handle(Unit|Category|Exercise $item, array $attributes): Unit|Category|Exercise
    {
        return DB::transaction(function () use ($item, $attributes): Unit|Category|Exercise {
            $current = $item->newQuery()->lockForUpdate()->findOrFail($item->getKey());
            $current->fill($attributes);

            if ($current->isDirty(['level_id', 'unit_id'])) {
                $current->position = null;
            }

            $current->save();

            return $current;
        }, 3);
    }
}
