<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPointsUnlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageLearningProfile', $this->user());
    }

    public function rules(): array
    {
        return ['points' => ['required', 'integer', 'min:1']];
    }

    protected function getRedirectUrl(): string
    {
        return route('access.unlock.show', $this->route()->parameters());
    }
}
