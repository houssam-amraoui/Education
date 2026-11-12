@if($page->hasPages())
    <nav class="row wrap" aria-label="{{ ($messageHistory ?? false) ? 'Message history' : 'Conversation pagination' }}">
        @if($page->previousPageUrl())
            <x-prototype.link :href="$page->previousPageUrl()">{{ ($messageHistory ?? false) ? 'Newer messages' : 'Previous' }}</x-prototype.link>
        @endif
        @if($page->nextPageUrl())
            <x-prototype.link :href="$page->nextPageUrl()">{{ ($messageHistory ?? false) ? 'Older messages' : 'Next' }}</x-prototype.link>
        @endif
    </nav>
@endif
