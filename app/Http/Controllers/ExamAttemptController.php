<?php

namespace App\Http\Controllers;

use App\Actions\Exams\StartExamAttempt;
use App\Actions\Exams\SubmitExamAttempt;
use App\Http\Requests\SubmitExamAttemptRequest;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Queries\ExamAttemptHistory;
use App\Support\AccountPages;
use App\Support\AttemptPages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamAttemptController extends Controller
{
    public function instructions(Exam $exam): View
    {
        return AttemptPages::instructions($exam);
    }

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

    public function index(Request $request, Exam $exam, ExamAttemptHistory $history): JsonResponse
    {
        return response()->json($history->paginate($request->user(), $exam));
    }

    public function show(Request $request, Exam $exam, ExamAttempt $attempt, ExamAttemptHistory $history): JsonResponse|View
    {
        if (AccountPages::html($request)) {
            return AttemptPages::show($exam, $attempt);
        }

        return response()->json($history->result($request->user(), $attempt));
    }
}
