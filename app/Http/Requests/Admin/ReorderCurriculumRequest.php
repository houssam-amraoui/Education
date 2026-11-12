<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('exam') ?? $this->route('exercise') ?? $this->route('lesson') ?? $this->route('category') ?? $this->route('unit') ?? $this->route('level') ?? $this->route('video'));
    }

    public function rules(): array
    {
        return [
            'position' => ['required', 'integer', 'min:1'],
        ];
    }
}
