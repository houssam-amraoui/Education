<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Curriculum\ChangeCurriculumStatus;
use App\Actions\Curriculum\ReorderCurriculum;
use App\Actions\Videos\SaveVideo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeCurriculumStatusRequest;
use App\Http\Requests\Admin\ListVideosRequest;
use App\Http\Requests\Admin\ReorderCurriculumRequest;
use App\Http\Requests\Admin\SaveVideoRequest;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Video;
use App\Support\AccountPages;
use App\Support\CurriculumStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function index(ListVideosRequest $request): View
    {
        $filters = $request->safe()->except('page');
        $query = Video::ordered();
        $positions = (clone $query)->pluck('id')->flip();
        $records = $query->matching($filters)
            ->when(isset($filters['status']), fn ($videos) => $videos->where('status', $filters['status']))
            ->with('levels')->paginate(20)->appends($filters);

        return AccountPages::view('A27', 'list', compact('records', 'positions', 'filters') + ['crumbs' => ['A01']], 'videos.admin-list');
    }

    public function create(): View
    {
        return $this->editor(new Video);
    }

    public function edit(Video $video): View
    {
        return $this->editor($video);
    }

    public function store(SaveVideoRequest $request, SaveVideo $save): JsonResponse|RedirectResponse
    {
        return $this->save($request, new Video, $save, 201);
    }

    public function update(SaveVideoRequest $request, Video $video, SaveVideo $save): JsonResponse|RedirectResponse
    {
        return $this->save($request, $video, $save, 200);
    }

    public function status(ChangeCurriculumStatusRequest $request, Video $video, ChangeCurriculumStatus $change): Response|RedirectResponse
    {
        $change->handle(Video::query(), $video->id, $request->enum('status', CurriculumStatus::class));

        if (! AccountPages::html($request)) {
            return response()->noContent();
        }

        return ($request->input('return_to') === 'editor'
            ? redirect()->route('admin.library.videos.edit', $video, 303)
            : redirect()->route('admin.library.videos.index', status: 303))
            ->with('status', 'Video state saved.');
    }

    public function reorder(ReorderCurriculumRequest $request, Video $video, ReorderCurriculum $reorder): Response|RedirectResponse
    {
        $reorder->handle(Video::query(), $video->id, $request->integer('position'));

        return AccountPages::html($request)
            ? redirect()->route('admin.library.videos.index', status: 303)->with('status', 'Order updated.')
            : response()->noContent();
    }

    private function editor(Video $video): View
    {
        Gate::authorize('manage', $video);
        $video->load(['levels', 'lessons']);

        return AccountPages::view('A28', 'editor', [
            'video' => $video,
            'levels' => Level::ordered()->get(),
            'lessons' => Lesson::with('category.unit.level')->orderBy('title')->orderBy('id')->get(),
            'crumbs' => ['A01', 'A27'],
        ], 'videos.admin-editor');
    }

    private function save(SaveVideoRequest $request, Video $video, SaveVideo $save, int $status): JsonResponse|RedirectResponse
    {
        $saved = $save->handle(
            $video,
            $request->safe()->only(['title', 'description', 'access']),
            $request->validated('source_url'),
            $request->safe()->only(['level_ids', 'lesson_ids']),
        );

        return AccountPages::html($request)
            ? redirect()->route('admin.library.videos.edit', $saved, 303)->with('status', 'Video saved.')
            : response()->json($saved, $status);
    }
}
