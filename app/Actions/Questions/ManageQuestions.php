<?php

namespace App\Actions\Questions;

use App\Actions\Curriculum\ReorderCurriculum;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Question;
use App\Support\QuestionType;
use Illuminate\Support\Facades\DB;

class ManageQuestions
{
    public function __construct(private ReorderCurriculum $reorderCurriculum) {}

    public function save(Exercise|Exam $owner, array $attributes, ?int $id = null): Question
    {
        $attributes += ['explanation' => null];
        if ($attributes['type'] === QuestionType::TrueFalse->value) {
            $attributes['answer']['value'] = (bool) $attributes['answer']['value'];
        }

        return DB::transaction(function () use ($owner, $attributes, $id): Question {
            $siblings = $this->lockOwner($owner)->questions();

            if ($id !== null) {
                $question = $siblings->lockForUpdate()->findOrFail($id);
                $question->update($attributes);

                return $question;
            }

            $last = (clone $siblings)->orderByDesc('position')->lockForUpdate()->first(['position']);
            $question = $siblings->make($attributes);
            $question->position = ($last?->position ?? 0) + 1;
            $question->save();

            return $question;
        }, 3);
    }

    public function remove(Exercise|Exam $owner, int $id): void
    {
        DB::transaction(function () use ($owner, $id): void {
            $siblings = $this->lockOwner($owner)->questions();
            $question = (clone $siblings)->lockForUpdate()->findOrFail($id);
            $question->delete();

            // Move in ascending order so each destination position is already free.
            $following = $siblings->where('position', '>', $question->position)->ordered()->lockForUpdate()->get();
            foreach ($following as $sibling) {
                $sibling->decrement('position');
            }
        }, 3);
    }

    public function reorder(Exercise|Exam $owner, int $id, int $position): void
    {
        DB::transaction(function () use ($owner, $id, $position): void {
            $siblings = $this->lockOwner($owner)->questions()->getQuery();
            $this->reorderCurriculum->handle($siblings, $id, $position);
        }, 3);
    }

    private function lockOwner(Exercise|Exam $owner): Exercise|Exam
    {
        $locked = $owner->newQuery()->lockForUpdate()->findOrFail($owner->getKey());
        abort_if($locked instanceof Exam && $locked->type !== Exam::INTERACTIVE, 404);

        return $locked;
    }
}
