<?php

namespace App\Http\Requests\Admin;

use App\Support\AccessTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccessRuleFiltersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'requirement' => ['nullable', Rule::in(AccessTarget::REQUIREMENTS)],
            'type' => ['required_with:target', 'nullable', Rule::in([...array_keys(AccessTarget::MODELS), AccessTarget::FEATURE])],
            'target' => ['required_with:type', 'nullable', 'integer', 'min:0'],
        ];
    }
}
