<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Resource;
use App\Models\User;
use App\Policies\ContentAccess;

enum RewardTarget: string
{
    case Exercise = 'exercise';
    case Exam = 'exam';
    case Resource = 'resource';
    case AskTeacher = 'ask_teacher';

    public function label(): string
    {
        return match ($this) {
            self::Exercise => 'Exercises',
            self::Exam => 'Exams',
            self::Resource => 'Resources',
            self::AskTeacher => 'Ask Teacher',
        };
    }

    public function model(): ?string
    {
        return match ($this) {
            self::Exercise => Exercise::class,
            self::Exam => Exam::class,
            self::Resource => Resource::class,
            self::AskTeacher => null,
        };
    }

    public function canUnlock(User $student, int $id): bool
    {
        return match ($this) {
            self::Exercise => Exercise::visibleTo($student, unlocking: true)->whereKey($id)->exists(),
            self::Exam => Exam::visibleTo($student, unlocking: true)->whereKey($id)->exists(),
            self::Resource => Resource::visibleTo($student, unlocking: true)->whereKey($id)
                ->pointsClassified()->exists(),
            self::AskTeacher => $id === 0 && app(ContentAccess::class)->allowsAskTeacher($student, unlocking: true),
        };
    }
}
