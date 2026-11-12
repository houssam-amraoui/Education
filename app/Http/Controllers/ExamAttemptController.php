<?php

namespace App\Http\Controllers;

use App\Actions\Exams\StartExamAttempt;
use App\Actions\Exams\SubmitExamAttempt;
use App\Http\Requests\SubmitExamAttemptRequest;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Queries\ExamAttemptHistory;
use App\Queries\LearningRecommendations;
use App\Support\AccountPages;
use App\Support\AttemptPages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamAttemptController extends Controller
{
    public function store(Request $request, Exam $exam, StartExamAttempt $start): JsonResponse|RedirectResponse
    {
        $attempt = $start->handle($request->user(), $exam);

        if (AccountPages::html($request)) {
            return redirect()->route('curriculum.exams.attempts.show', [$exam, $attempt], 303);
        }

        return response()->json($attempt->summary() + [
            'questions' => $attempt->items->map->learnerQuestion(),
        ], 201);
    }

    public function submit(SubmitExamAttemptRequest $request, Exam $exam, ExamAttempt $attempt, SubmitExamAttempt $submit): JsonResponse
    {
        return response()->json($submit->handle($request->user(), $attempt, $request->validated('answers'))->summary());
    }

    public function index(Request $request, Exam $exam, ExamAttemptHistory $history): JsonResponse|View
    {
        $records = $history->paginate($request->user(), $exam);
        if (! AccountPages::html($request)) {
            return response()->json($records);
        }

        return AccountPages::view('S09', 'history', compact('exam', 'records') + $this->reviewNavigation($exam), 'exams.history');
    }

    public function show(Request $request, Exam $exam, ExamAttempt $attempt, ExamAttemptHistory $history, LearningRecommendations $recommendations): JsonResponse|View
    {
        if (AccountPages::html($request)) {
            if ($attempt->submitted_at === null && $attempt->expired_at === null) {
                return AttemptPages::show($exam, $attempt);
            }

            $result = $history->result($request->user(), $attempt);

            return AccountPages::view('S08', 'results', [
                'exam' => $exam,
                'result' => $result,
                'recommendedLessons' => $recommendations->examReview($request->user(), $attempt),
            ] + $this->reviewNavigation($exam), 'exams.result');
        }

        return response()->json($history->result($request->user(), $attempt));
    }

    private function reviewNavigation(Exam $exam): array
    {
        return [
            'description' => $exam->title,
            'crumbs' => ['G13', 'G15'],
            'links' => ['G15' => ['url' => route('curriculum.exams.instructions', $exam), 'title' => $exam->title]],
        ];
    }
}
