<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Support\AccountPages;
use Illuminate\Foundation\Http\FormRequest;

class ListRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Role::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:built-in,custom'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return AccountPages::html($this) ? route('admin.accounts.roles.index') : parent::getRedirectUrl();
    }
}
