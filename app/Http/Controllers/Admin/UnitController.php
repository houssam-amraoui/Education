<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Curriculum\ChangeCurriculumStatus;
use App\Actions\Curriculum\ReorderCurriculum;
use App\Actions\Curriculum\UpdateCurriculumPlacement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeCurriculumStatusRequest;
use App\Http\Requests\Admin\ReorderCurriculumRequest;
use App\Http\Requests\Admin\SaveUnitRequest;
use App\Models\Level;
use App\Models\Unit;
use App\Support\AccountPages;
use App\Support\CurriculumStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class UnitController extends Controller
{
    public function store(SaveUnitRequest $request): JsonResponse|RedirectResponse
    {
        $unit = Unit::create($request->validated());

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.units.index', ['level_id' => $unit->level_id], status: 303)->with('status', 'Unit created.')
            : response()->json($unit, 201);
    }

    public function update(SaveUnitRequest $request, Unit $unit, UpdateCurriculumPlacement $update): JsonResponse|RedirectResponse
    {
        $unit = $update->handle($unit, $request->validated());

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.units.index', ['level_id' => $unit->level_id], status: 303)->with('status', 'Unit updated.')
            : response()->json($unit);
    }

    public function status(ChangeCurriculumStatusRequest $request, Level $level, Unit $unit, ChangeCurriculumStatus $change): Response|RedirectResponse
    {
        $change->handle(Unit::whereBelongsTo($level), $unit->getKey(), $request->enum('status', CurriculumStatus::class));

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.units.index', ['level_id' => $level->id], status: 303)->with('status', 'Publication updated.')
            : response()->noContent();
    }

    public function reorder(ReorderCurriculumRequest $request, Level $level, Unit $unit, ReorderCurriculum $reorder): Response|RedirectResponse
    {
        $reorder->handle(Unit::whereBelongsTo($level), $unit->getKey(), $request->integer('position'));

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.units.index', ['level_id' => $level->id], status: 303)->with('status', 'Order updated.')
            : response()->noContent();
    }
}
