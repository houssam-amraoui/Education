<?php

namespace App\Models;

use App\Models\Concerns\HasCurriculumOrder;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasCurriculumOrder;

    public const DOCUMENT = 'document';

    public const INTERACTIVE = 'interactive';

    public const ACCESS = ['public', 'registered'];

    protected $fillable = ['unit_id', 'type', 'title', 'instructions', 'access', 'available_from', 'available_until', 'duration_minutes'];

    protected $attributes = ['access' => 'public', 'status' => 'draft', 'position' => null];

    protected function casts(): array
    {
        return [
            'unit_id' => 'integer',
            'position' => 'integer',
            'duration_minutes' => 'integer',
            'status' => CurriculumStatus::class,
            'available_from' => 'immutable_datetime',
            'available_until' => 'immutable_datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function examFile(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class);
    }

    public function correctionFile(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class);
    }

    #[Scope]
    protected function visibleTo(Builder $query, ?User $user): void
    {
        $now = now()->utc()->toDateTimeString();

        $query->where('status', CurriculumStatus::Published)
            ->whereIn('access', $user ? self::ACCESS : ['public'])
            ->whereHas('unit', fn (Builder $unit) => $unit->visibleToPublic())
            ->where(fn (Builder $start) => $start->whereNull('available_from')->orWhere('available_from', '<=', $now))
            ->where(fn (Builder $end) => $end->whereNull('available_until')->orWhere('available_until', '>', $now))
            ->where(fn (Builder $configured) => $configured->where('type', self::INTERACTIVE)
                ->orWhere(fn (Builder $document) => $document->where('type', self::DOCUMENT)
                    ->whereNotNull('exam_file_id')->whereNotNull('correction_file_id')));
    }
}
