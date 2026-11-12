<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class StartExamAttempt
{
    public function handle(User $student, Exam $exam): ExamAttempt
    {
        return DB::transaction(function () use ($student, $exam): ExamAttempt {
            // Question authoring takes the same lock before changing the question set.
            $exam = Exam::query()->lockForUpdate()->findOrFail($exam->getKey());
            Gate::forUser($student)->authorize('attempt', $exam);
            $questions = $exam->questions()->ordered()->get();

            if ($questions->isEmpty()) {
                throw ValidationException::withMessages(['exam' => 'This exam has no questions.']);
            }

            $startedAt = now()->toImmutable()->utc()->startOfSecond();

            $attempt = $exam->attempts()->forceCreate([
                'student_id' => $student->getKey(),
                'started_at' => $startedAt,
                'deadline_at' => $exam->duration_minutes === null ? null : $startedAt->addMinutes($exam->duration_minutes),
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
