<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;
use Illuminate\Auth\Access\Response;

class VideoPolicy extends CurriculumPolicy
{
    public function view(?User $user, Video $video): Response
    {
        return Video::visibleTo($user)->whereKey($video->getKey())->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
