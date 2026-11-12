<?php

namespace App\Actions\Exercises;

use App\Models\Exercise;
use App\Models\ExerciseAttempt;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class StartExerciseAttempt
{
    public function handle(User $student, Exercise $exercise): ExerciseAttempt
    {
        return DB::transaction(function () use ($student, $exercise): ExerciseAttempt {
            // Question authoring takes the same lock before changing the question set.
            $exercise = Exercise::query()->lockForUpdate()->findOrFail($exercise->getKey());
            Gate::forUser($student)->authorize('attempt', $exercise);
            $questions = $exercise->questions()->ordered()->get();

            if ($questions->isEmpty()) {
                throw ValidationException::withMessages(['exercise' => 'This exercise has no questions.']);
            }

            $attempt = $exercise->attempts()->forceCreate([
                'student_id' => $student->getKey(),
                'started_at' => now(),
                'possible_points' => $questions->sum('points'),
            ]);

            $items = $questions->values()->map(fn (Question $question, int $index) => [
                'source_question_id' => $question->getKey(),
                'position' => $index + 1,
                'type' => $question->type,
                'prompt' => $question->prompt,
                'content' => $question->content,
                'answer_key' => $question->answer,
                'explanation' => $question->explanation,
                'points' => $question->points,
            ]);

            $attempt->items()->forceCreateMany($items);

            return $attempt->load('items');
        }, 3);
    }
}
