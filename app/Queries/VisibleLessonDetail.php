<?php

namespace App\Queries;

use App\Models\Category;
use App\Models\Lesson;
use App\Models\User;

class VisibleLessonDetail
{
    public function findOrFail(Category $category, int $lessonId, ?User $viewer = null): Lesson
    {
        return $category->lessons()
            ->visibleTo($viewer)
            ->with('category.unit.level')
            ->withVisibleNextLessons($viewer)
            ->findOrFail($lessonId);
    }
}
