<?php

namespace App\Actions\Lessons;

use App\Models\Category;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;

class SaveLesson
{
    public function __construct(private SaveLessonResources $resources) {}

    public function handle(Category $category, Lesson $lesson, array $attributes, ?array $videoIds, ?array $resourceIds = null): Lesson
    {
        return DB::transaction(function () use ($category, $lesson, $attributes, $videoIds, $resourceIds): Lesson {
            if ($lesson->exists) {
                $lesson = $category->lessons()->lockForUpdate()->findOrFail($lesson->id);
                $lesson->update($attributes);
            } else {
                $lesson = $category->lessons()->create($attributes);
            }

            if ($videoIds !== null) {
                $lesson->videos()->sync($videoIds);
            }

            if ($resourceIds !== null) {
                $this->resources->handle($lesson, $resourceIds);
            }

            return $lesson;
        }, 3);
    }
}
