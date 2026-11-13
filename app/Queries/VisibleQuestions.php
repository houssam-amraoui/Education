<?php

namespace App\Queries;

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use App\Support\QuestionDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VisibleQuestions
{
    public function forExercise(int $exerciseId, ?User $user = null): Collection
    {
        return $this->project(Question::whereHas('exercise', fn (Builder $exercise) => $exercise
            ->whereKey($exerciseId)->visibleTo($user)));
    }

    public function forExam(int $examId, ?User $user = null): Collection
    {
        return $this->project(Question::whereHas('exam', fn (Builder $exam) => $exam
            ->whereKey($examId)->where('type', Exam::INTERACTIVE)->visibleTo($user)));
    }

    private function project(Builder $questions): Collection
    {
        return $questions->ordered()->get(['id', 'type', 'prompt', 'points', 'position', 'content'])
            ->toBase()->map(fn (Question $question): array => [
                'id' => $question->id,
                'type' => $question->type->value,
                'prompt' => $question->prompt,
                'points' => $question->points,
                'position' => $question->position,
                'content' => QuestionDefinition::publicContent($question->type, $question->content),
            ]);
    }
}
