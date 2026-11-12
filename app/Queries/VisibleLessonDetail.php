<?php

namespace App\Queries;

use App\Models\Category;
use App\Models\Lesson;

class VisibleLessonDetail
{
    public function findOrFail(Category $category, int $lessonId): Lesson
    {
        return $category->lessons()
            ->visibleToPublic()
            ->with('category.unit.level')
            ->withPublicNextLessons()
            ->findOrFail($lessonId);
    }
}
