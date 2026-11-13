<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResourceFiltersRequest;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Resource;
use App\Support\AccountPages;
use App\Support\DocumentResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ResourcePageController extends Controller
{
    public function index(ResourceFiltersRequest $request): View
    {
        $filters = $request->safe()->except('page');
        $level = isset($filters['level_id']) ? Level::visibleTo($request->user())->findOrFail($filters['level_id']) : null;
        $resources = Resource::visibleTo($request->user())->matching($filters);
        if (isset($filters['selected'])) {
            $student = $request->user();
            Gate::authorize('manageLearningProfile', $student);
            $selectedLevelIds = $student->selectedLevels()->visibleTo($student)->pluck('levels.id')->all();
            $resources->whereHas('levels', fn ($levels) => $levels->whereKey($selectedLevelIds));
        }

        $records = $resources->paginate(12)->appends($filters);

        return AccountPages::view('G16', 'list', compact('records', 'filters', 'level') + ['crumbs' => ['G01']], 'library.catalogue');
    }

    public function show(Resource $resource, DocumentResponse $documents): View
    {
        Gate::authorize('view', $resource);
        $resource->load([
            'levels' => fn ($levels) => $levels->visibleTo(request()->user())->ordered(),
            'lessons' => fn ($lessons) => $lessons->visibleTo(request()->user())->with('category.unit.level')->orderBy('title'),
            'storedFile',
        ]);

        return AccountPages::view('G17', 'detail', [
            'title' => $resource->title,
            'resource' => $resource,
            'available' => $documents->available($resource->storedFile),
            'crumbs' => ['G01', 'G16'],
        ], 'library.detail');
    }

    public function document(Resource $resource, DocumentResponse $documents): View
    {
        return $this->viewer($resource, $documents);
    }

    public function lessonDocument(Lesson $lesson, Resource $resource, DocumentResponse $documents): View
    {
        $lesson = Lesson::visibleTo(request()->user())->with('category.unit.level')->findOrFail($lesson->id);

        return $this->viewer($resource, $documents, $lesson);
    }

    private function viewer(Resource $resource, DocumentResponse $documents, ?Lesson $lesson = null): View
    {
        Gate::authorize('view', $resource);
        $back = $lesson
            ? route('curriculum.lesson', [$lesson->category->unit->level, $lesson->category->unit, $lesson->category, $lesson])
            : route('library.resources.show', $resource);
        $fileRoute = $lesson ? 'library.lessons.resources.file' : 'library.resources.file';
        $parameters = $lesson ? [$lesson, $resource] : [$resource];

        return AccountPages::view('G18', 'document', [
            'resource' => $resource,
            'lesson' => $lesson,
            'back' => $back,
            'inline' => route($fileRoute, [...$parameters, 'inline']),
            'download' => route($fileRoute, [...$parameters, 'attachment']),
            'available' => $documents->available($resource->storedFile),
            'crumbs' => ['G01', 'G16', $lesson ? 'G07' : 'G17'],
            'links' => [$lesson ? 'G07' : 'G17' => ['title' => $lesson?->title ?? $resource->title, 'url' => $back]],
        ], 'library.document');
    }
}
