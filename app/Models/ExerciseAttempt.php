<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExerciseAttempt extends Model
{
    public $timestamps = false;

    public function summary(): array
    {
        return [
            'id' => $this->id,
            'exercise_id' => $this->exercise_id,
            'status' => $this->submitted_at === null ? 'in_progress' : 'completed',
            'started_at' => $this->started_at,
            'submitted_at' => $this->submitted_at,
            'possible_points' => $this->possible_points,
            'awarded_points' => $this->awarded_points,
        ];
    }

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'started_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'possible_points' => 'integer',
            'awarded_points' => 'integer',
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExerciseAttemptItem::class)->orderBy('position');
    }
}
