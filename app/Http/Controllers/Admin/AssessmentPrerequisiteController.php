<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Lessons\SavePrerequisiteLessons;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAssessmentPrerequisitesRequest;
use App\Models\Exam;
use App\Models\Exercise;
use App\Support\AccountPages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class AssessmentPrerequisiteController extends Controller
{
    public function exercise(SaveAssessmentPrerequisitesRequest $request, Exercise $exercise, SavePrerequisiteLessons $save): Response|RedirectResponse
    {
        return $this->update($request, $exercise, $save);
    }

    public function exam(SaveAssessmentPrerequisitesRequest $request, Exam $exam, SavePrerequisiteLessons $save): Response|RedirectResponse
    {
        return $this->update($request, $exam, $save);
    }

    private function update(SaveAssessmentPrerequisitesRequest $request, Exercise|Exam $content, SavePrerequisiteLessons $save): Response|RedirectResponse
    {
        try {
            $save->handle($content, $request->validated('prerequisite_lesson_ids'));
        } catch (ValidationException $exception) {
            throw $exception->redirectTo($request->editorUrl());
        }

        return AccountPages::html($request)
            ? redirect()->to($request->editorUrl(), 303)->with('status', 'Prerequisite lessons saved.')
            : response()->noContent();
    }
}
