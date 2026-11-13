@extends('prototype.layout')

@section('content')
    @php($index = route('curriculum.exercises.index'))
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <form class="filter-bar" method="get" action="{{ $index }}" data-live-form>
        @if($level)<input type="hidden" name="level_id" value="{{ $level->id }}">@endif
        <div class="field grow">
            <label for="content-search">Find in {{ $screen['title'] }}</label>
            <input id="content-search" name="search" type="search" placeholder="Try passive voice" value="{{ $filters['search'] ?? '' }}" maxlength="255">
        </div>
        <label class="field" for="content-type">Access
            <select id="content-type" name="access">
                <option value="">All content</option>
                <option value="free" @selected(($filters['access'] ?? null) === 'free')>Free</option>
            </select>
        </label>
        <button class="button button-primary" data-pending="Searching…">Search</button>
    </form>
    <div class="row between results-meta">
        <span>{{ trans_choice('{1} :count result|[0,*] :count results', $records->total()) }}</span>
        @if($level)<small dir="auto">{{ $level->title }}</small>@endif
    </div>
    @if($records->isEmpty())
        <div class="empty-state">
            <x-prototype.icon name="search"/>
            @if(filled($filters['search'] ?? null) || $records->currentPage() > 1)
                <h2>No matching content</h2><p>Try a different search or reset your filters.</p>
                <x-prototype.link :href="route('curriculum.exercises.index', $level ? ['level_id' => $level->id] : [])">Reset filters</x-prototype.link>
            @else
                <h2>No exercises available yet</h2>
            @endif
        </div>
    @else
        <div class="cards three">
            @foreach($records as $record)
                <x-prototype.card>
                    <span class="tile-icon"><x-prototype.icon name="book"/></span>
                    <div class="row between"><x-access-labels :record="$record"/></div>
                    <h2 class="card-title" dir="auto">{{ $record->title }}</h2>
                    <p><bdi>{{ $record->unit->level->title }}</bdi> · <bdi>{{ $record->unit->title }}</bdi></p>
                    <small>{{ trans_choice('{1} :count question|[0,*] :count questions', $record->questions_count) }} · Untimed</small>
                    <x-prototype.link :href="route('curriculum.exercises.instructions', $record)">Explore <x-prototype.icon name="arrow"/></x-prototype.link>
                </x-prototype.card>
            @endforeach
        </div>
        @if($records->hasPages())
            <nav class="row between results-meta" aria-label="Pages">
                <span>Page {{ $records->currentPage() }} of {{ $records->lastPage() }}</span>
                <div class="actions">
                    @if($records->previousPageUrl())<x-prototype.link :href="$records->previousPageUrl()">Previous</x-prototype.link>@endif
                    @if($records->nextPageUrl())<x-prototype.link :href="$records->nextPageUrl()">Next</x-prototype.link>@endif
                </div>
            </nav>
        @endif
    @endif
@endsection
