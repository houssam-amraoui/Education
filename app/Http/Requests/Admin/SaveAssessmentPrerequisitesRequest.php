<?php

namespace App\Http\Requests\Admin;

use App\Support\AccountPages;
use Illuminate\Foundation\Http\FormRequest;

class SaveAssessmentPrerequisitesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (AccountPages::html($this) && $this->has('prerequisite_lesson_ids') && $this->input('prerequisite_lesson_ids') === null) {
            $this->merge(['prerequisite_lesson_ids' => []]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('exercise') ?? $this->route('exam'));
    }

    public function rules(): array
    {
        return [
            'prerequisite_lesson_ids' => ['present', 'array', 'list', 'max:100'],
            'prerequisite_lesson_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }

    public function editorUrl(): string
    {
        return $this->route('exercise')
            ? route('admin.curriculum.exercises.edit', $this->route('exercise'))
            : route('admin.curriculum.exams.edit', $this->route('exam'));
    }

    protected function getRedirectUrl(): string
    {
        return $this->editorUrl();
    }
}
