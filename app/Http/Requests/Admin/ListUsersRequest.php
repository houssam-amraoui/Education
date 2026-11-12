<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\AccountPages;
use Illuminate\Foundation\Http\FormRequest;

class ListUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'membership' => ['nullable', 'in:assigned,unassigned'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return AccountPages::html($this) ? route('admin.accounts.users.index') : parent::getRedirectUrl();
    }
}
