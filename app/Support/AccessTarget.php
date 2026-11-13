<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\Level;
use Illuminate\Database\Eloquent\Model;

class AccessTarget
{
    public const FEATURE = 'ask_teacher';

    public const MODELS = ['level' => Level::class] + Conversation::SOURCE_TYPES;

    public const REQUIREMENTS = ['public', 'registered', 'level', 'prerequisite', 'points', 'premium'];

    public static function destination(string $type, ?Model $record = null): string
    {
        if ($type === self::FEATURE) {
            return route('account.messages.create');
        }

        if ($type === 'level') {
            return route('curriculum.level', $record);
        }

        return match ($type) {
            'lesson' => route('curriculum.lesson', [$record->category->unit->level_id, $record->category->unit_id, $record->category_id, $record]),
            'exercise', 'exam' => route("curriculum.{$type}s.instructions", $record),
            'resource', 'video' => route("library.{$type}s.show", $record),
        };
    }

    public static function catalogue(string $type): string
    {
        return route(match ($type) {
            'level', 'lesson' => 'curriculum.paths',
            'exercise', 'exam' => "curriculum.{$type}s.index",
            'resource', 'video' => "library.{$type}s.index",
            self::FEATURE => 'account.messages.index',
        });
    }
}
