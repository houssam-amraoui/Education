<?php

namespace App\Http\Requests\Admin;

use App\Models\PointCost;
use App\Models\Resource;
use App\Support\AccountPages;
use App\Support\RewardTarget;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePointCostRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $target = $this->input('target_id');

        if (AccountPages::html($this) && is_string($target) && preg_match('/\A([a-z_]+):([0-9]+)\z/', $target, $parts)) {
            $this->merge(['target_type' => $parts[1], 'target_id' => $parts[2]]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'points' => ['required', 'integer', 'between:1,1000000'],
            'enabled' => ['required', 'boolean'],
        ];

        if ($this->route('pointCost') instanceof PointCost) {
            return $rules + ['target_type' => ['prohibited'], 'target_id' => ['prohibited']];
        }

        $type = $this->input('target_type');
        $target = is_string($type) ? RewardTarget::tryFrom($type) : null;
        $model = $target?->model();

        return $rules + [
            'target_type' => ['required', Rule::enum(RewardTarget::class)],
            'target_id' => [
                'bail', 'required', 'integer',
                match ($target) {
                    RewardTarget::Resource => Rule::exists(Resource::class, 'id')
                        ->where(fn (Builder $query) => $query->whereIn('id', Resource::pointsClassified()->select('id'))),
                    RewardTarget::Exercise, RewardTarget::Exam => Rule::exists($model, 'id'),
                    default => 'in:0',
                },
                Rule::unique('point_costs')->where(fn (Builder $query) => $query->where('target_type', $target?->value)),
            ],
        ];
    }
}
