<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LearningResult
{
    public static function relations(string $type, ?int $levelId = null, ?User $viewer = null): array
    {
        return match ($type) {
            'lesson' => ['category:id,unit_id,title', 'category.unit:id,level_id,title', 'category.unit.level:id,title'],
            'exercise', 'exam' => ['unit:id,level_id,title', 'unit.level:id,title'],
            'resource', 'video' => [
                'levels' => fn (BelongsToMany $levels) => $levels->visibleTo($viewer)
                    ->when($levelId !== null, fn ($query) => $query->whereKey($levelId))
                    ->select('levels.id', 'levels.title')->orderBy('levels.title')->orderBy('levels.id'),
            ],
            'topic' => [],
        };
    }

    public static function from(string $type, Model $item): array
    {
        [$route, $parameters, $context] = match ($type) {
            'lesson' => [
                'curriculum.lesson',
                [$item->category->unit->level_id, $item->category->unit_id, $item->category_id, $item->id],
                [$item->category->unit->level->title, $item->category->unit->title, $item->category->title],
            ],
            'exercise', 'exam' => [
                'curriculum.'.$type.'s.instructions',
                [$item->id],
                [$item->unit->level->title, $item->unit->title],
            ],
            'resource', 'video' => [
                'library.'.$type.'s.show',
                [$item->id],
                $item->levels->pluck('title')->all(),
            ],
            'topic' => [
                'curriculum.topics.show',
                [$item->id],
                $item->description === null ? [] : [$item->description],
            ],
        };

        return [
            'title' => $item->title,
            'type' => $type,
            'access' => $item->access ?? 'public',
            'context' => $context,
            'destination' => ['route' => $route, 'parameters' => $parameters],
        ];
    }
}
