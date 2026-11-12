<?php

namespace App\Http\Controllers;

use App\Http\Requests\CurriculumFiltersRequest;
use App\Models\Exam;
use App\Models\Level;
use App\Queries\PrerequisiteCompletion;
use App\Queries\VisibleExams;
use App\Support\AccountPages;
use App\Support\AttemptPages;
use App\Support\DocumentResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExamPageController extends Controller
{
    public function index(CurriculumFiltersRequest $request, VisibleExams $exams): View
    {
        $filters = $request->validated();
        $level = isset($filters['level_id']) ? Level::visibleToPublic()->findOrFail($filters['level_id']) : null;
        $records = $exams->paginate($request->user(), $level?->id, search: $filters['search'] ?? null, type: $filters['type'] ?? null)
            ->appends($filters);

        return AccountPages::view('G13', 'list', compact('filters', 'level', 'records') + ['crumbs' => ['G01']], 'exams.catalogue');
    }

    public function show(Exam $exam, DocumentResponse $documents, PrerequisiteCompletion $preparation): View
    {
        if ($exam->type === Exam::INTERACTIVE) {
            return AttemptPages::instructions($exam);
        }

        Gate::authorize('viewFile', $exam);

        return AccountPages::view('G14', 'document-exam', [
            'title' => $exam->title,
            'exam' => $exam,
            'prerequisites' => $preparation->guidance(request()->user(), $exam),
            'paperAvailable' => $documents->available($exam->examFile),
            'correctionAvailable' => $documents->available($exam->correctionFile),
            'crumbs' => ['G13'],
        ], 'exams.document');
    }

    public function document(Exam $exam, string $document, DocumentResponse $documents): View
    {
        Gate::authorize('viewFile', $exam);

        $label = $document === 'paper' ? 'Exam paper' : 'Correction sheet';
        $back = route('curriculum.exams.instructions', $exam);
        $file = $document === 'paper' ? $exam->examFile : $exam->correctionFile;

        return AccountPages::view('G18', 'document', [
            'title' => $label,
            'documentTitle' => $exam->title,
            'back' => $back,
            'backLabel' => 'exam',
            'available' => $documents->available($file),
            'inline' => route('curriculum.exams.file', [$exam, $document, 'inline']),
            'download' => route('curriculum.exams.file', [$exam, $document, 'attachment']),
            'crumbs' => ['G13', 'G14'],
            'links' => ['G14' => ['url' => $back, 'title' => $exam->title]],
        ], 'library.document');
    }
}
