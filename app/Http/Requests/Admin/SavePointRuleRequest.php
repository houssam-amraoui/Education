<?php

namespace App\Http\Requests\Admin;

use App\Support\RewardEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePointRuleRequest extends FormRequest
{
    public function rules(): array
    {
        $event = $this->input('event');
        $assessment = in_array($event, [RewardEvent::ExerciseResult->value, RewardEvent::ExamCompletion->value], true);

        return [
            'name' => ['required', 'string', 'max:255'],
            'event' => ['required', Rule::enum(RewardEvent::class)],
            'points' => ['required', 'integer', 'between:1,1000000'],
            'enabled' => ['required', 'boolean'],
            'minimum_score_percent' => [
                Rule::prohibitedIf($event !== RewardEvent::ExerciseResult->value),
                'nullable', 'integer', 'between:0,100',
            ],
            'allow_retries' => [
                Rule::requiredIf($assessment),
                Rule::prohibitedIf(! $assessment),
                'nullable', 'boolean',
            ],
            'streak_days' => [
                Rule::requiredIf($event === RewardEvent::StreakMilestone->value),
                Rule::prohibitedIf($event !== RewardEvent::StreakMilestone->value),
                'nullable', 'integer', 'between:1,3660',
            ],
        ];
    }

    public function attributesForSave(): array
    {
        return ['allow_retries' => $this->validated('allow_retries') ?? false] + $this->validated() + [
            'minimum_score_percent' => null,
            'streak_days' => null,
        ];
    }
}
