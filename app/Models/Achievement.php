<?php

namespace App\Models;

use App\Support\RewardEvent;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = ['name', 'description', 'event', 'required_count', 'minimum_score_percent', 'reward_points', 'enabled'];

    protected function casts(): array
    {
        return [
            'event' => RewardEvent::class,
            'required_count' => 'integer',
            'minimum_score_percent' => 'integer',
            'reward_points' => 'integer',
            'enabled' => 'boolean',
        ];
    }
}
