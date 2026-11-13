<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entitlement extends Model
{
    public $timestamps = false;

    protected $fillable = ['student_id', 'target_type', 'target_id', 'source', 'point_unlock_id', 'granted_at', 'expires_at', 'revoked_at'];

    protected function casts(): array
    {
        return [
            'granted_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    public function pointUnlock(): BelongsTo
    {
        return $this->belongsTo(PointUnlock::class);
    }
}
