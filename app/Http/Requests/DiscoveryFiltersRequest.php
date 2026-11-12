<?php

namespace App\Http\Requests;

use App\Queries\GlobalSearch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiscoveryFiltersRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];

        return $this->routeIs('curriculum.search')
            ? $rules + ['type' => ['nullable', Rule::in(GlobalSearch::TYPES)]]
            : $rules + [
                'level_id' => ['nullable', 'integer', 'min:1'],
                'access' => ['nullable', Rule::in(['public'])],
            ];
    }

    protected function getRedirectUrl(): string
    {
        return route($this->route()->getName(), $this->route()->parameters());
    }
}
