<?php

namespace App\Http\Requests\Auth;

use Illuminate\Support\Arr;

class UpdateProfileRequest extends AccountDetailsRequest
{
    protected $redirectRoute = 'account.profile.show';

    public function authorize(): bool
    {
        return $this->user()->can('manageLearningProfile', $this->user());
    }

    public function rules(): array
    {
        return Arr::only($this->detailsRules($this->user()), ['name', 'email']) + [
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
