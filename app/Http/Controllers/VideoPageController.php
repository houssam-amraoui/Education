<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoFiltersRequest;
use App\Models\Level;
use App\Models\Video;
use App\Queries\VisibleVideos;
use App\Support\AccountPages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VideoPageController extends Controller
{
    public function index(VideoFiltersRequest $request, VisibleVideos $videos): View
    {
        $filters = $request->safe()->except('page');
        $level = isset($filters['level_id']) ? Level::visibleToPublic()->findOrFail($filters['level_id']) : null;
        $records = $videos->paginate($request->user(), $filters);

        return AccountPages::view('G19', 'list', compact('records', 'filters', 'level') + ['crumbs' => ['G01']], 'videos.catalogue');
    }

    public function show(Request $request, Video $video): View
    {
        Gate::authorize('view', $video);
        $video->load(['lessons' => fn ($lessons) => $lessons->visibleToPublic()->ordered()->with([
            'category.unit.level',
            'resources' => fn ($resources) => $resources->visibleTo($request->user())->matching([]),
        ])]);

        return AccountPages::view('G20', 'detail', [
            'title' => $video->title,
            'video' => $video,
            'resources' => $video->lessons->flatMap->resources->unique('id'),
            'crumbs' => ['G01', 'G19'],
        ], 'videos.detail');
    }
}
