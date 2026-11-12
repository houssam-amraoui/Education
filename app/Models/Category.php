<?php

namespace App\Models;

use App\Models\Concerns\HasCurriculumOrder;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasCurriculumOrder;

    protected $fillable = ['unit_id', 'title', 'description'];

    protected $attributes = ['status' => 'published', 'position' => null];

    protected function casts(): array
    {
        return ['unit_id' => 'integer', 'status' => CurriculumStatus::class, 'position' => 'integer'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    #[Scope]
    protected function visibleToPublic(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), CurriculumStatus::Published)
            ->whereHas('unit', fn (Builder $unit) => $unit->visibleToPublic());
    }
}
