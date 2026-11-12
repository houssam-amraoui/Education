<?php

namespace App\Http\Requests;

use App\Support\CurriculumStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSelectedLevelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageLearningProfile', $this->user());
    }

    public function rules(): array
    {
        return [
            'level_ids' => ['sometimes', 'array', 'list'],
            'level_ids.*' => ['bail', 'required', 'integer', 'min:1', 'distinct',
                Rule::exists('levels', 'id')->where('status', CurriculumStatus::Published->value)],
        ];
    }
}
