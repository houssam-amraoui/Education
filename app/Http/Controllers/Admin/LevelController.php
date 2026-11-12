<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Curriculum\ChangeCurriculumStatus;
use App\Actions\Curriculum\ReorderCurriculum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeCurriculumStatusRequest;
use App\Http\Requests\Admin\ReorderCurriculumRequest;
use App\Http\Requests\Admin\SaveLevelRequest;
use App\Models\Level;
use App\Support\AccountPages;
use App\Support\CurriculumStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class LevelController extends Controller
{
    public function store(SaveLevelRequest $request): JsonResponse|RedirectResponse
    {
        $level = Level::create($request->validated());

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.levels.index', status: 303)->with('status', 'Level created.')
            : response()->json($level, 201);
    }

    public function update(SaveLevelRequest $request, Level $level): JsonResponse|RedirectResponse
    {
        $level->update($request->validated());

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.levels.index', status: 303)->with('status', 'Level updated.')
            : response()->json($level);
    }

    public function status(ChangeCurriculumStatusRequest $request, Level $level, ChangeCurriculumStatus $change): Response|RedirectResponse
    {
        $change->handle(Level::query(), $level->getKey(), $request->enum('status', CurriculumStatus::class));

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.levels.index', status: 303)->with('status', 'Publication updated.')
            : response()->noContent();
    }

    public function reorder(ReorderCurriculumRequest $request, Level $level, ReorderCurriculum $reorder): Response|RedirectResponse
    {
        $reorder->handle(Level::query(), $level->getKey(), $request->integer('position'));

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.levels.index', status: 303)->with('status', 'Order updated.')
            : response()->noContent();
    }
}
