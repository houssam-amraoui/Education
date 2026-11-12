<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAchievement extends Model
{
    public $timestamps = false;

    protected $fillable = ['student_id', 'achievement_id', 'source_key', 'awarded_at'];

    protected function casts(): array
    {
        return ['awarded_at' => 'immutable_datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }
}
