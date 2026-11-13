<?php

namespace App\Models;

use App\Policies\ContentAccess;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Resource extends Model
{
    public const TYPES = ['pdf', 'book', 'worksheet', 'study-guide', 'other'];

    public const ACCESS = ['public', 'registered', 'level', 'prerequisite', 'points', 'premium'];

    protected $fillable = ['title', 'description', 'type', 'access'];

    protected $attributes = ['access' => 'public', 'status' => 'draft'];

    protected function casts(): array
    {
        return ['status' => CurriculumStatus::class];
    }

    public function storedFile(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class);
    }

    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(Level::class);
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class);
    }

    #[Scope]
    protected function matching(Builder $query, array $filters): void
    {
        $search = $filters['search'] ?? null;
        $level = $filters['level_id'] ?? null;
        $query->when($search !== null, fn (Builder $items) => $items->where(
            fn (Builder $matches) => $matches->whereLike('title', '%'.$search.'%')
                ->orWhereLike('description', '%'.$search.'%')
        ))
            ->when(isset($filters['type']), fn (Builder $items) => $items->where('type', $filters['type']))
            ->when(isset($filters['status']), fn (Builder $items) => $items->where('status', $filters['status']))
            ->when(isset($filters['access']), fn (Builder $items) => $items->where('access', $filters['access']))
            ->when($level !== null, fn (Builder $items) => $items->whereHas('levels', fn (Builder $levels) => $levels->whereKey($level)))
            ->orderBy('title')->orderBy('id');
    }

    #[Scope]
    protected function visibleTo(Builder $query, ?User $user, bool $unlocking = false): void
    {
        $query->publishedForDelivery($user);
        app(ContentAccess::class)->constrain($query, $user, $this->qualifyColumn('access'), $unlocking);
    }

    #[Scope]
    protected function publishedForDelivery(Builder $query, ?User $user = null): void
    {
        $query->where($this->qualifyColumn('status'), CurriculumStatus::Published)
            ->whereNotNull($this->qualifyColumn('stored_file_id'))
            ->where(fn (Builder $placements) => $placements->doesntHave('levels')
                ->orWhereHas('levels', fn (Builder $levels) => $levels->visibleTo($user)));
    }
}
