<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccessRule extends Model
{
    protected $fillable = ['target_type', 'target_id', 'requirement', 'required_level_id', 'required_lesson_id'];

    public function target(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'target_type', 'target_id');
    }
}
