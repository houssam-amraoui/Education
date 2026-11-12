<?php

namespace App\Models;

use App\Models\Concerns\HasCurriculumOrder;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasCurriculumOrder;

    protected $fillable = ['level_id', 'title', 'description'];

    protected $attributes = ['status' => 'draft', 'position' => null];

    protected function casts(): array
    {
        return ['level_id' => 'integer', 'status' => CurriculumStatus::class, 'position' => 'integer'];
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    #[Scope]
    protected function visibleToPublic(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), CurriculumStatus::Published)
            ->whereHas('level', fn (Builder $level) => $level->visibleToPublic());
    }
}
