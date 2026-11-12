<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Curriculum\ChangeCurriculumStatus;
use App\Actions\Curriculum\ReorderCurriculum;
use App\Actions\Exams\SaveExam;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeCurriculumStatusRequest;
use App\Http\Requests\Admin\ReorderCurriculumRequest;
use App\Http\Requests\Admin\SaveExamRequest;
use App\Http\Requests\CurriculumFiltersRequest;
use App\Models\Exam;
use App\Models\Unit;
use App\Queries\PrerequisiteLessonChoices;
use App\Support\AccountPages;
use App\Support\CurriculumStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(private PrerequisiteLessonChoices $prerequisites) {}

    public function index(CurriculumFiltersRequest $request): View
    {
        Gate::authorize('manage', Exam::class);
        $filters = $request->validated();
        $records = Exam::with('unit.level')->orderBy('unit_id')->ordered()
            ->when(filled($filters['search'] ?? null), fn ($query) => $query->whereLike('title', '%'.$filters['search'].'%'))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->paginate(20)->appends($filters);

        $positions = Exam::whereIn('unit_id', $records->pluck('unit_id')->unique())
            ->ordered()->get(['id', 'unit_id'])->groupBy('unit_id')
            ->map(fn ($siblings) => $siblings->pluck('id')->flip());

        return AccountPages::view('A20', 'list', compact('filters', 'records', 'positions') + ['crumbs' => ['A01']], 'exams.admin-list');
    }

    public function create(string $type): View
    {
        return $this->editor(new Exam(['type' => $type]));
    }

    public function edit(Exam $exam): View
    {
        return $this->editor($exam);
    }

    private function editor(Exam $exam): View
    {
        Gate::authorize('manage', $exam);
        $units = Unit::with('level')->orderBy('level_id')->ordered()->get()->groupBy('level_id');
        $document = $exam->type === Exam::DOCUMENT;

        return AccountPages::view($document ? 'A21' : 'A22', 'editor', compact('exam', 'units', 'document') + [
            'crumbs' => ['A01', 'A20'],
        ] + ($exam->exists ? $this->prerequisites->forContent($exam) : []), 'exams.admin-editor');
    }

    public function store(SaveExamRequest $request, SaveExam $save): JsonResponse|RedirectResponse
    {
        $exam = $this->save($request, new Exam, $save);

        return AccountPages::html($request) ? $this->saved($exam) : response()->json($exam, 201);
    }

    public function update(SaveExamRequest $request, Exam $exam, SaveExam $save): JsonResponse|RedirectResponse
    {
        $exam = $this->save($request, $exam, $save);

        return AccountPages::html($request) ? $this->saved($exam) : response()->json($exam);
    }

    public function status(ChangeCurriculumStatusRequest $request, Unit $unit, Exam $exam, ChangeCurriculumStatus $change): Response|RedirectResponse
    {
        $change->handle(Exam::whereBelongsTo($unit), $exam->id, $request->enum('status', CurriculumStatus::class));

        if (! AccountPages::html($request)) {
            return response()->noContent();
        }

        return ($request->input('return_to') === 'editor'
            ? redirect()->route('admin.curriculum.exams.edit', $exam, 303)
            : redirect()->route('admin.curriculum.exams.index', status: 303))
            ->with('status', 'Exam state saved.');
    }

    public function reorder(ReorderCurriculumRequest $request, Unit $unit, Exam $exam, ReorderCurriculum $reorder): Response|RedirectResponse
    {
        $reorder->handle(Exam::whereBelongsTo($unit), $exam->id, $request->integer('position'));

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.exams.index', status: 303)->with('status', 'Order updated.')
            : response()->noContent();
    }

    private function saved(Exam $exam): RedirectResponse
    {
        return redirect()->route('admin.curriculum.exams.edit', $exam, 303)->with('status', 'Exam saved.');
    }

    private function save(SaveExamRequest $request, Exam $exam, SaveExam $save): Exam
    {
        $attributes = $request->safe()->except(['exam_file', 'correction_file', 'timing_mode']);
        if (AccountPages::html($request)) {
            foreach (['available_from', 'available_until'] as $field) {
                if (isset($attributes[$field])) {
                    $attributes[$field] .= '+00:00';
                }
            }
            if ($request->validated('timing_mode') === 'untimed') {
                $attributes['duration_minutes'] = null;
            }
        }

        return $save->handle($exam, $attributes, array_filter([
            'examFile' => $request->file('exam_file'),
            'correctionFile' => $request->file('correction_file'),
        ]));
    }
}
