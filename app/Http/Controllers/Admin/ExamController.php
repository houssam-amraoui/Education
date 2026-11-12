<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Curriculum\ChangeCurriculumStatus;
use App\Actions\Curriculum\ReorderCurriculum;
use App\Actions\Exams\SaveExam;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeCurriculumStatusRequest;
use App\Http\Requests\Admin\ReorderCurriculumRequest;
use App\Http\Requests\Admin\SaveExamRequest;
use App\Models\Exam;
use App\Models\Unit;
use App\Support\CurriculumStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ExamController extends Controller
{
    public function store(SaveExamRequest $request, SaveExam $save): JsonResponse
    {
        return response()->json($this->save($request, new Exam, $save), 201);
    }

    public function update(SaveExamRequest $request, Exam $exam, SaveExam $save): JsonResponse
    {
        return response()->json($this->save($request, $exam, $save));
    }

    public function status(ChangeCurriculumStatusRequest $request, Unit $unit, Exam $exam, ChangeCurriculumStatus $change): Response
    {
        $change->handle(Exam::whereBelongsTo($unit), $exam->id, $request->enum('status', CurriculumStatus::class));

        return response()->noContent();
    }

    public function reorder(ReorderCurriculumRequest $request, Unit $unit, Exam $exam, ReorderCurriculum $reorder): Response
    {
        $reorder->handle(Exam::whereBelongsTo($unit), $exam->id, $request->integer('position'));

        return response()->noContent();
    }

    private function save(SaveExamRequest $request, Exam $exam, SaveExam $save): Exam
    {
        return $save->handle($exam, $request->safe()->except(['exam_file', 'correction_file']), array_filter([
            'examFile' => $request->file('exam_file'),
            'correctionFile' => $request->file('correction_file'),
        ]));
    }
}
