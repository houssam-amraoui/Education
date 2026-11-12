<div id="recommended-preparation">
    <h3>Recommended preparation</h3>
    @forelse($prerequisites as $prerequisite)
        <div class="list-row">
            <div>
                <h4 dir="auto">{{ $prerequisite->title }}</h4>
                @if($prerequisite->completed === true)
                    <x-prototype.badge tone="success">Completed</x-prototype.badge>
                @elseif($prerequisite->completed === false)
                    <small>Recommended review</small>
                @endif
            </div>
            <x-prototype.link :href="App\Support\ProgressPages::lessonUrl($prerequisite)"
                :aria-label="__('Review lesson: :title', ['title' => $prerequisite->title])">Review lesson</x-prototype.link>
        </div>
    @empty
        <p>No prerequisite lessons are available.</p>
    @endforelse
</div>
