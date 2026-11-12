<?php

namespace App\Http\Requests;

use App\Models\Exam;
use App\Models\Level;
use App\Support\CurriculumStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurriculumFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->routeIs('admin.curriculum.*') || $this->user()->can('manage', Level::class);
    }

    public function rules(): array
    {
        $rules = ['search' => ['nullable', 'string', 'max:255']];

        if ($this->routeIs('admin.curriculum.*')) {
            $rules['status'] = ['nullable', Rule::enum(CurriculumStatus::class)];
        }

        if ($this->routeIs('admin.curriculum.units.index', 'admin.curriculum.units.create', 'curriculum.exercises.index', 'curriculum.exams.index')) {
            $rules['level_id'] = ['nullable', 'integer', 'exists:levels,id'];
        }

        if ($this->routeIs('curriculum.exams.index')) {
            $rules['type'] = ['nullable', Rule::in(Exam::TYPES)];
        }

        if ($this->routeIs('curriculum.exercises.index')) {
            $rules['access'] = ['nullable', Rule::in(['free'])];
        }

        if ($this->routeIs('admin.curriculum.categories.index', 'admin.curriculum.categories.create')) {
            $rules['unit_id'] = ['nullable', 'integer', 'exists:units,id'];
        }

        if ($this->routeIs('admin.curriculum.lessons.index')) {
            $rules['category_id'] = ['nullable', 'integer', 'exists:categories,id'];
        }

        return $rules;
    }

    protected function getRedirectUrl(): string
    {
        return route($this->route()->getName(), $this->route()->parameters());
    }
}
