@extends('prototype.layout')

@section('content')
    @php($teacher = $area === 'admin')
    @include('curriculum.heading')
    <div class="split">
        <div class="stack">
            <div class="conversation-header">
                <h2 class="[overflow-wrap:anywhere]" dir="auto">{{ $thread->subject }}</h2>
                <p dir="auto">{{ $teacher ? $thread->student->name.' · Student' : 'Your English teacher' }}</p>
            </div>
            @forelse($messages as $message)
                <article @class(['message-bubble', 'teacher' => $message->from_teacher]) id="message-{{ $message->id }}">
                    <strong dir="auto">{{ !$teacher && $message->from_teacher ? 'Your English teacher' : $message->sender->name }}</strong>
                    <small><time datetime="{{ $message->created_at->toAtomString() }}">{{ $message->created_at->translatedFormat('j M Y · H:i') }}</time></small>
                    <p class="[overflow-wrap:anywhere] whitespace-pre-wrap" dir="auto">{{ $message->body }}</p>
                </article>
            @empty
                <div class="empty-state"><h2>No messages on this page</h2>
                    @if($messages->total())
                        <x-prototype.link :href="$messages->url(1)">View latest messages</x-prototype.link>
                    @endif
                </div>
            @endforelse
            @include('messages.pagination', ['page' => $messages, 'messageHistory' => true])
            @include('curriculum.feedback')
            @if(!$teacher || Gate::allows('reply', $thread))
                <form method="post" action="{{ route($teacher ? 'admin.messages.reply' : 'account.messages.reply', $thread) }}" class="stack" data-live-form>
                    @csrf
                    <x-prototype.field :label="$teacher ? 'Reply to '.$thread->student->name : 'Your follow-up question'"
                        name="reply" type="textarea" required maxlength="5000" dir="auto"
                        :value="is_string(old('reply')) ? old('reply') : ''" :error="$errors->first('reply')"/>
                    <button class="button button-primary" data-pending="Sending…">{{ $teacher ? 'Send reply' : 'Send follow-up' }}</button>
                </form>
            @endif
        </div>
        <x-prototype.card>
            @if($thread->source_title)
                <h3>{{ $thread->source_type === 'lesson' ? 'Attached lesson' : 'Attached learning context' }}</h3>
                @if($context && $context['context'])
                    <x-prototype.badge><bdi>{{ implode(' · ', $context['context']) }}</bdi></x-prototype.badge>
                @endif
                <h3 dir="auto">{{ $thread->source_title }}</h3>
                @if($context)
                    <p>{{ $thread->source_type === 'lesson' ? 'This question was opened from the lesson.' : 'This question was opened from the linked content.' }}</p>
                    <x-prototype.link :href="$context['url']">{{ $thread->source_type === 'lesson' ? 'Open lesson' : 'Open source content' }}</x-prototype.link>
                @else
                    <p>The original learning content is no longer available.</p>
                @endif
            @endif
            <x-prototype.link :href="$url($teacher ? 'A31' : 'S13')" variant="tertiary">Back to conversations →</x-prototype.link>
        </x-prototype.card>
    </div>
@endsection
