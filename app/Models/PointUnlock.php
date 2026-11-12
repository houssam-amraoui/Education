<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointUnlock extends Model
{
    public $timestamps = false;

    protected $fillable = ['student_id', 'point_cost_id', 'point_transaction_id', 'unlocked_at'];

    protected function casts(): array
    {
        return ['unlocked_at' => 'immutable_datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function pointCost(): BelongsTo
    {
        return $this->belongsTo(PointCost::class);
    }

    public function pointTransaction(): BelongsTo
    {
        return $this->belongsTo(PointTransaction::class);
    }
}
