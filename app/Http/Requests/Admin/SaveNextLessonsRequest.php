<?php

namespace App\Http\Requests\Admin;

use App\Support\AccountPages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveNextLessonsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // An unchecked checkbox group submits only the empty hidden field.
        if (AccountPages::html($this)) {
            foreach (['next_lesson_ids', 'resource_ids'] as $field) {
                if ($this->has($field) && $this->input($field) === null) {
                    $this->merge([$field => []]);
                }
            }
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
            'resource_ids' => ['sometimes', 'array', 'list', 'max:100'],
            'resource_ids.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', Rule::exists('resources', 'id')],
        ];
    }
}
