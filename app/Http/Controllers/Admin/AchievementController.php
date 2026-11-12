<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SaveAchievementRequest;
use App\Models\Achievement;
use App\Support\AccountPages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class AchievementController
{
    public function index(): JsonResponse
    {
        return response()->json(Achievement::query()->orderBy('id')->paginate());
    }

    public function store(SaveAchievementRequest $request): JsonResponse|RedirectResponse
    {
        $achievement = Achievement::create($request->validated() + ['reward_points' => 0]);

        return AccountPages::html($request)
            ? redirect()->route('admin.rewards.achievements.index', [], 303)->with('status', 'Achievement saved.')
            : response()->json($achievement, 201);
    }

    public function update(SaveAchievementRequest $request, Achievement $achievement): JsonResponse|RedirectResponse
    {
        $achievement->update($request->validated() + ['minimum_score_percent' => null]);

        return AccountPages::html($request)
            ? redirect()->route('admin.rewards.achievements.index', [], 303)->with('status', 'Achievement saved.')
            : response()->json($achievement->refresh());
    }
}
