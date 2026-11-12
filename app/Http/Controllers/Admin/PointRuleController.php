<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SavePointRuleRequest;
use App\Models\PointRule;
use App\Support\AccountPages;
use App\Support\RewardEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PointRuleController
{
    public function index(): JsonResponse
    {
        return response()->json(PointRule::query()->orderBy('id')->paginate());
    }

    public function store(SavePointRuleRequest $request): JsonResponse|RedirectResponse
    {
        return $this->saved($request, PointRule::create($request->attributesForSave()), 201);
    }

    public function update(SavePointRuleRequest $request, PointRule $pointRule): JsonResponse|RedirectResponse
    {
        $pointRule->update($request->attributesForSave());

        return $this->saved($request, $pointRule->refresh());
    }

    private function saved(SavePointRuleRequest $request, PointRule $pointRule, int $status = 200): JsonResponse|RedirectResponse
    {
        if (AccountPages::html($request)) {
            return redirect()->route(
                $pointRule->event === RewardEvent::StreakMilestone ? 'admin.rewards.streak.edit' : 'admin.rewards.points',
                $pointRule->event === RewardEvent::StreakMilestone ? [$pointRule] : [],
                303,
            )->with('status', 'Points rule saved.');
        }

        return response()->json($pointRule, $status);
    }
}
