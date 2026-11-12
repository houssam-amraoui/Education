<?php

namespace App\Queries;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class PrerequisiteCompletion
{
    /** Preparation for a content page that has already authorized the viewer. */
    public function guidance(?User $viewer, Lesson|Exercise|Exam $content): Collection
    {
        if ($viewer && Gate::forUser($viewer)->allows('manageLearningProfile', $viewer)) {
            return $this->forContent($viewer, $content)['lessons']->load('category.unit.level');
        }

        return $content->prerequisiteLessons()->visibleToPublic()->ordered()->with('category.unit.level')->get();
    }

    /** @return array{lessons: Collection<int, Lesson>, required: bool, allows_access: bool} */
    public function forContent(User $student, Lesson|Exercise|Exam $content, bool $required = false): array
    {
        Gate::forUser($student)->authorize('manageLearningProfile', $student);

        $visibleContent = $content instanceof Exam ? Exam::visibleTo($student) : $content->newQuery()->visibleToPublic();
        abort_unless($visibleContent->whereKey($content->getKey())->exists(), 404);

        $links = $content->prerequisiteLessons();
        $visibility = Lesson::visibleToPublic()
            ->whereColumn('lessons.id', $links->getQualifiedRelatedPivotKeyName())
            ->selectRaw('1');

        $lessons = $links->select('lessons.*')
            ->addSelect(['visible_prerequisite' => $visibility])
            ->withExists(['progress as completed' => fn (Builder $progress) => $progress
                ->where('student_id', $student->getKey())->whereNotNull('completed_at')])
            ->ordered()
            ->get();

        $visible = $lessons->filter(fn (Lesson $lesson) => (bool) $lesson->visible_prerequisite)->values();
        $allCompleted = $lessons->isNotEmpty()
            && $visible->count() === $lessons->count()
            && $visible->every(fn (Lesson $lesson) => $lesson->completed);

        return [
            'lessons' => $visible,
            'required' => $required,
            'allows_access' => ! $required || $allCompleted,
        ];
    }
}
