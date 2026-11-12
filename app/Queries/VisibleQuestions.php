<?php

namespace App\Queries;

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use App\Support\QuestionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VisibleQuestions
{
    public function forExercise(int $exerciseId): Collection
    {
        return $this->project(Question::whereHas('exercise', fn (Builder $exercise) => $exercise
            ->whereKey($exerciseId)->visibleToPublic()));
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
                'content' => $this->publicContent($question),
            ]);
    }

    private function publicContent(Question $question): array
    {
        $groups = match ($question->type) {
            QuestionType::SingleChoice, QuestionType::MultipleSelection => ['options'],
            QuestionType::Matching => ['left', 'right'],
            QuestionType::FillInTheBlanks => ['slots'],
            QuestionType::Ordering => ['items'],
            QuestionType::TrueFalse, QuestionType::ShortAnswer => [],
        };

        $content = [];
        foreach ($groups as $group) {
            $content[$group] = array_map(
                fn (array $item): array => ['id' => $item['id'], 'label' => $item['label']],
                $question->content[$group],
            );
        }

        return $content;
    }
}
