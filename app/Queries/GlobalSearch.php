<?php

namespace App\Queries;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Resource;
use App\Models\User;
use App\Models\Video;
use App\Support\LearningResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GlobalSearch
{
    public const TYPES = ['lesson', 'exercise', 'exam', 'resource', 'video', 'topic'];

    public function __construct(private VisibleTopics $topics) {}

    public function paginate(string $search, ?User $user = null, int $perPage = 12, ?string $type = null): LengthAwarePaginator
    {
        $search = trim($search);
        $pattern = '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
        $sources = [
            'lesson' => Lesson::visibleTo($user),
            'exercise' => Exercise::visibleTo($user),
            'exam' => Exam::visibleTo($user),
            'resource' => Resource::visibleTo($user),
            'video' => Video::visibleTo($user),
            'topic' => $this->topics->query($user),
        ];

        $matches = null;
        foreach ($sources as $resultType => $source) {
            $branch = (clone $source)->select('id', 'title')->selectRaw('? as result_type', [$resultType])
                ->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])->toBase();
            $matches = $matches === null ? $branch : $matches->unionAll($branch);
        }

        $page = DB::query()->fromSub($matches, 'search_results')
            ->when($search === '', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($type !== null, fn ($query) => $query->where('result_type', $type))
            ->orderBy('title')->orderBy('result_type')->orderBy('id')
            ->paginate($perPage);

        $details = [];
        foreach ($page->getCollection()->groupBy('result_type') as $type => $rows) {
            $details[$type] = $this->metadata($sources[$type], $type, $user)->findMany($rows->pluck('id'))->keyBy('id');
        }

        // A record can disappear or lose visibility between the page and metadata reads.
        $page->setCollection($page->getCollection()->map(function (object $row) use ($details): ?array {
            $item = $details[$row->result_type]->get($row->id);

            return $item === null ? null : LearningResult::from($row->result_type, $item)
                + ['access_labels' => app(AccessDestination::class)->labels($item)->all()];
        })->filter()->values());

        return $page;
    }

    private function metadata(Builder $source, string $type, ?User $user): Builder
    {
        $columns = match ($type) {
            'lesson' => ['category_id'],
            'exercise' => ['unit_id'],
            'exam' => ['unit_id', 'access'],
            'resource', 'video' => ['access'],
            'topic' => ['description'],
        };

        return $source->select('id', 'title', ...$columns)->with(LearningResult::relations($type, viewer: $user));
    }
}
