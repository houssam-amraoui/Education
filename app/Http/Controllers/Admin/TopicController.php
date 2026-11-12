<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Curriculum\ChangeCurriculumStatus;
use App\Actions\Topics\SaveTopic;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeCurriculumStatusRequest;
use App\Http\Requests\Admin\ListTopicsRequest;
use App\Http\Requests\Admin\SaveTopicRequest;
use App\Models\Topic;
use App\Support\AccountPages;
use App\Support\CurriculumStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TopicController extends Controller
{
    public function index(ListTopicsRequest $request): View
    {
        $filters = $request->safe()->except('page');
        $records = Topic::query()->withCount(['lessons', 'exercises', 'exams', 'resources', 'videos'])
            ->when(isset($filters['search']), fn ($topics) => $topics->whereLike('title', '%'.$filters['search'].'%'))
            ->when(isset($filters['status']), fn ($topics) => $topics->where('status', $filters['status']))
            ->orderBy('title')->orderBy('id')->paginate(20)->appends($filters);

        return AccountPages::view('A29', 'list', compact('filters', 'records') + ['crumbs' => ['A01']], 'topics.admin-list');
    }

    public function create(): View
    {
        return $this->edit(new Topic);
    }

    public function edit(Topic $topic): View
    {
        Gate::authorize('manage', $topic);

        return AccountPages::view('A30', 'editor', compact('topic') + ['crumbs' => ['A01', 'A29']], 'topics.admin-editor');
    }

    public function relationships(Topic $topic): View
    {
        Gate::authorize('manage', $topic);
        $options = [];
        $selected = [];
        foreach (Topic::CONTENT as $type => $model) {
            $options[$type] = $model::query()->orderBy('title')->orderBy('id')->get(['id', 'title']);
            $relation = $topic->{$type.'s'}();
            $selected[$type] = $relation->pluck($relation->getRelated()->getQualifiedKeyName())->all();
        }

        return AccountPages::view('A15', 'relationships', compact('topic', 'options', 'selected') + [
            'crumbs' => ['A01', 'A29', 'A30'],
            'links' => ['A30' => ['url' => route('admin.library.topics.edit', $topic)]],
        ], 'topics.relationships');
    }

    public function store(SaveTopicRequest $request, SaveTopic $save): JsonResponse|RedirectResponse
    {
        return $this->save($request, new Topic, $save, 201);
    }

    public function update(SaveTopicRequest $request, Topic $topic, SaveTopic $save): JsonResponse|RedirectResponse
    {
        return $this->save($request, $topic, $save, 200);
    }

    public function updateRelationships(SaveTopicRequest $request, Topic $topic, SaveTopic $save): RedirectResponse
    {
        $save->handle($topic, [], $request->validated());

        return redirect()->route('admin.library.topics.relationships', $topic, 303)->with('status', 'Relationships saved.');
    }

    public function status(ChangeCurriculumStatusRequest $request, Topic $topic, ChangeCurriculumStatus $change): Response|RedirectResponse
    {
        $change->handle(Topic::query(), $topic->id, $request->enum('status', CurriculumStatus::class));

        if (! AccountPages::html($request)) {
            return response()->noContent();
        }

        return ($request->input('return_to') === 'editor'
            ? redirect()->route('admin.library.topics.edit', $topic, 303)
            : redirect()->route('admin.library.topics.index', status: 303))
            ->with('status', 'Topic state saved.');
    }

    private function save(SaveTopicRequest $request, Topic $topic, SaveTopic $save, int $status): JsonResponse|RedirectResponse
    {
        $fields = ['title', 'description'];
        $saved = $save->handle($topic, $request->safe()->only($fields), $request->safe()->except($fields));

        return AccountPages::html($request)
            ? redirect()->route('admin.library.topics.edit', $saved, 303)->with('status', 'Topic saved.')
            : response()->json($saved, $status);
    }
}
