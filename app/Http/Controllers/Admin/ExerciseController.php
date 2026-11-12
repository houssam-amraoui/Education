<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Curriculum\ChangeCurriculumStatus;
use App\Actions\Curriculum\ReorderCurriculum;
use App\Actions\Curriculum\UpdateCurriculumPlacement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeCurriculumStatusRequest;
use App\Http\Requests\Admin\ReorderCurriculumRequest;
use App\Http\Requests\Admin\SaveExerciseRequest;
use App\Models\Exercise;
use App\Models\Unit;
use App\Support\CurriculumStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ExerciseController extends Controller
{
    public function store(SaveExerciseRequest $request): JsonResponse
    {
        return response()->json(Exercise::create($request->validated()), 201);
    }

    public function update(SaveExerciseRequest $request, Exercise $exercise, UpdateCurriculumPlacement $update): JsonResponse
    {
        return response()->json($update->handle($exercise, $request->validated()));
    }

    public function status(ChangeCurriculumStatusRequest $request, Unit $unit, Exercise $exercise, ChangeCurriculumStatus $change): Response
    {
        $change->handle(Exercise::whereBelongsTo($unit), $exercise->id, $request->enum('status', CurriculumStatus::class));

        return response()->noContent();
    }

    public function reorder(ReorderCurriculumRequest $request, Unit $unit, Exercise $exercise, ReorderCurriculum $reorder): Response
    {
        $reorder->handle(Exercise::whereBelongsTo($unit), $exercise->id, $request->integer('position'));

        return response()->noContent();
    }
}
