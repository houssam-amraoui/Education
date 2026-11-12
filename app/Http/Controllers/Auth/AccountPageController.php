<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AccountLocaleRequest;
use App\Queries\ContinueLearning;
use App\Queries\StudentProgress;
use App\Support\AccountPages;
use App\Support\ProgressPages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AccountPageController extends Controller
{
    public function register(): View
    {
        return AccountPages::view('G21', 'auth');
    }

    public function login(Request $request): View
    {
        $destination = AccountPages::destination($request, $request->query('return_to'));
        if ($destination !== null) {
            $request->session()->put('url.intended', $destination);
        }

        return AccountPages::view('G22', 'auth');
    }

    public function home(Request $request, StudentProgress $progress, ContinueLearning $learning): View
    {
        $student = $request->user();
        if (Gate::denies('manageLearningProfile', $student)) {
            return AccountPages::view('S01', 'home');
        }

        $levels = $student->selectedLevels()->visibleToPublic()->ordered()->get();
        $selected = $levels->modelKeys();

        return AccountPages::view('S01', 'dashboard', [
            'student' => $student,
            'completion' => $progress->completion($student),
            'stats' => $progress->assessmentStats($student),
            'paths' => $levels->map(fn ($level): array => [
                'level' => $level, 'completion' => $progress->completion($student, $level->id),
            ]),
            'resume' => $learning->resume($student, $selected),
            'recent' => $learning->recentLessons($student, $selected),
            'history' => ProgressPages::activity($progress->history($student, perPage: 5, scoresOnly: true), $student),
        ], 'progress.dashboard');
    }

    public function locale(AccountLocaleRequest $request): RedirectResponse
    {
        $request->session()->put('locale', $request->validated('locale'));

        return redirect()->to(
            AccountPages::destination($request, $request->validated('return_to'), includeAuthPages: true)
                ?? route($request->user() ? 'account.home' : 'account.login.show'),
            303
        );
    }
}
