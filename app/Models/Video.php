<?php

namespace App\Models;

use App\Models\Concerns\HasCurriculumOrder;
use App\Policies\ContentAccess;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Video extends Model
{
    use HasCurriculumOrder;

    public const ACCESS = ['public', 'registered', 'level', 'prerequisite', 'points', 'premium'];

    protected $fillable = ['title', 'description', 'access'];

    protected $attributes = ['access' => 'public', 'status' => 'draft', 'position' => null];

    protected function casts(): array
    {
        return ['status' => CurriculumStatus::class, 'position' => 'integer'];
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class);
    }

    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(Level::class);
    }

    public function youtubeUrl(bool $embed = false): ?string
    {
        if ($this->provider !== 'youtube' || ! preg_match('/\A[A-Za-z0-9_-]{11}\z/', $this->provider_id ?? '')) {
            return null;
        }

        return ($embed ? 'https://www.youtube-nocookie.com/embed/' : 'https://www.youtube.com/watch?v=').$this->provider_id;
    }

    #[Scope]
    protected function visibleTo(Builder $query, ?User $user): void
    {
        $query->where($this->qualifyColumn('status'), CurriculumStatus::Published)
            ->where(fn (Builder $placement) => $placement->doesntHave('levels')
                ->orWhereHas('levels', fn (Builder $levels) => $levels->visibleTo($user)));
        app(ContentAccess::class)->constrain($query, $user, $this->qualifyColumn('access'));
    }

    #[Scope]
    protected function matching(Builder $query, array $filters, ?User $viewer = null): void
    {
        $search = $filters['search'] ?? null;
        $level = $filters['level_id'] ?? null;
        $access = $filters['access'] ?? null;

        $query->when($search !== null && $search !== '', fn (Builder $videos) => $videos->where(
            fn (Builder $matches) => $matches->whereLike($this->qualifyColumn('title'), '%'.$search.'%')
                ->orWhereLike($this->qualifyColumn('description'), '%'.$search.'%')
        ))
            ->when($level !== null, fn (Builder $videos) => $videos->whereHas(
                'levels', fn (Builder $levels) => $levels->visibleTo($viewer)->whereKey($level)
            ))
            ->when($access !== null, fn (Builder $videos) => $videos->where($this->qualifyColumn('access'), $access));
    }
}
