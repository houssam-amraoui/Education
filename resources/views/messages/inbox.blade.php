@extends('prototype.layout')

@section('content')
    @php
        $teacher = $area === 'admin';
        $inbox = $url($teacher ? 'A31' : 'S13');
    @endphp
    @include('curriculum.heading')
    <div class="row between wrap">
        <p>{{ $teacher ? 'Questions and replies from your students.' : 'A little guidance can make a big difference.' }}</p>
        @unless($teacher)
            <x-prototype.link :href="$url('S14')" variant="primary">Ask your teacher</x-prototype.link>
        @endunless
    </div>
    <form class="filter-bar" method="get" action="{{ $inbox }}" data-live-form>
        <div class="field grow">
            <x-prototype.field label="Find a conversation" name="search" type="search"
                :value="$search ?? ''" :error="$errors->first('search')" maxlength="120"
                :placeholder="$teacher ? 'Search by subject or name' : 'Search by subject'"/>
        </div>
        <button class="button button-secondary" data-pending="Searching…">Search</button>
    </form>
    @include('curriculum.feedback')
    <div class="stack">
        @forelse($threads as $thread)
            @php($latest = $teacher ? $thread->latestMessage : ($thread->latestTeacherReply ?? $thread->latestMessage))
            <x-prototype.card>
                <div class="row between wrap">
                    <div class="row">
                        <span class="avatar" aria-hidden="true">{{ $teacher ? Str::initials($thread->student->name) : 'TE' }}</span>
                        <div>
                            <h2 class="card-title [overflow-wrap:anywhere]" dir="auto">{{ $thread->subject }}</h2>
                            <small><bdi>{{ $teacher ? $thread->student->name.' · Student' : 'Your English teacher' }}</bdi></small>
                        </div>
                    </div>
                    @if($latest)
                        <x-prototype.badge :tone="$latest->from_teacher ? 'info' : 'neutral'">
                            {{ $latest->from_teacher ? 'Teacher reply' : 'Student message' }} · {{ $latest->created_at->diffForHumans() }}
                        </x-prototype.badge>
                    @endif
                </div>
                @if($latest)
                    <p class="[overflow-wrap:anywhere]" dir="auto">{{ Str::limit($latest->body, 160) }}</p>
                @endif
                <x-prototype.link :href="route($teacher ? 'admin.messages.show' : 'account.messages.show', $thread)">
                    Open conversation <x-prototype.icon name="arrow"/>
                </x-prototype.link>
            </x-prototype.card>
        @empty
            <div class="empty-state">
                @if($search)
                    <h2>No matching conversations</h2>
                    <p>{{ $teacher ? 'Try another subject or name.' : 'Try another subject.' }}</p>
                    <x-prototype.link :href="$inbox">Reset search</x-prototype.link>
                @elseif($threads->total() === 0)
                    <h2>No conversations yet</h2>
                    <p>{{ $teacher ? 'Student questions will appear here.' : 'Ask your teacher to start a conversation.' }}</p>
                @else
                    <h2>No conversations on this page</h2>
                    <x-prototype.link :href="$inbox">Return to conversations</x-prototype.link>
                @endif
            </div>
        @endforelse
        @include('messages.pagination', ['page' => $threads])
    </div>
@endsection
