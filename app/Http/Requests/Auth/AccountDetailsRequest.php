<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

abstract class AccountDetailsRequest extends FormRequest
{
    protected function detailsRules(?User $user = null): array
    {
        $uniqueEmail = Rule::unique(User::class);
        if ($user !== null) {
            $uniqueEmail->ignore($user);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', $uniqueEmail],
            'password' => [
                'bail',
                ...($user === null ? [] : ['sometimes']),
                'required',
                'string',
                'confirmed',
                Password::min(8),
                function (string $attribute, string $value, Closure $fail): void {
                    if (strlen($value) > 72 || str_contains($value, "\0")) {
                        $fail('The password must be at most 72 bytes and cannot contain null bytes.');
                    }
                },
            ],
        ];
    }
}
