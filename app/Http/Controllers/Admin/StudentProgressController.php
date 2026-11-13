<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ExerciseAttempt;
use App\Models\User;
use App\Queries\ManagedStudentProgress;
use App\Queries\StudentProgress;
use App\Support\AccountPages;
use App\Support\ProgressPages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentProgressController extends Controller
{
    public function show(Request $request, User $user, ManagedStudentProgress $managed, StudentProgress $progress): View
    {
        $completion = $managed->completion($request->user(), $user);
        $levels = $user->selectedLevels()->visibleTo($user)->ordered()->get();

        return AccountPages::view('A04', 'overview', [
            'parent' => 'A02',
            'student' => $user,
            'completion' => $completion,
            'stats' => $progress->assessmentStats($user),
            'week' => $progress->weeklyActivity($user),
            'groups' => $levels->map(fn ($level): array => [
                'title' => $level->title,
                'completion' => $progress->completion($user, $level->id),
                'url' => route('curriculum.level', $level),
                'label' => 'Open level',
            ]),
            'history' => ProgressPages::activity($managed->history($request->user(), $user), $user, managed: true),
        ], 'progress.overview');
    }

    public function review(Request $request, User $user, string $type, string $attempt, ManagedStudentProgress $managed): View
    {
        Gate::authorize('viewStudentProgress', $user);
        $record = ($type === 'exam' ? ExamAttempt::query() : ExerciseAttempt::query())
            ->where('student_id', $user->id)->findOrFail($attempt);
        $result = $managed->review($request->user(), $user, $record);
        $assessment = $record instanceof ExamAttempt ? $record->exam : $record->exercise;

        return AccountPages::view('A04', 'review', [
            'title' => $assessment->title,
            'description' => $user->name.' · Attempt review',
            'crumbs' => ['A02', 'A04'],
            'links' => ['A04' => ['title' => 'Student learning activity', 'url' => route('admin.accounts.users.progress', $user)]],
            'result' => $result,
        ], 'progress.review');
    }
}
