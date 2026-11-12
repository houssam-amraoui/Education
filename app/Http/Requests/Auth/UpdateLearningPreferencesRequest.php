<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLearningPreferencesRequest extends FormRequest
{
    protected $redirectRoute = 'account.preferences.show';

    public function authorize(): bool
    {
        return $this->user()->can('manageLearningProfile', $this->user());
    }

    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(array_keys(config('app.supported_locales')))],
            'daily_study_goal_minutes' => ['required', 'integer', Rule::in([15, 30, 45])],
            'learning_focus' => ['present', 'nullable', 'string', 'max:255'],
        ];
    }
}
