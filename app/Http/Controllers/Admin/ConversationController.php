<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Messaging\AppendConversationMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConversationFiltersRequest;
use App\Http\Requests\ReplyToConversationRequest;
use App\Queries\Conversations;
use App\Support\AccountPages;
use App\Support\ConversationPages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function __construct(private Conversations $conversations, private ConversationPages $pages) {}

    public function index(ConversationFiltersRequest $request): View
    {
        $search = $request->validated('search');
        $threads = $this->conversations->teacherInbox($request->user(), $search)->appends(['search' => $search]);

        return AccountPages::view('A31', 'inbox', [
            'parent' => 'A01', 'threads' => $threads, 'search' => $search,
        ], 'messages.inbox');
    }

    public function show(Request $request, int $conversation): View
    {
        $teacher = $request->user();
        $thread = $this->conversations->teacherThread($teacher, $conversation);

        return AccountPages::view('A32', 'editor', [
            'crumbs' => ['A01', 'A31'], 'title' => 'Reply to '.$thread->student->name, 'thread' => $thread,
            'messages' => $this->conversations->teacherMessages($teacher, $conversation),
            'context' => $this->pages->source($teacher, $thread->source_type, $thread->source_id),
        ], 'messages.thread');
    }

    public function reply(ReplyToConversationRequest $request, int $conversation, AppendConversationMessage $append): RedirectResponse
    {
        $append->reply($request->user(), $conversation, $request->validated());

        return redirect()->route('admin.messages.show', $conversation, 303)->with('status', 'Reply sent.');
    }
}
