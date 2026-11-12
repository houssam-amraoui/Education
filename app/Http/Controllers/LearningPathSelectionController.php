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

        return AccountPages::view('S02', 'learning-goals', [
            'parent' => 'S01',
            'levels' => Level::visibleToPublic()->ordered()->get(),
            'selected' => $student->selectedLevels()->pluck('levels.id')->all(),
        ]);
    }

    public function update(SaveSelectedLevelsRequest $request, SaveSelectedLevels $save): RedirectResponse
    {
        $save->handle($request->user(), $request->validated('level_ids', []));

        return redirect()->route('account.home', status: 303)->with('status', 'Learning paths saved.');
    }
}
