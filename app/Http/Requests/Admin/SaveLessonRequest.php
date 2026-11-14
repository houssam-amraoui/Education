<?php

namespace App\Http\Requests\Admin;

use App\Models\Resource;
use App\Models\Video;
use App\Support\AccountPages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLessonRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (AccountPages::html($this) && $this->has('video_ids') && $this->input('video_ids') === null) {
            $this->merge(['video_ids' => []]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('category'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:500000'],
            'video_ids' => ['sometimes', 'array', 'list', 'max:100'],
            'video_ids.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', Rule::exists(Video::class, 'id')],
            'resource_ids' => ['sometimes', 'array', 'list', 'max:100'],
            'resource_ids.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', Rule::exists(Resource::class, 'id')],
        ];
    }
}
