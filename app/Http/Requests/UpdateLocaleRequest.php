<?php

namespace App\Http\Requests;

use App\Support\Prototype;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateLocaleRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response(__('shared.feedback.validation_summary'), 422));
    }

    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(array_keys(config('app.supported_locales')))],
            'screen' => ['required', 'string', Rule::in(array_keys(Prototype::screens()))],
            'query' => ['nullable', 'string'],
        ];
    }
}
