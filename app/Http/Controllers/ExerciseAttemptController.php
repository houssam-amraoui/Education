<?php

namespace App\Http\Controllers;

use App\Actions\Exercises\StartExerciseAttempt;
use App\Actions\Exercises\SubmitExerciseAttempt;
use App\Http\Requests\SubmitExerciseAttemptRequest;
use App\Models\Exercise;
use App\Models\ExerciseAttempt;
use App\Queries\ExerciseAttemptHistory;
use App\Support\AccountPages;
use App\Support\AttemptPages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExerciseAttemptController extends Controller
{
    public function instructions(Exercise $exercise): View
    {
        return AttemptPages::instructions($exercise);
    }

    public function store(Request $request, Exercise $exercise, StartExerciseAttempt $start): JsonResponse|RedirectResponse
    {
        $attempt = $start->handle($request->user(), $exercise);

        return AccountPages::html($request)
            ? redirect()->route('curriculum.exercises.attempts.show', [$exercise, $attempt], 303)
            : $this->started($attempt);
    }

    public function submit(SubmitExerciseAttemptRequest $request, Exercise $exercise, ExerciseAttempt $attempt, SubmitExerciseAttempt $submit): JsonResponse
    {
        $result = $submit->handle($request->user(), $attempt, $request->validated('answers'));

        return response()->json($result->summary());
    }

    public function index(Request $request, Exercise $exercise, ExerciseAttemptHistory $history): JsonResponse
    {
        return response()->json($history->paginate($request->user(), $exercise));
    }

    public function show(Request $request, Exercise $exercise, ExerciseAttempt $attempt, ExerciseAttemptHistory $history): JsonResponse|View
    {
        if (AccountPages::html($request)) {
            return AttemptPages::show($exercise, $attempt);
        }

        return response()->json($history->result($request->user(), $attempt));
    }

    public function retry(Request $request, Exercise $exercise, ExerciseAttempt $attempt, StartExerciseAttempt $start): JsonResponse
    {
        Gate::authorize('access', $attempt);
        abort_if($attempt->submitted_at === null, 404);

        return $this->started($start->handle($request->user(), $exercise));
    }

    private function started(ExerciseAttempt $attempt): JsonResponse
    {
        return response()->json([
            'id' => $attempt->id,
            'exercise_id' => $attempt->exercise_id,
            'started_at' => $attempt->started_at,
            'possible_points' => $attempt->possible_points,
            'questions' => $attempt->items->map->learnerQuestion(),
        ], 201);
    }
}
