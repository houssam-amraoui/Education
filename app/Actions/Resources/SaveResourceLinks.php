<?php

namespace App\Actions\Resources;

use App\Models\Resource;
use Illuminate\Support\Facades\DB;

class SaveResourceLinks
{
    public function handle(Resource $resource, array $levelIds, array $lessonIds): void
    {
        DB::transaction(function () use ($resource, $levelIds, $lessonIds): void {
            $current = Resource::query()->lockForUpdate()->findOrFail($resource->getKey());
            $current->levels()->sync($levelIds);
            $current->lessons()->sync($lessonIds);
        }, 3);
    }
}
