<?php

namespace App\Http\Requests\Admin;

use App\Models\Resource;
use App\Support\CurriculumStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListResourcesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', Resource::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(Resource::TYPES)],
            'status' => ['nullable', Rule::enum(CurriculumStatus::class)],
            'level_id' => ['bail', 'nullable', 'integer', 'exists:levels,id'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.library.resources.index');
    }
}
