<?php

namespace App\Http\Requests\Admin;

use App\Models\AccessRule;
use App\Support\AccountPages;
use App\Support\AccessTarget;
use App\Support\RewardTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveAccessRuleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $target = $this->input('target');
        if (AccountPages::html($this) && is_string($target) && preg_match('/\A([a-z_]+):([0-9]+)\z/', $target, $parts)) {
            $this->merge(['target_type' => $parts[1], 'target_id' => $parts[2]]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('access-rules.manage');
    }

    public function rules(): array
    {
        return [
            'target_type' => ['required', Rule::in([...array_keys(AccessTarget::MODELS), AccessTarget::FEATURE])],
            'target_id' => ['required', 'integer', 'min:0'],
            'requirement' => ['required', Rule::in(AccessTarget::REQUIREMENTS)],
            'required_level_id' => ['required_if:requirement,level', 'prohibited_unless:requirement,level', 'nullable', 'integer', 'exists:levels,id'],
            'required_lesson_id' => ['required_if:requirement,prerequisite', 'prohibited_unless:requirement,prerequisite', 'nullable', 'integer', 'exists:lessons,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $type = $this->input('target_type');
            $id = (int) $this->input('target_id');
            $requirement = $this->input('requirement');
            $model = AccessTarget::MODELS[$type] ?? null;
            if ($model === null ? $id !== 0 : ! $model::whereKey($id)->exists()) {
                $validator->errors()->add('target_id', __('Select an existing access target.'));
            }

            if ($requirement === 'points' && RewardTarget::tryFrom($type) === null) {
                $validator->errors()->add('requirement', __('This target does not support points unlocks.'));
            }

            if ($type === 'lesson' && $requirement === 'prerequisite' && $id === (int) $this->input('required_lesson_id')) {
                $validator->errors()->add('required_lesson_id', __('A lesson cannot require its own completion.'));
            }

            $rule = $this->route('accessRule');
            if (AccessRule::where(['target_type' => $type, 'target_id' => $id, 'requirement' => $requirement])
                ->when($rule, fn ($rules) => $rules->whereKeyNot($rule->getKey()))->exists()) {
                $validator->errors()->add('requirement', __('This target already has that requirement.'));
            }
        }];
    }
}
