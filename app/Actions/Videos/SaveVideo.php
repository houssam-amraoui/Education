<?php

namespace App\Actions\Videos;

use App\Models\Video;
use App\Support\YouTubeUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveVideo
{
    public function handle(Video $video, array $metadata, ?string $sourceUrl, array $links): Video
    {
        $sourceId = $sourceUrl === null ? null : YouTubeUrl::videoId($sourceUrl);
        if ($sourceId === null && (! $video->exists || $sourceUrl !== null)) {
            throw ValidationException::withMessages(['source_url' => 'Enter a supported YouTube video URL.']);
        }

        return DB::transaction(function () use ($video, $metadata, $sourceId, $links): Video {
            $current = $video->exists ? Video::query()->lockForUpdate()->findOrFail($video->getKey()) : new Video;
            $current->fill($metadata);
            if ($sourceId !== null) {
                $current->provider = 'youtube';
                $current->provider_id = $sourceId;
            }
            $current->save();

            if (isset($links['lesson_ids'])) {
                $current->lessons()->sync($links['lesson_ids']);
            }
            if (isset($links['level_ids'])) {
                $current->levels()->sync($links['level_ids']);
            }

            return $current;
        }, 3);
    }
}
