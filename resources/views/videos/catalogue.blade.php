@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <form class="filter-bar" method="get" action="{{ route('library.videos.index') }}" data-live-form>
        @if($level)<input type="hidden" name="level_id" value="{{ $level->id }}">@endif
        <div class="field grow">
            <label for="content-search">Find in {{ $screen['title'] }}</label>
            <input id="content-search" name="search" type="search" placeholder="Try passive voice" value="{{ $filters['search'] ?? '' }}" maxlength="255">
        </div>
        <div class="field">
            <label for="content-type">Access</label>
            <select id="content-type" name="access">
                <option value="">All content</option>
                @foreach(App\Models\Video::ACCESS as $access)
                    <option value="{{ $access }}" @selected(($filters['access'] ?? null) === $access)>{{ __("library.access.$access") }}</option>
                @endforeach
            </select>
        </div>
        <button class="button button-primary" data-pending="Searching…">Search</button>
    </form>
    <div class="row between results-meta">
        <span>{{ trans_choice('{1} :count result|[0,*] :count results', $records->total()) }}</span>
        @if($level)<small dir="auto">{{ $level->title }}</small>@endif
    </div>
    @if($records->isEmpty())
        <div class="empty-state">
            <x-prototype.icon name="search"/>
            @if(array_filter($filters, fn ($value) => $value !== null) || $records->currentPage() > 1)
                <h2>No matching content</h2>
                <p>Try a different search or reset your filters.</p>
                <x-prototype.link :href="route('library.videos.index', $level ? ['level_id' => $level->id] : [])">Reset filters</x-prototype.link>
            @else
                <h2>No videos available yet</h2>
            @endif
        </div>
    @else
        <div class="cards three">
            @foreach($records as $record)
                <x-prototype.card>
                    <div class="video-thumbnail"><x-prototype.icon name="play"/><span>{{ $record->provider === 'youtube' ? 'YouTube' : ucfirst($record->provider) }}</span></div>
                    <div class="row between"><x-access-labels :record="$record"/></div>
                    <h2 class="card-title" dir="auto">{{ $record->title }}</h2>
                    @if($record->description)<p dir="auto">{{ $record->description }}</p>@endif
                    <x-prototype.link :href="route('library.videos.show', $record)">Explore <x-prototype.icon name="arrow"/></x-prototype.link>
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
