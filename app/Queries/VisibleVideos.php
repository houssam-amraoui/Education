<?php

namespace App\Queries;

use App\Models\User;
use App\Models\Video;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VisibleVideos
{
    /** @param array{search?: string, level_id?: int, access?: string} $filters */
    public function paginate(?User $user, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return Video::visibleTo($user)->matching($filters)->ordered()->paginate($perPage)->appends($filters);
    }

    public function findOrFail(?User $user, int $videoId): Video
    {
        return Video::visibleTo($user)->findOrFail($videoId);
    }
}
