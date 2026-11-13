<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use App\Support\AccessTarget;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as Query;
use Illuminate\Support\Facades\DB;

class ContentAccess
{
    public function constrain(Builder $content, ?User $viewer, ?string $classification = null, bool $unlocking = false): void
    {
        $model = $content->getModel();
        $id = $model->getQualifiedKeyName();
        $type = $model->getMorphClass();
        $studentId = $this->studentId($viewer);

        $content->whereNotExists($this->unmetRules($type, $id, $studentId, $unlocking));

        if ($classification !== null) {
            $content->where(function (Builder $legacy) use ($classification, $type, $id, $studentId, $unlocking): void {
                $legacy->where($classification, 'public');
                if ($studentId === null) {
                    return;
                }

                $legacy->orWhere($classification, 'registered')
                    ->orWhere(fn (Builder $configured) => $configured->whereIn($classification, ['level', 'prerequisite'])
                        ->whereExists($this->targetRules($type, $id)->whereColumn('access_rules.requirement', $classification)))
                    ->orWhere(fn (Builder $points) => $points->where($classification, 'points')
                        ->when(! $unlocking, fn (Builder $points) => $points->whereExists($this->grant($type, $id, $studentId, 'points'))))
                    ->orWhere(fn (Builder $premium) => $premium->where($classification, 'premium')
                        ->whereExists($this->grant($type, $id, $studentId, 'premium')));
            });
        }
    }

    public function allowsAskTeacher(User $viewer, bool $unlocking = false): bool
    {
        $studentId = $this->studentId($viewer);

        return $studentId !== null
            && $this->unmetRules(AccessTarget::FEATURE, 0, $studentId, $unlocking)->doesntExist();
    }

    public function requirements(string $type, int $id, ?string $classification = null): Collection
    {
        $rules = $this->targetRules($type, $id)->where('requirement', '<>', 'public')->orderBy('id')->get()->keyBy('requirement');

        return $this->withClassification($rules, $classification);
    }

    public function outstanding(string $type, int $id, ?User $viewer, ?string $classification = null): Collection
    {
        $studentId = $this->studentId($viewer);
        $rules = $this->unmetRules($type, $id, $studentId, false)->orderBy('id')->get()->keyBy('requirement');

        if ($studentId !== null && ($classification === 'registered'
            || (in_array($classification, ['points', 'premium'], true) && $this->grant($type, $id, $studentId, $classification)->exists())
            || (in_array($classification, ['level', 'prerequisite'], true)
                && $this->targetRules($type, $id)->where('requirement', $classification)->exists()))) {
            return $rules;
        }

        return $this->withClassification($rules, $classification);
    }

    private function withClassification(Collection $rules, ?string $classification): Collection
    {
        if ($classification !== null && $classification !== 'public' && ! $rules->has($classification)) {
            $rules->put($classification, (object) ['requirement' => $classification, 'required_level_id' => null, 'required_lesson_id' => null]);
        }

        return $rules;
    }

    private function studentId(?User $viewer): ?int
    {
        return $viewer?->roles()->where('key', Role::STUDENT)->exists() ? $viewer->getKey() : null;
    }

    private function unmetRules(string $type, string|int $id, ?int $studentId, bool $unlocking): Query
    {
        return $this->targetRules($type, $id)->whereNot(function (Query $met) use ($type, $id, $studentId, $unlocking): void {
            $met->where('requirement', 'public');
            if ($studentId === null) {
                return;
            }

            $met->orWhere('requirement', 'registered')
                ->orWhere(fn (Query $level) => $level->where('requirement', 'level')->whereExists(
                    DB::table('level_user')->join('levels', 'levels.id', '=', 'level_user.level_id')
                        ->whereColumn('level_user.level_id', 'access_rules.required_level_id')
                        ->where('level_user.user_id', $studentId)->where('levels.status', CurriculumStatus::Published)
                ))
                ->orWhere(fn (Query $lesson) => $lesson->where('requirement', 'prerequisite')->whereExists(
                    DB::table('lesson_progress')
                        ->join('lessons as prerequisite', 'prerequisite.id', '=', 'lesson_progress.lesson_id')
                        ->join('categories as prerequisite_category', 'prerequisite_category.id', '=', 'prerequisite.category_id')
                        ->join('units as prerequisite_unit', 'prerequisite_unit.id', '=', 'prerequisite_category.unit_id')
                        ->join('levels as prerequisite_level', 'prerequisite_level.id', '=', 'prerequisite_unit.level_id')
                        ->whereColumn('lesson_progress.lesson_id', 'access_rules.required_lesson_id')
                        ->where('lesson_progress.student_id', $studentId)->whereNotNull('lesson_progress.completed_at')
                        ->where('prerequisite.status', CurriculumStatus::Published)
                        ->where('prerequisite_category.status', CurriculumStatus::Published)
                        ->where('prerequisite_unit.status', CurriculumStatus::Published)
                        ->where('prerequisite_level.status', CurriculumStatus::Published)
                ))
                ->orWhere(fn (Query $points) => $points->where('requirement', 'points')
                    ->when(! $unlocking, fn (Query $points) => $points->whereExists($this->grant($type, $id, $studentId, 'points'))))
                ->orWhere(fn (Query $premium) => $premium->where('requirement', 'premium')
                    ->whereExists($this->grant($type, $id, $studentId, 'premium')));
        });
    }

    private function targetRules(string $type, string|int $id): Query
    {
        return $this->target(DB::table('access_rules'), $type, $id);
    }

    private function target(Query $query, string $type, string|int $id): Query
    {
        return $query->where('target_type', $type)
            ->when(is_int($id), fn (Query $target) => $target->where('target_id', $id),
                fn (Query $target) => $target->whereColumn('target_id', $id));
    }

    private function grant(string $type, string|int $id, int $studentId, string $source): Query
    {
        $now = now()->utc()->toDateTimeString();

        $grant = DB::table('entitlements')
            ->where('entitlements.target_type', $type)
            ->when(is_int($id), fn (Query $query) => $query->where('entitlements.target_id', $id),
                fn (Query $query) => $query->whereColumn('entitlements.target_id', $id))
            ->where('entitlements.student_id', $studentId)
            ->where('entitlements.source', $source)
            ->where('entitlements.granted_at', '<=', $now)
            ->whereNull('entitlements.revoked_at')
            ->where(fn (Query $valid) => $valid->whereNull('entitlements.expires_at')->orWhere('entitlements.expires_at', '>', $now));

        if ($source === 'points') {
            $grant->join('point_unlocks', 'point_unlocks.id', '=', 'entitlements.point_unlock_id')
                ->join('point_costs', 'point_costs.id', '=', 'point_unlocks.point_cost_id')
                ->whereColumn('point_unlocks.student_id', 'entitlements.student_id')
                ->whereColumn('point_costs.target_type', 'entitlements.target_type')
                ->whereColumn('point_costs.target_id', 'entitlements.target_id');
        }

        return $grant;
    }
}
