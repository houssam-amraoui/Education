<?php

namespace App\Http\Requests\Admin;

use App\Support\AccountPages;
use Illuminate\Foundation\Http\FormRequest;

class SaveNextLessonsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // An unchecked checkbox group submits only the empty hidden field.
        if (AccountPages::html($this) && $this->has('next_lesson_ids') && $this->input('next_lesson_ids') === null) {
            $this->merge(['next_lesson_ids' => []]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('category'));
    }

    public function rules(): array
    {
        return [
            'next_lesson_ids' => ['present', 'array', 'list', 'max:100'],
            'next_lesson_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
