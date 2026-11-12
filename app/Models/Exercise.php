<?php

namespace App\Models;

use App\Models\Concerns\HasCurriculumOrder;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    use HasCurriculumOrder;

    protected $fillable = ['unit_id', 'title', 'instructions'];

    protected $attributes = ['status' => 'draft', 'position' => null];

    protected function casts(): array
    {
        return ['unit_id' => 'integer', 'status' => CurriculumStatus::class, 'position' => 'integer'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function prerequisiteLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'exercise_prerequisite_lesson', 'exercise_id', 'prerequisite_lesson_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExerciseAttempt::class);
    }

    #[Scope]
    protected function visibleToPublic(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), CurriculumStatus::Published)
            ->whereHas('unit', fn (Builder $unit) => $unit->visibleToPublic());
    }
}
