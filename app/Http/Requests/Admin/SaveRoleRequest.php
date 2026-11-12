<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Support\Ability;
use App\Support\AccountPages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRoleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (AccountPages::html($this) && $this->boolean('abilities_present')) {
            $this->mergeIfMissing(['abilities' => []]);
        }
    }

    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role === null
            ? $this->user()->can('create', Role::class)
            : $this->user()->can('update', $role);
    }

    public function rules(): array
    {
        $uniqueName = Rule::unique(Role::class);
        if ($role = $this->route('role')) {
            $uniqueName->ignore($role);
        }

        return [
            'name' => ['required', 'string', 'max:255', $uniqueName],
            'description' => ['nullable', 'string', 'max:1000'],
            'abilities' => ['present', 'array', 'list', 'max:'.count(Ability::cases())],
            'abilities.*' => ['bail', 'required', 'string', 'distinct', Rule::enum(Ability::class)],
        ];
    }
}
