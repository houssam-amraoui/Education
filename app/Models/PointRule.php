<?php

namespace App\Models;

use App\Support\RewardEvent;
use Illuminate\Database\Eloquent\Model;

class PointRule extends Model
{
    protected $fillable = [
        'name', 'event', 'points', 'enabled', 'minimum_score_percent', 'allow_retries', 'streak_days',
    ];

    protected function casts(): array
    {
        return [
            'event' => RewardEvent::class,
            'points' => 'integer',
            'enabled' => 'boolean',
            'minimum_score_percent' => 'integer',
            'allow_retries' => 'boolean',
            'streak_days' => 'integer',
        ];
    }
}
