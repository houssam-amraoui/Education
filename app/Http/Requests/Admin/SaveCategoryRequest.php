<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('category') ?? Category::class);
    }

    public function rules(): array
    {
        return [
            'unit_id' => ['bail', 'required', 'integer', Rule::exists(Unit::class, 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
