<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AccountLocaleRequest;
use App\Queries\AchievementProgress;
use App\Queries\ContinueLearning;
use App\Queries\Conversations;
use App\Queries\LearningRecommendations;
use App\Queries\LearningStreak;
use App\Queries\PointBalance;
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

    public function home(
        Request $request,
        StudentProgress $progress,
        ContinueLearning $learning,
        LearningRecommendations $recommendations,
        Conversations $conversations,
        PointBalance $points,
        LearningStreak $streaks,
        AchievementProgress $achievements,
    ): View {
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
            'recommendedExercises' => $recommendations->exercises($student, 1),
            'recommendedLessons' => $recommendations->nextLessons($student, limit: 1),
            'recentTeacherReplies' => $conversations->recentTeacherReplies($student),
            'pointBalance' => $points->for($student),
            'pointsThisWeek' => (int) $points->transactions($student)->where('amount', '>', 0)
                ->whereBetween('recorded_at', [now()->utc()->startOfWeek(), now()->utc()])->sum('amount'),
            'streakDays' => $streaks->current($student)['days'],
            'recentAchievement' => $achievements->awards($student)->with('achievement')
                ->orderByDesc('awarded_at')->orderByDesc('id')->first(),
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
