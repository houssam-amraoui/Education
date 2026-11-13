<?php

namespace App\Actions\Lessons;

use App\Actions\Analytics\RecordAnalyticsEvent;
use App\Actions\Rewards\AwardLearningRewards;
use App\Actions\Rewards\LockPointAccount;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RecordLessonProgress
{
    public function __construct(
        private LockPointAccount $lock,
        private AwardLearningRewards $rewards,
        private RecordAnalyticsEvent $analytics,
    ) {}

    public function view(User $student, Lesson $lesson): void
    {
        $key = $this->authorizedKey($student, $lesson);

        DB::transaction(function () use ($key, $student, $lesson): void {
            $viewedAt = now()->utc()->toImmutable();
            LessonProgress::upsert(
                $key + ['last_viewed_at' => $viewedAt->toDateTimeString()],
                ['student_id', 'lesson_id'],
                ['last_viewed_at'],
            );
            $this->analytics->lessonOpen($student, $lesson->id, $viewedAt);
        }, 3);
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
        abort_unless(Lesson::visibleTo($student)->whereKey($lesson->getKey())->exists(), 404);

        return [
            'student_id' => $student->getKey(),
            'lesson_id' => $lesson->getKey(),
        ];
    }
}
