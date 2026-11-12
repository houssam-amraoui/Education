<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\Exam;
use App\Models\User;
use App\Queries\ConversationSource;

class ConversationPages
{
    public function __construct(private ConversationSource $sources) {}

    public function source(User $actor, ?string $type, ?int $id): ?array
    {
        if ($type === null || $id === null || ! isset(Conversation::SOURCE_TYPES[$type])) {
            return null;
        }

        $content = $this->sources->find($actor, $type, $id, LearningResult::relations($type));
        if ($content === null) {
            return null;
        }

        $result = LearningResult::from($type, $content);
        $result['url'] = $content instanceof Exam && $content->type === Exam::DOCUMENT
            ? route('curriculum.exams.document', [$id, 'paper'])
            : route($result['destination']['route'], $result['destination']['parameters']);

        return $result;
    }
}
