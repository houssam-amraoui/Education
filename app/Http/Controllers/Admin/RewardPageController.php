<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\RewardFiltersRequest;
use App\Models\Achievement;
use App\Models\PointCost;
use App\Models\PointRule;
use App\Support\AccountPages;
use App\Support\RewardEvent;
use App\Support\RewardTarget;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RewardPageController
{
    public function points(RewardFiltersRequest $request): View
    {
        $rules = DB::table('point_rules')->select('id', 'enabled', 'points', 'name', 'event')->selectRaw("'rule' AS kind");
        $costs = DB::table('point_costs')->select('point_costs.id', 'point_costs.enabled', 'point_costs.points')
            ->selectRaw("COALESCE(exercises.title, exams.title, resources.title, CASE WHEN target_type = 'ask_teacher' THEN 'Ask Teacher' ELSE 'Unavailable content' END) AS name")
            ->addSelect('point_costs.target_type AS event')->selectRaw("'cost' AS kind");

        foreach (['exercise', 'exam', 'resource'] as $type) {
            $table = $type.'s';
            $costs->leftJoin($table, fn ($join) => $join->on($table.'.id', '=', 'point_costs.target_id')
                ->where('point_costs.target_type', $type));
        }

        return $this->listing($request, 'A33', DB::query()->fromSub($rules->unionAll($costs), 'reward_records'));
    }

    public function achievements(RewardFiltersRequest $request): View
    {
        return $this->listing($request, 'A35', DB::table('achievements'));
    }

    public function rule(?PointRule $pointRule = null): View
    {
        return $this->editor('A34', 'rule', $pointRule);
    }

    public function streak(?PointRule $pointRule = null): View
    {
        abort_if($pointRule?->exists && $pointRule->event !== RewardEvent::StreakMilestone, 404);

        return $this->editor('A37', 'rule', $pointRule, [
            'milestones' => PointRule::query()->where('event', RewardEvent::StreakMilestone)
                ->orderBy('streak_days')->orderBy('id')->get(),
        ]);
    }

    public function achievement(?Achievement $achievement = null): View
    {
        return $this->editor('A36', 'achievement', $achievement);
    }

    public function cost(?PointCost $pointCost = null): View
    {
        $targets = [];
        if (! $pointCost?->exists) {
            foreach (RewardTarget::cases() as $type) {
                $model = $type->model();
                $targets[$type->label()] = $model
                      ? $model::query()->when($type === RewardTarget::Resource, fn ($query) => $query->where(fn ($resources) => $resources
                          ->where('access', 'points')->orWhereExists(\App\Models\AccessRule::where('target_type', 'resource')
                              ->where('requirement', 'points')->whereColumn('target_id', 'resources.id')->toBase())))
                        ->orderBy('title')->pluck('title', 'id')->mapWithKeys(fn ($title, $id) => [$type->value.':'.$id => $title])->all()
                    : [$type->value.':0' => $type->label()];
            }
        }

        $targetName = null;
        if ($pointCost?->exists) {
            $model = $pointCost->target_type->model();
            $targetName = $model
                ? ($model::query()->whereKey($pointCost->target_id)->value('title') ?? 'Unavailable content')
                : $pointCost->target_type->label();
        }

        return $this->editor('A34', 'cost', $pointCost, compact('targets', 'targetName'));
    }

    private function listing(RewardFiltersRequest $request, string $id, Builder $query): View
    {
        $search = $request->validated('search');
        $status = $request->validated('status');
        $records = $query->when($search !== null, fn (Builder $rows) => $rows->whereLike('name', '%'.$search.'%'))
            ->when($status !== null, fn (Builder $rows) => $rows->where('enabled', $status === 'enabled'))
            ->orderBy('name')->orderBy('id');

        if ($id === 'A33') {
            $records->orderBy('kind');
        }

        return AccountPages::view($id, 'list', [
            'records' => $records->paginate(12)->appends($request->validated()),
            'search' => $search, 'status' => $status,
            'crumbs' => $id === 'A33' ? ['A01'] : ['A01', 'A33'],
        ], 'rewards.admin-list');
    }

    private function editor(string $id, string $kind, PointRule|PointCost|Achievement|null $record, array $data = []): View
    {
        $parent = $kind === 'achievement' ? 'A35' : 'A33';

        return AccountPages::view($id, 'editor', $data + [
            'record' => $record?->exists ? $record : null,
            'kind' => $kind,
            'parent' => $parent,
            'crumbs' => $parent === 'A35' ? ['A01', 'A33', 'A35'] : ['A01', 'A33'],
        ], 'rewards.admin-editor');
    }
}
