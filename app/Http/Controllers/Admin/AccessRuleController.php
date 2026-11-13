<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AccessRuleFiltersRequest;
use App\Http\Requests\Admin\SaveAccessRuleRequest;
use App\Models\AccessRule;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\PointCost;
use App\Support\AccountPages;
use App\Support\AccessTarget;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccessRuleController
{
    public function index(AccessRuleFiltersRequest $request): View
    {
        $filters = $request->validated();
        $type = $filters['type'] ?? null;
        $id = $filters['target'] ?? null;
        $search = $filters['search'] ?? null;
        $records = AccessRule::query()
            ->whereIn('target_type', [...array_keys(AccessTarget::MODELS), AccessTarget::FEATURE])
            ->when($type !== null, fn ($rules) => $rules->where('target_type', $type)->where('target_id', $id))
            ->when($filters['requirement'] ?? null, fn ($rules, $requirement) => $rules->where('requirement', $requirement))
            ->when($search !== null, function ($rules) use ($search): void {
                $rules->where(function ($matches) use ($search): void {
                    $matches->whereHasMorph('target', array_values(AccessTarget::MODELS),
                        fn ($targets) => $targets->whereLike('title', '%'.$search.'%'));
                    if (stripos('Ask Teacher', $search) !== false) {
                        $matches->orWhere('target_type', AccessTarget::FEATURE);
                    }
                });
            })->orderBy('target_type')->orderBy('target_id')->orderBy('id')->paginate(20)->appends($filters);

        $records->getCollection()->whereIn('target_type', array_keys(AccessTarget::MODELS))->load('target');
        $model = AccessTarget::MODELS[$type] ?? null;
        $targetName = $model ? $model::whereKey($id)->value('title') : ($type === AccessTarget::FEATURE && (int) $id === 0 ? 'Ask Teacher' : null);

        return AccountPages::view('A38', 'list', compact('records', 'filters', 'targetName', 'type', 'id'), 'access.admin-list');
    }

    public function create(AccessRuleFiltersRequest $request): View
    {
        $type = $request->validated('type');
        $id = $request->validated('target');
        $rule = new AccessRule(['target_type' => $type, 'target_id' => $id]);

        return $this->editor($rule);
    }

    public function edit(AccessRule $accessRule): View
    {
        return $this->editor($accessRule);
    }

    public function store(SaveAccessRuleRequest $request): JsonResponse|RedirectResponse
    {
        return $this->save($request, new AccessRule, 201);
    }

    public function update(SaveAccessRuleRequest $request, AccessRule $accessRule): JsonResponse|RedirectResponse
    {
        return $this->save($request, $accessRule, 200);
    }

    private function save(SaveAccessRuleRequest $request, AccessRule $rule, int $status): JsonResponse|RedirectResponse
    {
        try {
            $rule->fill($request->validated() + ['required_level_id' => null, 'required_lesson_id' => null])->save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['requirement' => __('This target already has that requirement.')]);
        }

        return AccountPages::html($request)
            ? redirect()->route('admin.access-rules.edit', $rule, 303)->with('status', 'Access rule saved.')
            : response()->json($rule, $status);
    }

    private function editor(AccessRule $rule): View
    {
        $targets = $this->targets();
        $target = $rule->target_type === null ? null : "{$rule->target_type}:{$rule->target_id}";
        $levels = Level::orderBy('title')->pluck('title', 'id');
        $lessons = Lesson::orderBy('title')->pluck('title', 'id');
        $cost = $rule->requirement === 'points'
            ? PointCost::where('target_type', $rule->target_type)->where('target_id', $rule->target_id)->first()
            : null;

        return AccountPages::view('A39', 'editor', compact('rule', 'targets', 'target', 'levels', 'lessons', 'cost')
            + ['parent' => 'A38'], 'access.admin-editor');
    }

    private function targets(): array
    {
        $targets = [AccessTarget::FEATURE => [0 => 'Ask Teacher']];
        foreach (AccessTarget::MODELS as $type => $model) {
            $targets[$type] = $model::orderBy('title')->pluck('title', 'id')->all();
        }

        return $targets;
    }
}
