<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    public const VISIT = 'visit';

    public const REGISTRATION = 'registration';

    public const RESOURCE_OPEN = 'resource_open';

    public const VIDEO_PLAYBACK = 'video_playback';

    public const LESSON_OPEN = 'lesson_open';

    public $timestamps = false;
}
