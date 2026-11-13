<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Queries\ContinueLearning;
use App\Queries\StudentProgress;
use App\Support\AccountPages;
use App\Support\ProgressPages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentProgressController extends Controller
{
    public function show(Request $request, StudentProgress $progress, ContinueLearning $learning, ?string $level = null): View
    {
        $student = $request->user();
        Gate::authorize('manageLearningProfile', $student);
        $selected = $student->selectedLevels()->visibleTo($student)->ordered()->get();
        $path = $level === null ? null : Level::visibleTo($student)->findOrFail($level);
        $levelId = $path?->id;
        $groups = $path ? $path->units()->visibleTo($student)->ordered()->get() : Level::visibleTo($student)->ordered()->get();

        return AccountPages::view($path ? 'S11' : 'S10', 'overview', [
            'title' => $path ? $path->title.' progress' : null,
            'crumbs' => $path ? ['S01', 'S10'] : ['S01'],
            'level' => $path,
            'completion' => $progress->completion($student, $levelId),
            'stats' => $progress->assessmentStats($student, $levelId),
            'week' => $progress->weeklyActivity($student, $levelId),
            'groups' => $groups->map(fn ($group): array => [
                'title' => $group->title,
                'completion' => $path ? $progress->completion($student, $levelId, $group->id) : $progress->completion($student, $group->id),
                'url' => $path ? route('curriculum.unit', [$path, $group]) : route('account.progress.level', $group),
                'label' => $path ? 'Open unit' : 'Level detail',
            ]),
            'resume' => $learning->resume($student, $path ? $selected->where('id', $levelId)->modelKeys() : $selected->modelKeys()),
            'history' => ProgressPages::activity($progress->history($student, $levelId, 5), $student),
            'historyUrl' => route('account.progress.history'),
        ], 'progress.overview');
    }

    public function history(Request $request, StudentProgress $progress): View
    {
        $student = $request->user();

        return AccountPages::view('S12', 'history', [
            'crumbs' => ['S01', 'S10'],
            'history' => ProgressPages::activity($progress->history($student), $student),
        ], 'progress.history');
    }
}
