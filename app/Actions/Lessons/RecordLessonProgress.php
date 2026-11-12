<?php

namespace App\Actions\Lessons;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RecordLessonProgress
{
    public function view(User $student, Lesson $lesson): void
    {
        $key = $this->authorizedKey($student, $lesson);

        LessonProgress::upsert(
            $key + ['last_viewed_at' => now()->utc()->toDateTimeString()],
            ['student_id', 'lesson_id'],
            ['last_viewed_at'],
        );
    }

    public function complete(User $student, Lesson $lesson): void
    {
        $key = $this->authorizedKey($student, $lesson);
        $time = now()->utc()->toDateTimeString();
        DB::transaction(function () use ($key, $time): void {
            // Lock or insert the unique row without overwriting any existing timestamps.
            LessonProgress::upsert($key + ['completed_at' => $time], ['student_id', 'lesson_id'], ['lesson_id']);
            LessonProgress::where($key)->whereNull('completed_at')->update(['completed_at' => $time]);
        });
    }

    private function authorizedKey(User $student, Lesson $lesson): array
    {
        Gate::forUser($student)->authorize('manageLearningProfile', $student);
        abort_unless(Lesson::visibleToPublic()->whereKey($lesson->getKey())->exists(), 404);

        return [
            'student_id' => $student->getKey(),
            'lesson_id' => $lesson->getKey(),
        ];
    }
}
