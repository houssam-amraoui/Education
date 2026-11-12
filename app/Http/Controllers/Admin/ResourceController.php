<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Resources\ChangeResourceStatus;
use App\Actions\Resources\SaveResource;
use App\Actions\Resources\SaveResourceLinks;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeCurriculumStatusRequest;
use App\Http\Requests\Admin\ListResourcesRequest;
use App\Http\Requests\Admin\SaveResourceLinksRequest;
use App\Http\Requests\Admin\SaveResourceRequest;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Resource;
use App\Support\AccountPages;
use App\Support\CurriculumStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ResourceController extends Controller
{
    public function index(ListResourcesRequest $request): JsonResponse|View
    {
        $filters = $request->safe()->except('page');
        $resources = Resource::matching($filters)->paginate(20)->appends($filters);

        if (AccountPages::html($request)) {
            return AccountPages::view('A25', 'list', [
                'records' => $resources,
                'filters' => $filters,
                'crumbs' => ['A01'],
            ], 'library.admin-list');
        }

        return response()->json($resources);
    }

    public function create(): View
    {
        return $this->editor(new Resource);
    }

    public function edit(Resource $resource): View
    {
        return $this->editor($resource);
    }

    public function store(SaveResourceRequest $request, SaveResource $save): JsonResponse|RedirectResponse
    {
        return $this->save($request, new Resource, $save, 201);
    }

    public function update(SaveResourceRequest $request, Resource $resource, SaveResource $save): JsonResponse|RedirectResponse
    {
        return $this->save($request, $resource, $save, 200);
    }

    public function links(SaveResourceLinksRequest $request, Resource $resource, SaveResourceLinks $save): Response
    {
        $save->handle($resource, $request->validated('level_ids'), $request->validated('lesson_ids'));

        return response()->noContent();
    }

    public function status(ChangeCurriculumStatusRequest $request, Resource $resource, ChangeResourceStatus $change): Response|RedirectResponse
    {
        $change->handle($resource, $request->enum('status', CurriculumStatus::class));

        if (AccountPages::html($request)) {
            return ($request->input('return_to') === 'editor'
                ? redirect()->route('admin.library.resources.edit', $resource, 303)
                : redirect()->route('admin.library.resources.index', status: 303))
                ->with('status', 'Resource state saved.');
        }

        return response()->noContent();
    }

    private function editor(Resource $resource): View
    {
        Gate::authorize('manage', $resource);
        $resource->load(['storedFile', 'levels', 'lessons']);

        return AccountPages::view('A26', 'editor', [
            'resource' => $resource,
            'levels' => Level::ordered()->get(),
            'lessons' => Lesson::with('category.unit.level')->orderBy('title')->orderBy('id')->get(),
            'crumbs' => ['A01', 'A25'],
        ], 'library.admin-editor');
    }

    private function save(SaveResourceRequest $request, Resource $resource, SaveResource $save, int $status): JsonResponse|RedirectResponse
    {
        $saved = $save->handle(
            $resource,
            $request->safe()->only(['title', 'description', 'type', 'access']),
            $request->file('file'),
            $request->safe()->only(['level_ids', 'lesson_ids']),
        );

        return AccountPages::html($request)
            ? redirect()->route('admin.library.resources.edit', $saved, 303)->with('status', 'Resource saved.')
            : response()->json($saved, $status);
    }
}
