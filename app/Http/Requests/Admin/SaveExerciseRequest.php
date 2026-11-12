<?php

namespace App\Http\Requests\Admin;

use App\Models\Exercise;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('exercise') ?? Exercise::class);
    }

    public function rules(): array
    {
        return [
            'unit_id' => ['bail', 'required', 'integer', Rule::exists(Unit::class, 'id')],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string', 'max:10000'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        $exercise = $this->route('exercise');

        return $exercise ? route('admin.curriculum.exercises.edit', $exercise) : route('admin.curriculum.exercises.create');
    }
}
