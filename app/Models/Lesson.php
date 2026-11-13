<?php

namespace App\Models;

use App\Models\Concerns\HasCurriculumOrder;
use App\Policies\ContentAccess;
use App\Support\CurriculumStatus;
use App\Support\LessonRichText;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasCurriculumOrder;

    protected $fillable = ['title', 'body'];

    protected $attributes = ['status' => 'draft', 'position' => null];

    protected function casts(): array
    {
        return ['category_id' => 'integer', 'status' => CurriculumStatus::class, 'position' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function nextLessons(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'lesson_next_lesson', 'lesson_id', 'next_lesson_id');
    }

    public function prerequisiteLessons(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'lesson_prerequisite_lesson', 'lesson_id', 'prerequisite_lesson_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
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
        $query->where($this->qualifyColumn('status'), CurriculumStatus::Published)
            ->whereHas('category', fn (Builder $category) => $category->publishedInCurriculum());
    }

    #[Scope]
    protected function visibleToPublic(Builder $query): void
    {
        $query->visibleTo();
    }

    #[Scope]
    protected function visibleTo(Builder $query, ?User $user = null): void
    {
        $query->where($this->qualifyColumn('status'), CurriculumStatus::Published);
        $query->whereHas('category', fn (Builder $parent) => $parent->visibleTo($user));
        app(ContentAccess::class)->constrain($query, $user);
    }

    #[Scope]
    protected function withVisibleNextLessons(Builder $query, ?User $user = null): void
    {
        $query->with([
            'nextLessons' => fn (BelongsToMany $links) => $links->visibleTo($user)->ordered()->with('category.unit.level'),
        ]);
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn (?string $html): ?string => $html === null ? null : LessonRichText::clean($html),
        );
    }
}
