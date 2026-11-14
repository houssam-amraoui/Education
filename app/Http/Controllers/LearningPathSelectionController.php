<?php

namespace App\Http\Controllers;

use App\Actions\Auth\SaveSelectedLevels;
use App\Http\Requests\SaveSelectedLevelsRequest;
use App\Models\Level;
use App\Support\AccountPages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearningPathSelectionController extends Controller
{
    public function show(Request $request): View
    {
        $student = $request->user();
        Gate::authorize('manageLearningProfile', $student);

        $levels = Level::publishedInCurriculum()->ordered()->get();
        $suggestedLevelId = $levels->first(fn (Level $level) => (string) $level->id === $request->query('level_id'))?->id;

        return AccountPages::view('S02', 'learning-goals', [
            'parent' => 'S01',
            'levels' => $levels,
            'selected' => $student->selectedLevels()->pluck('levels.id')->all(),
            'suggestedLevelId' => $suggestedLevelId,
            'returnTo' => AccountPages::destination($request, $request->query('return_to')),
        ]);
    }

    public function update(SaveSelectedLevelsRequest $request, SaveSelectedLevels $save): RedirectResponse
    {
        $save->handle($request->user(), $request->validated('level_ids', []));

        return redirect()->to(AccountPages::destination($request, $request->validated('return_to')) ?? route('account.home'), 303)
            ->with('status', 'Learning paths saved.');
    }
}
