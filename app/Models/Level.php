<?php

namespace App\Models;

use App\Models\Concerns\HasCurriculumOrder;
use App\Policies\ContentAccess;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    use HasCurriculumOrder;

    protected $fillable = ['title', 'description'];

    protected $attributes = ['status' => 'draft', 'position' => null];

    protected function casts(): array
    {
        return ['status' => CurriculumStatus::class, 'position' => 'integer'];
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class);
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class);
    }

    #[Scope]
    protected function publishedInCurriculum(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), CurriculumStatus::Published);
    }

    #[Scope]
    protected function visibleToPublic(Builder $query): void
    {
        $query->visibleTo();
    }

    #[Scope]
    protected function visibleTo(Builder $query, ?User $user = null): void
    {
        $query->publishedInCurriculum();
        app(ContentAccess::class)->constrain($query, $user);
    }
}
