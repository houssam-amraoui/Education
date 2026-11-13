<?php

namespace App\Queries;

use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\PointCost;
use App\Models\PointUnlock;
use App\Models\User;
use App\Policies\ContentAccess;
use App\Support\AccessTarget;
use App\Support\CurriculumStatus;
use App\Support\RewardTarget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AccessDestination
{
    public function __construct(private ContentAccess $access) {}

    public function find(string $type, int $id, ?User $viewer): ?Model
    {
        $model = AccessTarget::MODELS[$type] ?? null;
        if ($model === null) {
            return null;
        }

        $query = $model::query()->where('status', CurriculumStatus::Published);
        match ($type) {
            'lesson' => $query->whereHas('category', fn ($category) => $category->publishedInCurriculum())->with('category.unit.level'),
            'exercise', 'exam' => $query->whereHas('unit', fn ($unit) => $unit->publishedInCurriculum())->with('unit.level'),
            'resource', 'video' => $query->where(fn ($records) => $records->doesntHave('levels')
                ->orWhereHas('levels', fn ($levels) => $levels->visibleTo($viewer))),
            default => null,
        };
        $record = $query->find($id);

        if ($type === 'resource' && $record?->stored_file_id === null) {
            return null;
        }

        if ($record instanceof Exam && (($record->available_from?->isFuture() ?? false)
            || ($record->available_until !== null && ! $record->available_until->isFuture())
            || ($record->type === Exam::DOCUMENT && (! $record->exam_file_id || ! $record->correction_file_id)))) {
            return null;
        }

        return $record;
    }

    public function allows(string $type, int $id, ?User $viewer, ?Model $record): bool
    {
        return $type === AccessTarget::FEATURE
            ? $id === 0 && $viewer !== null && $this->access->allowsAskTeacher($viewer)
            : $record !== null && $record->newQuery()->visibleTo($viewer)->whereKey($id)->exists();
    }

    public function outstanding(string $type, int $id, ?User $viewer, ?Model $record): Collection
    {
        $rules = $this->access->outstanding($type, $id, $viewer, $record?->access);
        $level = $this->ancestorLevel($type, $record);
        if ($level !== null) {
            $rules = $rules->concat($this->access->outstanding('level', $level->id, $viewer));
        }

        return $rules->values();
    }

    public function labels(Model $record): Collection
    {
        if (! isset(AccessTarget::MODELS[$record->getMorphClass()])) {
            return collect();
        }

        $type = $record->getMorphClass();
        $rules = $this->access->requirements($type, $record->id, $record->access);
        $level = $this->ancestorLevel($type, $record);
        if ($level !== null) {
            $rules = $rules->concat($this->access->requirements('level', $level->id));
        }

        return $rules->pluck('requirement')->unique()->values();
    }

    public function cost(string $type, int $id, User $student, Collection $outstanding): ?PointCost
    {
        $target = RewardTarget::tryFrom($type);
        if ($target === null || ! $outstanding->contains('requirement', 'points') || ! $target->canUnlock($student, $id)) {
            return null;
        }

        return PointCost::where('target_type', $type)->where('target_id', $id)->where('enabled', true)->where('points', '>', 0)
            ->whereNotIn('id', PointUnlock::where('student_id', $student->id)->select('point_cost_id'))->first();
    }

    public function prerequisite(int $id, ?User $viewer): ?Lesson
    {
        return Lesson::visibleTo($viewer)->with('category.unit.level')->find($id);
    }

    private function ancestorLevel(string $type, ?Model $record): ?Level
    {
        return match ($type) {
            'lesson' => $record?->category->unit->level,
            'exercise', 'exam' => $record?->unit->level,
            default => null,
        };
    }
}
