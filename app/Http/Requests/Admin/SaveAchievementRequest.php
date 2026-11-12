<?php

namespace App\Http\Requests\Admin;

use App\Support\RewardEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAchievementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:4000'],
            'event' => ['required', Rule::enum(RewardEvent::class)],
            'required_count' => ['required', 'integer', 'between:1,3660'],
            'minimum_score_percent' => [
                Rule::prohibitedIf($this->input('event') !== RewardEvent::ExerciseResult->value),
                'nullable', 'integer', 'between:0,100',
            ],
            'reward_points' => ['sometimes', 'required', 'integer', 'between:0,1000000'],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
