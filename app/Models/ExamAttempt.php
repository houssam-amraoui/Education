<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'started_at' => 'immutable_datetime',
            'deadline_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'expired_at' => 'immutable_datetime',
            'possible_points' => 'integer',
            'awarded_points' => 'integer',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExamAttemptItem::class)->orderBy('position');
    }

    public function summary(): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'status' => match (true) {
                $this->submitted_at !== null => 'completed',
                $this->expired_at !== null => 'expired',
                default => 'in_progress',
            },
            'started_at' => $this->started_at,
            'deadline_at' => $this->deadline_at,
            'submitted_at' => $this->submitted_at,
            'expired_at' => $this->expired_at,
            'possible_points' => $this->possible_points,
            'awarded_points' => $this->awarded_points,
        ];
    }
}
