<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = ['student_id', 'amount', 'source_key', 'recorded_at'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'recorded_at' => 'immutable_datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
