<?php

namespace App\Actions\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class AppendConversationMessage
{
    public static function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'max:5000'],
            'sender_id' => ['missing'],
            'recipient_id' => ['missing'],
        ];
    }

    public function followUp(User $student, int $conversationId, array $input): Message
    {
        return $this->append($student, $conversationId, $input, false);
    }

    public function reply(User $teacher, int $conversationId, array $input): Message
    {
        Gate::forUser($teacher)->authorize('manage', Conversation::class);

        return $this->append($teacher, $conversationId, $input, true);
    }

    private function append(User $actor, int $conversationId, array $input, bool $fromTeacher): Message
    {
        return DB::transaction(function () use ($actor, $conversationId, $input, $fromTeacher): Message {
            $conversation = Conversation::query()->lockForUpdate()->findOrFail($conversationId);
            Gate::forUser($actor)->authorize($fromTeacher ? 'reply' : 'followUp', $conversation);

            $validated = Validator::make($input, self::rules())->validate();

            $message = $conversation->messages()->forceCreate([
                'sender_id' => $actor->getKey(),
                'from_teacher' => $fromTeacher,
                'body' => trim($validated['reply']),
            ]);
            $conversation->forceFill(['last_message_at' => $message->created_at])->save();

            return $message;
        }, 3);
    }
}
