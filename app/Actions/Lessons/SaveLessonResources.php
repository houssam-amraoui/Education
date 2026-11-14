<?php

namespace App\Actions\Lessons;

use App\Models\Lesson;
use App\Models\Resource;
use Illuminate\Validation\ValidationException;

class SaveLessonResources
{
    // Call inside the transaction holding the lesson lock.
    public function handle(Lesson $lesson, array $resourceIds): void
    {
        $resources = $lesson->resources();
        $attached = $resources->pluck('resources.id')->all();
        $affected = array_merge($attached, $resourceIds);

        // Resource-side edits take the same locks before changing links.
        $locked = Resource::query()->whereKey($affected)->orderBy('id')->lockForUpdate()->pluck('id')->all();
        if (array_diff($resourceIds, $locked) !== []) {
            throw ValidationException::withMessages(['resource_ids' => 'Select existing resources.']);
        }

        $current = $resources->newPivotQuery()->whereIn('resource_id', $affected)->lockForUpdate()->pluck('resource_id')->all();
        $resources->detach(array_diff($attached, $resourceIds));
        $resources->attach(array_diff($resourceIds, $current));
    }
}
