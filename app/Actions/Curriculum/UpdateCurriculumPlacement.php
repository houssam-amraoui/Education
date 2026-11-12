<?php

namespace App\Actions\Curriculum;

use App\Models\Category;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class UpdateCurriculumPlacement
{
    public function handle(Unit|Category $item, array $attributes): Unit|Category
    {
        return DB::transaction(function () use ($item, $attributes): Unit|Category {
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
