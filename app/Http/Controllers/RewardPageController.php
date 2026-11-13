<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\PointCost;
use App\Queries\AccessDestination;
use App\Queries\AchievementProgress;
use App\Queries\LearningStreak;
use App\Queries\PointBalance;
use App\Support\AccountPages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RewardPageController
{
    public function index(Request $request, AchievementProgress $progress, LearningStreak $streaks, PointBalance $points, AccessDestination $access): View
    {
        $student = $request->user();
        Gate::authorize('manageLearningProfile', $student);
        $definitions = $progress->definitions($student)->orderBy('id')->paginate(9);
        $awards = $progress->awards($student)->whereIn('achievement_id', $definitions->modelKeys())
            ->pluck('awarded_at', 'achievement_id');
        $unlocks = [];
        foreach (PointCost::where('enabled', true)->orderBy('id')->cursor() as $cost) {
            $type = $cost->target_type->value;
            $id = $cost->target_id;
            $record = $access->find($type, $id, $student);
            if (($record || ($type === 'ask_teacher' && $id === 0))
                && $access->cost($type, $id, $student, $access->outstanding($type, $id, $student, $record))) {
                $unlocks[] = ['title' => $record?->title ?? 'Ask Teacher', 'points' => $cost->points, 'url' => route('access.requirements', [$type, $id])];
            }
        }

        return AccountPages::view('S16', 'rewards', [
            'definitions' => $definitions,
            'awards' => $awards,
            'awardCount' => $progress->awards($student)->count(),
            'balance' => $points->for($student),
            'unlocks' => $unlocks,
            'streak' => $streaks->current($student)['days'],
            'crumbs' => ['S01'],
        ], 'rewards.index');
    }

    public function show(
        Request $request, Achievement $achievement, AchievementProgress $progress, LearningStreak $streaks, PointBalance $points,
    ): View {
        $student = $request->user();
        Gate::authorize('manageLearningProfile', $student);
        $achievement = $progress->definitions($student)->findOrFail($achievement->id);
        $award = $progress->awards($student)->where('achievement_id', $achievement->id)->first();
        $count = $award ? $achievement->required_count
            : min($achievement->required_count, $progress->count($student, $achievement, $streaks->current($student)['days']));
        $reward = $award ? (int) $points->transactions($student)->where('source_key', "achievement:{$achievement->id}")->value('amount')
            : $achievement->reward_points;

        return AccountPages::view('S17', 'achievement', [
            'title' => $achievement->name, 'crumbs' => ['S01', 'S16'],
            'achievement' => $achievement, 'award' => $award, 'count' => $count, 'reward' => $reward,
        ], 'rewards.achievement');
    }
}
