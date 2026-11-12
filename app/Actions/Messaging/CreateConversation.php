<?php

namespace App\Actions\Messaging;

use App\Models\Conversation;
use App\Models\User;
use App\Queries\ConversationSource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateConversation
{
    public function __construct(private ConversationSource $sources) {}

    public static function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
            'recipient_id' => ['missing'],
            'student_id' => ['missing'],
            'sender_id' => ['missing'],
            'source_type' => ['missing'],
            'source_id' => ['missing'],
            'source_title' => ['missing'],
        ];
    }

    /**
     * Source coordinates come from the source entry point, separately from the message form.
     */
    public function handle(User $student, array $input, ?string $sourceType = null, ?int $sourceId = null): Conversation
    {
        Gate::forUser($student)->authorize('create', Conversation::class);

        $message = Validator::make($input, self::rules())->validate();

        $context = Validator::make(['type' => $sourceType, 'id' => $sourceId], [
            'type' => ['required_with:id', 'nullable', Rule::in(array_keys(Conversation::SOURCE_TYPES))],
            'id' => ['required_with:type', 'nullable', 'integer', 'min:1'],
        ])->validate();

        return DB::transaction(function () use ($student, $message, $context): Conversation {
            $source = null;
            if ($context['type'] !== null) {
                $source = $this->sources->find($student, $context['type'], $context['id'])
                    ?? throw (new ModelNotFoundException)->setModel(Conversation::SOURCE_TYPES[$context['type']], [$context['id']]);
            }

            $conversation = Conversation::forceCreate([
                'student_id' => $student->getKey(),
                'subject' => trim($message['subject']),
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'source_title' => $source?->title,
                'last_message_at' => now(),
            ]);

            $conversation->messages()->forceCreate([
                'sender_id' => $student->getKey(),
                'from_teacher' => false,
                'body' => trim($message['message']),
            ]);

            return $conversation;
        }, 3);
    }
}
