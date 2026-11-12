<?php

namespace App\Actions\Lessons;

use App\Actions\Rewards\AwardLearningRewards;
use App\Actions\Rewards\LockPointAccount;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RecordLessonProgress
{
    public function __construct(private LockPointAccount $lock, private AwardLearningRewards $rewards) {}

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
        DB::transaction(function () use ($student, $lesson): void {
            $this->lock->handle($student);
            $key = $this->authorizedKey($student, $lesson);
            LessonProgress::upsert($key, ['student_id', 'lesson_id'], ['lesson_id']);
            $completed = LessonProgress::where($key)->whereNull('completed_at')
                ->update(['completed_at' => now()->utc()->toDateTimeString()]);

            if ($completed) {
                $this->rewards->handle($student, LessonProgress::where($key)->firstOrFail());
            }
        }, 3);
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
