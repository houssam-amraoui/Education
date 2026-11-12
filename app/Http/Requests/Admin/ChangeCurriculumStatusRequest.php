<?php

namespace App\Http\Requests\Admin;

use App\Support\CurriculumStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeCurriculumStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('lesson') ?? $this->route('unit') ?? $this->route('level') ?? $this->route('resource'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(CurriculumStatus::class)],
        ];
    }
}
