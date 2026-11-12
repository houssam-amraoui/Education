<?php

namespace App\Support;

enum RewardEvent: string
{
    case LessonCompletion = 'lesson_completion';
    case ExerciseResult = 'exercise_result';
    case ExamCompletion = 'exam_completion';
    case StreakMilestone = 'streak_milestone';

    public function label(): string
    {
        return match ($this) {
            self::LessonCompletion => 'Complete a lesson',
            self::ExerciseResult => 'Complete an exercise',
            self::ExamCompletion => 'Complete an exam',
            self::StreakMilestone => 'Maintain a streak',
        };
    }

    public function criterion(): string
    {
        return match ($this) {
            self::LessonCompletion => 'completed lessons',
            self::ExerciseResult => 'completed exercises',
            self::ExamCompletion => 'completed exams',
            self::StreakMilestone => 'consecutive learning days',
        };
    }

    public function learningScreen(): string
    {
        return match ($this) {
            self::ExerciseResult => 'G11',
            self::ExamCompletion => 'G13',
            default => 'G04',
        };
    }
}
