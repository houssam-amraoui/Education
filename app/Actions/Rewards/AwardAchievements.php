<?php

namespace App\Actions\Rewards;

use App\Models\Achievement;
use App\Models\ExamAttempt;
use App\Models\ExerciseAttempt;
use App\Models\LessonProgress;
use App\Models\PointTransaction;
use App\Models\StudentAchievement;
use App\Models\User;
use App\Queries\AchievementProgress;
use App\Support\RewardEvent;
use Carbon\CarbonImmutable;

class AwardAchievements
{
    public function __construct(private AchievementProgress $progress) {}

    public function handle(
        User $student,
        LessonProgress|ExerciseAttempt|ExamAttempt $record,
        RewardEvent $event,
        string $source,
        CarbonImmutable $occurredAt,
        int $streakDays,
    ): void {
        $definitions = Achievement::query()->where('enabled', true)
            ->whereIn('event', [$event, RewardEvent::StreakMilestone])
            ->whereNotIn('id', StudentAchievement::query()->whereBelongsTo($student, 'student')->select('achievement_id'))
            ->get();

        foreach ($definitions as $definition) {
            if ($this->qualifies($record, $definition)
                && $this->progress->count($student, $definition, $streakDays) >= $definition->required_count) {
                $award = StudentAchievement::firstOrCreate(
                    ['student_id' => $student->getKey(), 'achievement_id' => $definition->getKey()],
                    ['source_key' => $source, 'awarded_at' => $occurredAt],
                );

                if ($award->wasRecentlyCreated && $definition->reward_points > 0) {
                    PointTransaction::create([
                        'student_id' => $student->getKey(),
                        'source_key' => "achievement:{$definition->id}",
                        'amount' => $definition->reward_points,
                        'recorded_at' => $occurredAt,
                    ]);
                }
            }
        }
    }

    private function qualifies(LessonProgress|ExerciseAttempt|ExamAttempt $record, Achievement $definition): bool
    {
        return $definition->event !== RewardEvent::ExerciseResult
            || $definition->minimum_score_percent === null
            || ($record->possible_points > 0
                && $record->awarded_points * 100 >= $record->possible_points * $definition->minimum_score_percent);
    }
}
