<?php

namespace App\Http\Requests;

use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResourceFiltersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(Resource::TYPES)],
            'access' => ['nullable', Rule::in(Resource::ACCESS)],
            'level_id' => ['nullable', 'integer', 'min:1'],
            'selected' => ['sometimes', Rule::in(['1']), 'prohibits:level_id'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('library.resources.index', $this->query('selected') === '1' ? ['selected' => 1] : []);
    }
}
