<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscoveryFiltersRequest;
use App\Models\Level;
use App\Models\Topic;
use App\Queries\AccessDestination;
use App\Queries\GlobalSearch;
use App\Queries\VisibleTopics;
use App\Support\AccountPages;
use App\Support\LearningResult;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DiscoveryController extends Controller
{
    public function search(DiscoveryFiltersRequest $request, GlobalSearch $search): View
    {
        $filters = $request->safe()->except('page');
        $records = $search->paginate($filters['q'] ?? '', $request->user(), type: $filters['type'] ?? null)->appends($filters);

        return AccountPages::view('G08', 'search', compact('filters', 'records') + ['crumbs' => ['G01']], 'discovery.catalogue');
    }

    public function topics(DiscoveryFiltersRequest $request, VisibleTopics $topics): View
    {
        $filters = $request->safe()->except('page');
        $level = $this->level($request);
        $viewer = isset($filters['access']) ? null : $request->user();
        $records = $topics->paginate($viewer, $level?->id, search: $filters['q'] ?? null)->appends($filters);

        return AccountPages::view('G09', 'topics', compact('filters', 'records', 'level') + [
            'crumbs' => $level ? ['G01', 'G03'] : ['G01'],
            'links' => $level ? ['G03' => ['title' => $level->title, 'url' => route('curriculum.level', $level)]] : [],
        ], 'discovery.catalogue');
    }

    public function topic(DiscoveryFiltersRequest $request, int $topic, VisibleTopics $topics, AccessDestination $access): View
    {
        $filters = $request->safe()->except('page');
        $level = $this->level($request);
        $record = $topics->findOrFail($topic, $request->user(), $level?->id);
        $records = collect();
        foreach (array_keys(Topic::CONTENT) as $type) {
            foreach ($record->{$type.'s'} as $item) {
                $labels = $access->labels($item);
                if ((! isset($filters['access']) || $labels->isEmpty())
                    && (! isset($filters['q']) || Str::contains($item->title, $filters['q'], ignoreCase: true))) {
                    $records->push(LearningResult::from($type, $item) + ['access_labels' => $labels->all()]);
                }
            }
        }

        return AccountPages::view('G10', 'topic', compact('filters', 'records', 'level') + [
            'topic' => $record,
            'title' => $record->title,
            'description' => $record->description,
            'crumbs' => ['G01', 'G09'],
            'links' => ['G09' => ['url' => route('curriculum.topics.index', $level ? ['level_id' => $level->id] : [])]],
        ], 'discovery.catalogue');
    }

    private function level(DiscoveryFiltersRequest $request): ?Level
    {
        $id = $request->validated('level_id');

        return $id === null ? null : Level::visibleTo($request->user())->findOrFail($id);
    }
}
