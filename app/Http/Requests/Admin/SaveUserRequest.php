<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Auth\AccountDetailsRequest;
use App\Models\User;
use App\Support\AccountPages;

class SaveUserRequest extends AccountDetailsRequest
{
    protected function prepareForValidation(): void
    {
        if (AccountPages::html($this) && $this->route('user') && $this->input('password') === null && $this->input('password_confirmation') === null) {
            $this->request->remove('password');
        }
    }

    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user === null
            ? $this->user()->can('create', User::class)
            : $this->user()->can('update', $user);
    }

    public function rules(): array
    {
        return $this->detailsRules($this->route('user'));
    }
}
