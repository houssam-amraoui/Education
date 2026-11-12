<?php

namespace App\Queries;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ConversationSource
{
    public function find(User $actor, string $type, int $id, array $relations = []): ?Model
    {
        $model = Conversation::SOURCE_TYPES[$type] ?? null;
        if ($model === null) {
            return null;
        }

        $query = $model::query();

        return (match ($type) {
            'lesson', 'exercise' => $query->visibleToPublic(),
            'exam', 'resource', 'video' => $query->visibleTo($actor),
        })->with($relations)->find($id);
    }
}
