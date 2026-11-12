<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Curriculum\ChangeCurriculumStatus;
use App\Actions\Curriculum\ReorderCurriculum;
use App\Actions\Curriculum\UpdateCurriculumPlacement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderCurriculumRequest;
use App\Http\Requests\Admin\SaveCategoryRequest;
use App\Models\Category;
use App\Models\Unit;
use App\Support\AccountPages;
use App\Support\CurriculumStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function store(SaveCategoryRequest $request): JsonResponse|RedirectResponse
    {
        $category = Category::create($request->validated());

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.categories.index', ['unit_id' => $category->unit_id], status: 303)->with('status', 'Category created.')
            : response()->json($category, 201);
    }

    public function update(SaveCategoryRequest $request, Category $category, UpdateCurriculumPlacement $update): JsonResponse|RedirectResponse
    {
        $category = $update->handle($category, $request->validated());

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.categories.index', ['unit_id' => $category->unit_id], status: 303)->with('status', 'Category updated.')
            : response()->json($category);
    }

    public function archive(Request $request, Unit $unit, Category $category, ChangeCurriculumStatus $change): Response|RedirectResponse
    {
        $change->handle(Category::whereBelongsTo($unit), $category->getKey(), CurriculumStatus::Archived);

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.categories.index', ['unit_id' => $unit->id], status: 303)->with('status', 'Category archived.')
            : response()->noContent();
    }

    public function reorder(ReorderCurriculumRequest $request, Unit $unit, Category $category, ReorderCurriculum $reorder): Response|RedirectResponse
    {
        $reorder->handle(Category::whereBelongsTo($unit), $category->getKey(), $request->integer('position'));

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.categories.index', ['unit_id' => $unit->id], status: 303)->with('status', 'Order updated.')
            : response()->noContent();
    }
}
