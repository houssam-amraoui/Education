<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Curriculum\ChangeCurriculumStatus;
use App\Actions\Curriculum\ReorderCurriculum;
use App\Actions\Curriculum\UpdateCurriculumPlacement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeCurriculumStatusRequest;
use App\Http\Requests\Admin\ReorderCurriculumRequest;
use App\Http\Requests\Admin\SaveExerciseRequest;
use App\Http\Requests\CurriculumFiltersRequest;
use App\Models\Exercise;
use App\Models\Unit;
use App\Queries\PrerequisiteLessonChoices;
use App\Support\AccountPages;
use App\Support\CurriculumStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExerciseController extends Controller
{
    public function __construct(private PrerequisiteLessonChoices $prerequisites) {}

    public function index(CurriculumFiltersRequest $request): View
    {
        Gate::authorize('manage', Exercise::class);
        $filters = $request->validated();
        $records = Exercise::query()->with('unit.level')->orderBy('unit_id')->ordered()
            ->when(filled($filters['search'] ?? null), fn ($query) => $query->whereLike('title', '%'.$filters['search'].'%'))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->paginate(20)->appends($filters);
        $positions = Exercise::whereIn('unit_id', $records->pluck('unit_id')->unique())
            ->ordered()->get(['id', 'unit_id'])->groupBy('unit_id')
            ->map(fn ($siblings) => $siblings->pluck('id')->flip());

        return AccountPages::view('A16', 'list', compact('filters', 'records', 'positions') + ['crumbs' => ['A01']], 'exercises.admin-list');
    }

    public function create(): View
    {
        return $this->editor(new Exercise);
    }

    public function edit(Exercise $exercise): View
    {
        return $this->editor($exercise);
    }

    public function store(SaveExerciseRequest $request): JsonResponse|RedirectResponse
    {
        $exercise = Exercise::create($request->validated());

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.exercises.edit', $exercise, 303)->with('status', 'Exercise saved.')
            : response()->json($exercise, 201);
    }

    public function update(SaveExerciseRequest $request, Exercise $exercise, UpdateCurriculumPlacement $update): JsonResponse|RedirectResponse
    {
        $exercise = $update->handle($exercise, $request->validated());

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.exercises.edit', $exercise, 303)->with('status', 'Exercise saved.')
            : response()->json($exercise);
    }

    public function status(ChangeCurriculumStatusRequest $request, Unit $unit, Exercise $exercise, ChangeCurriculumStatus $change): Response|RedirectResponse
    {
        $change->handle(Exercise::whereBelongsTo($unit), $exercise->id, $request->enum('status', CurriculumStatus::class));

        if (! AccountPages::html($request)) {
            return response()->noContent();
        }

        return ($request->input('return_to') === 'editor'
            ? redirect()->route('admin.curriculum.exercises.edit', $exercise, 303)
            : redirect()->route('admin.curriculum.exercises.index', status: 303))
            ->with('status', 'Exercise state saved.');
    }

    public function reorder(ReorderCurriculumRequest $request, Unit $unit, Exercise $exercise, ReorderCurriculum $reorder): Response|RedirectResponse
    {
        $reorder->handle(Exercise::whereBelongsTo($unit), $exercise->id, $request->integer('position'));

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.exercises.index', status: 303)->with('status', 'Order updated.')
            : response()->noContent();
    }

    private function editor(Exercise $exercise): View
    {
        Gate::authorize('manage', $exercise);
        $units = Unit::with('level')->orderBy('level_id')->ordered()->get()->groupBy('level_id');

        return AccountPages::view('A17', 'editor', compact('exercise', 'units') + ['crumbs' => ['A01', 'A16']]
            + ($exercise->exists ? $this->prerequisites->forContent($exercise) : []), 'exercises.admin-editor');
    }
}
