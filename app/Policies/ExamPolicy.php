<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExamPolicy extends CurriculumPolicy
{
    public function manageQuestions(User $user, Exam $exam): Response
    {
        if (! $this->manage($user)) {
            return Response::deny();
        }

        return $exam->type === Exam::INTERACTIVE ? Response::allow() : Response::denyAsNotFound();
    }

    public function view(?User $user, Exam $exam): Response
    {
        return Exam::visibleTo($user)->whereKey($exam->getKey())->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function viewFile(?User $user, Exam $exam): Response
    {
        return Exam::visibleTo($user)->whereKey($exam->getKey())
            ->where('type', Exam::DOCUMENT)
            ->where('exam_file_id', $exam->exam_file_id)
            ->where('correction_file_id', $exam->correction_file_id)
            ->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
