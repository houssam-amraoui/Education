<?php

namespace App\Queries;

use App\Models\Topic;
use App\Models\User;
use App\Support\LearningResult;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VisibleTopics
{
    public function paginate(?User $user = null, ?int $levelId = null, int $perPage = 12, ?string $search = null): LengthAwarePaginator
    {
        $counts = [];
        foreach (array_keys(Topic::CONTENT) as $type) {
            $counts[$type.'s'] = $this->visibleContent($type, $user, $levelId);
        }

        return $this->query($user, $levelId)
            ->withCount($counts)
            ->when($search !== null, fn (Builder $topics) => $topics->whereLike('title', '%'.$search.'%'))
            ->orderBy('title')->orderBy('id')
            ->paginate($perPage)
            ->appends($levelId === null ? [] : ['level_id' => $levelId]);
    }

    public function findOrFail(int $topicId, ?User $user = null, ?int $levelId = null): Topic
    {
        $relations = [];
        foreach (array_keys(Topic::CONTENT) as $type) {
            $visible = $this->visibleContent($type, $user, $levelId);
            $relations[$type.'s'] = function (BelongsToMany $content) use ($visible, $type, $levelId): void {
                $visible($content);
                $content->with(LearningResult::relations($type, $levelId));
                $content->orderBy($content->qualifyColumn('title'))->orderBy($content->getRelated()->getQualifiedKeyName());
            };
        }

        return $this->query($user, $levelId)->with($relations)->findOrFail($topicId);
    }

    public function query(?User $user = null, ?int $levelId = null): Builder
    {
        return Topic::published()->where(function (Builder $topics) use ($user, $levelId): void {
            foreach (array_keys(Topic::CONTENT) as $type) {
                $topics->orWhereHas($type.'s', $this->visibleContent($type, $user, $levelId));
            }
        });
    }

    private function visibleContent(string $type, ?User $user, ?int $levelId): Closure
    {
        return function (Builder|BelongsToMany $content) use ($type, $user, $levelId): void {
            match ($type) {
                'lesson', 'exercise' => $content->visibleToPublic(),
                'exam', 'resource', 'video' => $content->visibleTo($user),
            };

            if ($levelId !== null) {
                match ($type) {
                    'lesson' => $content->whereRelation('category.unit', 'level_id', $levelId),
                    'exercise', 'exam' => $content->whereRelation('unit', 'level_id', $levelId),
                    'resource', 'video' => $content->whereHas(
                        'levels', fn (Builder $levels) => $levels->visibleToPublic()->whereKey($levelId)
                    ),
                };
            }
        };
    }
}
