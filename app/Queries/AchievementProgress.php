<?php

namespace App\Queries;

use App\Models\Achievement;
use App\Models\ExamAttempt;
use App\Models\ExerciseAttempt;
use App\Models\LessonProgress;
use App\Models\StudentAchievement;
use App\Models\User;
use App\Support\RewardEvent;
use Illuminate\Database\Eloquent\Builder;

class AchievementProgress
{
    public function awards(User $student): Builder
    {
        return StudentAchievement::query()->whereBelongsTo($student, 'student');
    }

    public function definitions(User $student): Builder
    {
        $awards = $this->awards($student)->select('achievement_id');

        return Achievement::query()->where(fn (Builder $query) => $query
            ->where('enabled', true)->orWhereIn('id', $awards));
    }

    public function count(User $student, Achievement $definition, int $streakDays): int
    {
        if ($definition->event === RewardEvent::StreakMilestone) {
            return $streakDays;
        }

        [$model, $timestamp, $target] = match ($definition->event) {
            RewardEvent::LessonCompletion => [LessonProgress::class, 'completed_at', 'lesson_id'],
            RewardEvent::ExerciseResult => [ExerciseAttempt::class, 'submitted_at', 'exercise_id'],
            RewardEvent::ExamCompletion => [ExamAttempt::class, 'submitted_at', 'exam_id'],
        };
        $query = $model::query()->whereBelongsTo($student, 'student')->whereNotNull($timestamp);

        if ($definition->event === RewardEvent::ExerciseResult && $definition->minimum_score_percent !== null) {
            $query->where('possible_points', '>', 0)
                ->whereRaw('awarded_points * 100 >= possible_points * ?', [$definition->minimum_score_percent]);
        }

        return $query->distinct()->count($target);
    }
}
