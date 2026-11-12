<?php

namespace App\Actions\Topics;

use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaveTopic
{
    public function handle(Topic $topic, array $metadata, array $links): Topic
    {
        return DB::transaction(function () use ($topic, $metadata, $links): Topic {
            $current = $topic->exists ? Topic::query()->lockForUpdate()->findOrFail($topic->getKey()) : new Topic;
            $current->fill($metadata)->save();

            foreach (array_keys(Topic::CONTENT) as $type) {
                if (isset($links[$type.'_ids'])) {
                    $relation = Str::plural($type);
                    $current->{$relation}()->sync($links[$type.'_ids']);
                }
            }

            return $current;
        }, 3);
    }
}
