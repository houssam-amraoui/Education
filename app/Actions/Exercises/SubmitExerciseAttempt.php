<?php

namespace App\Actions\Exercises;

use App\Actions\Questions\GradeQuestion;
use App\Models\ExerciseAttempt;
use App\Models\User;
use App\Support\AttemptAnswers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SubmitExerciseAttempt
{
    public function __construct(private GradeQuestion $grade) {}

    public function handle(User $student, ExerciseAttempt $attempt, array $answers): ExerciseAttempt
    {
        return DB::transaction(function () use ($student, $attempt, $answers): ExerciseAttempt {
            $attempt = ExerciseAttempt::query()->lockForUpdate()->findOrFail($attempt->getKey());
            Gate::forUser($student)->authorize('access', $attempt);

            if ($attempt->submitted_at !== null) {
                return $attempt;
            }

            $items = $attempt->items()->get();
            AttemptAnswers::validate($items, $answers);
            $total = 0;

            foreach ($items as $item) {
                $answer = $answers[$item->id];
                $grade = $this->grade->handle($item->type, $item->answer_key, $answer, $item->points);
                $item->forceFill([
                    'submitted_answer' => $answer,
                    'correct' => $grade['correct'],
                    'awarded_points' => $grade['awarded_points'],
                ])->save();
                $total += $grade['awarded_points'];
            }

            $attempt->forceFill(['submitted_at' => now(), 'awarded_points' => $total])->save();

            return $attempt;
        }, 3);
    }
}
