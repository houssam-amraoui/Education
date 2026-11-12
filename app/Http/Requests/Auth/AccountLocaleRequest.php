<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountLocaleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(array_keys(config('app.supported_locales')))],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
