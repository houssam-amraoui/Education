<?php

namespace App\Http\Requests\Admin;

use App\Support\AccountPages;
use Illuminate\Foundation\Http\FormRequest;

class AssignUserRolesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (AccountPages::html($this) && $this->boolean('roles_present')) {
            $this->mergeIfMissing(['role_ids' => []]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('assignRoles', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'role_ids' => ['present', 'array', 'list'],
            'role_ids.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', 'exists:roles,id'],
        ];
    }
}
