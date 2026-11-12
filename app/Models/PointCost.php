<?php

namespace App\Models;

use App\Support\RewardTarget;
use Illuminate\Database\Eloquent\Model;

class PointCost extends Model
{
    protected $fillable = ['target_type', 'target_id', 'points', 'enabled'];

    protected function casts(): array
    {
        return [
            'target_type' => RewardTarget::class,
            'target_id' => 'integer',
            'points' => 'integer',
            'enabled' => 'boolean',
        ];
    }
}
