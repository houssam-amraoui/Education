<?php

namespace App\Models;

use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Topic extends Model
{
    public const CONTENT = [
        'lesson' => Lesson::class,
        'exercise' => Exercise::class,
        'exam' => Exam::class,
        'resource' => Resource::class,
        'video' => Video::class,
    ];

    protected $fillable = ['title', 'description'];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return ['status' => CurriculumStatus::class];
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class);
    }

    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class);
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class);
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
    protected function published(Builder $query): void
    {
        $query->where('status', CurriculumStatus::Published);
    }
}
