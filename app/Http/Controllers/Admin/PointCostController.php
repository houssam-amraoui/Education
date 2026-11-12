<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SavePointCostRequest;
use App\Models\PointCost;
use App\Support\AccountPages;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class PointCostController
{
    public function index(): JsonResponse
    {
        return response()->json(PointCost::query()->orderBy('id')->paginate());
    }

    public function store(SavePointCostRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $cost = PointCost::create($request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['target_id' => __('A points cost already exists for this target.')]);
        }

        return AccountPages::html($request)
            ? redirect()->route('admin.rewards.points', [], 303)->with('status', 'Points cost saved.')
            : response()->json($cost, 201);
    }

    public function update(SavePointCostRequest $request, PointCost $pointCost): JsonResponse|RedirectResponse
    {
        $pointCost->update($request->safe()->only(['points', 'enabled']));

        return AccountPages::html($request)
            ? redirect()->route('admin.rewards.points', [], 303)->with('status', 'Points cost saved.')
            : response()->json($pointCost->refresh());
    }
}
