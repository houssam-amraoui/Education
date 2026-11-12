<?php

namespace App\Actions\Rewards;

use App\Models\ExamAttempt;
use App\Models\ExerciseAttempt;
use App\Models\LessonProgress;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Models\User;
use App\Queries\LearningStreak;
use App\Support\RewardEvent;

class AwardLearningRewards
{
    public function __construct(
        private LearningStreak $streaks,
        private AwardAchievements $achievements,
    ) {}

    // Called only for a new finalization, inside the transaction holding the student lock.
    public function handle(User $student, LessonProgress|ExerciseAttempt|ExamAttempt $record): void
    {
        [$event, $occurredAt] = match ($record::class) {
            LessonProgress::class => [RewardEvent::LessonCompletion, $record->completed_at],
            ExerciseAttempt::class => [RewardEvent::ExerciseResult, $record->submitted_at],
            ExamAttempt::class => [RewardEvent::ExamCompletion, $record->submitted_at],
        };
        $source = $event->value.':'.$record->getKey();
        $rules = PointRule::query()->where('enabled', true)->where('event', $event)->get();

        foreach ($rules as $rule) {
            if ($record instanceof ExerciseAttempt && $rule->minimum_score_percent !== null
                && ($record->possible_points <= 0 || $record->awarded_points * 100 < $record->possible_points * $rule->minimum_score_percent)) {
                continue;
            }

            if (! $rule->allow_retries && $this->hasPreviousSubmission($record)) {
                continue;
            }

            $this->credit($student, $rule, $source, $occurredAt);
        }

        $streak = $this->streaks->current($student, $occurredAt);
        $this->awardStreak($student, $streak, $occurredAt);
        $this->achievements->handle($student, $record, $event, $source, $occurredAt, $streak['days']);
    }

    private function awardStreak(User $student, array $streak, \DateTimeInterface $occurredAt): void
    {
        $rules = PointRule::query()->where('enabled', true)
            ->where('event', RewardEvent::StreakMilestone)->where('streak_days', $streak['days'])->get();

        foreach ($rules as $rule) {
            $this->credit($student, $rule, "streak:{$streak['started_on']}:{$streak['days']}", $occurredAt);
        }
    }

    private function hasPreviousSubmission(LessonProgress|ExerciseAttempt|ExamAttempt $record): bool
    {
        if ($record instanceof LessonProgress) {
            return false;
        }

        $target = $record instanceof ExerciseAttempt ? 'exercise_id' : 'exam_id';

        return $record->newQuery()
            ->where('student_id', $record->student_id)
            ->where($target, $record->{$target})
            ->whereKeyNot($record->getKey())
            ->whereNotNull('submitted_at')
            ->exists();
    }

    private function credit(User $student, PointRule $rule, string $source, \DateTimeInterface $occurredAt): void
    {
        PointTransaction::firstOrCreate(
            ['student_id' => $student->getKey(), 'source_key' => "rule:{$rule->id}:{$source}"],
            ['amount' => $rule->points, 'recorded_at' => $occurredAt],
        );
    }
}
