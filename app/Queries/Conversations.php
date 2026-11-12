<?php

namespace App\Queries;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class Conversations
{
    public function teacherInbox(User $teacher, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        Gate::forUser($teacher)->authorize('manage', Conversation::class);
        Validator::make(['search' => $search], ['search' => ['nullable', 'string', 'max:120']])->validate();

        $search = trim($search ?? '');
        $query = Conversation::query()->with('student:id,name');
        if ($search !== '') {
            $pattern = '%'.$search.'%';
            $query->where(fn (Builder $matches) => $matches->whereLike('subject', $pattern)
                ->orWhereHas('student', fn (Builder $students) => $students->whereLike('name', $pattern)));
        }

        return $this->paginateInbox($query, $perPage);
    }

    public function teacherThread(User $teacher, int $conversationId): Conversation
    {
        Gate::forUser($teacher)->authorize('manage', Conversation::class);

        return Conversation::with('student:id,name')->findOrFail($conversationId);
    }

    public function teacherMessages(User $teacher, int $conversationId, int $perPage = 30): LengthAwarePaginator
    {
        return $this->paginateMessages($this->teacherThread($teacher, $conversationId), $perPage);
    }

    public function studentInbox(User $student, int $perPage = 20, ?string $search = null): LengthAwarePaginator
    {
        Gate::forUser($student)->authorize('create', Conversation::class);

        $query = Conversation::ownedBy($student)->with('latestTeacherReply');
        if ($search !== null && trim($search) !== '') {
            $query->whereLike('subject', '%'.trim($search).'%');
        }

        return $this->paginateInbox($query, $perPage);
    }

    public function studentThread(User $student, int $conversationId): Conversation
    {
        Gate::forUser($student)->authorize('create', Conversation::class);

        return Conversation::ownedBy($student)->findOrFail($conversationId);
    }

    public function studentMessages(User $student, int $conversationId, int $perPage = 30): LengthAwarePaginator
    {
        return $this->paginateMessages($this->studentThread($student, $conversationId), $perPage);
    }

    public function recentTeacherReplies(User $student, int $limit = 5): Collection
    {
        Gate::forUser($student)->authorize('create', Conversation::class);

        return Message::where('from_teacher', true)
            ->whereIn('conversation_id', Conversation::ownedBy($student)->select('id'))
            ->with(['conversation:id,subject', 'sender:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    private function paginateInbox(Builder $query, int $perPage): LengthAwarePaginator
    {
        return $query->with('latestMessage')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    private function paginateMessages(Conversation $conversation, int $perPage): LengthAwarePaginator
    {
        $page = $conversation->messages()->with('sender:id,name')->orderByDesc('id')->paginate($perPage);

        return $page->setCollection($page->getCollection()->reverse()->values());
    }
}
