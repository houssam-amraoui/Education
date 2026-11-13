<?php

namespace App\Queries;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExerciseAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AssessmentActivity
{
    public function exercises(User $student, ?int $levelId = null): Builder
    {
        return ExerciseAttempt::query()
            ->where('student_id', $student->getKey())
            ->whereNotNull('submitted_at')
            ->whereHas('exercise', fn (Builder $exercise) => $exercise->visibleTo($student)
                ->when($levelId !== null, fn (Builder $content) => $content->whereRelation('unit', 'level_id', $levelId)));
    }

    public function exams(User $student, ?int $levelId = null): Builder
    {
        return ExamAttempt::query()
            ->where('student_id', $student->getKey())
            ->whereHas('exam', fn (Builder $exam) => $exam->visibleTo($student)
                ->where('type', Exam::INTERACTIVE)
                ->when($levelId !== null, fn (Builder $content) => $content->whereRelation('unit', 'level_id', $levelId)));
    }
}
