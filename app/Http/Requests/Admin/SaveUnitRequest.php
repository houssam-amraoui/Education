<?php

namespace App\Http\Requests\Admin;

use App\Models\Level;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('unit') ?? Unit::class);
    }

    public function rules(): array
    {
        return [
            'level_id' => ['bail', 'required', 'integer', Rule::exists(Level::class, 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
