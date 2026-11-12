<?php

namespace App\Http\Requests\Admin;

use App\Models\Topic;
use App\Support\CurriculumStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTopicsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', Topic::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(CurriculumStatus::class)],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.library.topics.index');
    }
}
