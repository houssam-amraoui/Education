<?php

namespace App\Http\Requests;

use App\Actions\Messaging\AppendConversationMessage;
use App\Queries\Conversations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReplyToConversationRequest extends FormRequest
{
    public function authorize(Conversations $conversations): bool
    {
        $id = (int) $this->route('conversation');
        if ($this->routeIs('admin.messages.reply')) {
            return Gate::allows('reply', $conversations->teacherThread($this->user(), $id));
        }

        $conversations->studentThread($this->user(), $id);

        return true;
    }

    public function rules(): array
    {
        return AppendConversationMessage::rules();
    }

    protected function getRedirectUrl(): string
    {
        return route($this->routeIs('admin.messages.reply') ? 'admin.messages.show' : 'account.messages.show', $this->route('conversation'));
    }
}
