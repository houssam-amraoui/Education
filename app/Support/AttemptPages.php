<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Exercise;
use App\Models\ExerciseAttempt;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AttemptPages
{
    public static function instructions(Exercise|Exam $assessment): View
    {
        $exam = $assessment instanceof Exam;
        abort_if($exam && $assessment->type !== Exam::INTERACTIVE, 404);
        Gate::authorize('view', $assessment);

        return AccountPages::view($exam ? 'G15' : 'G12', 'instructions', [
            'title' => $assessment->title,
            'description' => $exam ? 'Interactive exam' : 'Practice exercise',
            'headingBadge' => auth()->check() ? $assessment->unit->level->title : null,
            'assessment' => $assessment,
            'exam' => $exam,
            'questionCount' => $assessment->questions()->count(),
            'canStart' => Gate::allows('attempt', $assessment),
            'startUrl' => route(self::prefix($exam).'.attempts.store', $assessment),
        ], 'attempts.instructions');
    }

    public static function show(Exercise|Exam $assessment, ExerciseAttempt|ExamAttempt $attempt): View
    {
        Gate::authorize('access', $attempt);
        $exam = $attempt instanceof ExamAttempt;
        $summary = $attempt->summary();
        $questions = $summary['status'] === 'in_progress' ? $attempt->items()->get()->map->learnerQuestion() : collect();
        $instructions = $exam ? 'G15' : 'G12';
        $prefix = self::prefix($exam);

        return AccountPages::view($exam ? 'S07' : 'S04', 'attempt', [
            'title' => $assessment->title,
            'description' => $exam ? 'Interactive exam' : 'Practice exercise',
            'headingBadge' => $assessment->unit->level->title,
            'parent' => $instructions,
            'links' => [$instructions => ['url' => route($prefix.'.instructions', $assessment), 'title' => 'Instructions']],
            'exam' => $exam,
            'questions' => $questions,
            'summary' => $summary,
            'deadline' => $exam ? $attempt->deadline_at : null,
            'submitUrl' => route($prefix.'.attempts.submit', [$assessment, $attempt]),
            'resultUrl' => route($prefix.'.attempts.show', [$assessment, $attempt]),
        ], 'attempts.show');
    }

    private static function prefix(bool $exam): string
    {
        return $exam ? 'curriculum.exams' : 'curriculum.exercises';
    }
}
