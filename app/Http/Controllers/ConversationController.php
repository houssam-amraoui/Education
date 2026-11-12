<?php

namespace App\Http\Controllers;

use App\Actions\Messaging\AppendConversationMessage;
use App\Actions\Messaging\CreateConversation;
use App\Http\Requests\ConversationFiltersRequest;
use App\Http\Requests\CreateConversationRequest;
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
        $threads = $this->conversations->studentInbox($request->user(), search: $search)->appends(['search' => $search]);

        return AccountPages::view('S13', 'inbox', [
            'parent' => 'S01', 'threads' => $threads, 'search' => $search,
        ], 'messages.inbox');
    }

    public function create(Request $request, ?string $type = null, ?int $source = null): View
    {
        $context = $this->pages->source($request->user(), $type, $source);
        abort_if($type !== null && $context === null, 404);

        return AccountPages::view('S14', 'editor', [
            'parent' => 'S13', 'context' => $context,
            'submitUrl' => $type === null ? route('account.messages.store') : $request->fullUrl(),
        ], 'messages.compose');
    }

    public function store(CreateConversationRequest $request, CreateConversation $create, ?string $type = null, ?int $source = null): RedirectResponse
    {
        $thread = $create->handle($request->user(), $request->validated(), $type, $source);

        return redirect()->route('account.messages.show', $thread, 303)->with('status', 'Question sent.');
    }

    public function show(Request $request, int $conversation): View
    {
        $student = $request->user();
        $thread = $this->conversations->studentThread($student, $conversation);

        return AccountPages::view('S15', 'editor', [
            'crumbs' => ['S01', 'S13'], 'thread' => $thread,
            'messages' => $this->conversations->studentMessages($student, $conversation),
            'context' => $this->pages->source($student, $thread->source_type, $thread->source_id),
        ], 'messages.thread');
    }

    public function reply(ReplyToConversationRequest $request, int $conversation, AppendConversationMessage $append): RedirectResponse
    {
        $append->followUp($request->user(), $conversation, $request->validated());

        return redirect()->route('account.messages.show', $conversation, 303)->with('status', 'Follow-up sent.');
    }
}
