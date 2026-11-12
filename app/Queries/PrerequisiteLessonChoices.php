<?php

namespace App\Queries;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Support\CurriculumStatus;

class PrerequisiteLessonChoices
{
    public function forContent(Lesson|Exercise|Exam $content): array
    {
        $selectedPrerequisites = $content->prerequisiteLessons()->pluck('lessons.id')->all();
        $prerequisiteCandidates = Lesson::with('category.unit.level')
            ->when($content instanceof Lesson, fn ($query) => $query->whereKeyNot($content->getKey()))
            ->where(fn ($query) => $query->where('status', '!=', CurriculumStatus::Archived)
                ->orWhereIn('id', $selectedPrerequisites))
            ->orderBy('category_id')->ordered()->get();

        return compact('prerequisiteCandidates', 'selectedPrerequisites');
    }
}
