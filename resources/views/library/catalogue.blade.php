@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <form class="filter-bar" method="get" action="{{ route('library.resources.index') }}" data-live-form>
        @if($level)<input type="hidden" name="level_id" value="{{ $level->id }}">@endif
        @if(isset($filters['selected']))<input type="hidden" name="selected" value="1">@endif
        <div class="field grow">
            <label for="content-search">Find in {{ $screen['title'] }}</label>
            <input id="content-search" name="search" type="search" placeholder="Try passive voice" value="{{ $filters['search'] ?? '' }}" maxlength="255">
        </div>
        <div class="field">
            <label for="content-type">Access</label>
            <select id="content-type" name="access">
                <option value="">All content</option>
                @foreach(App\Models\Resource::ACCESS as $access)
                    <option value="{{ $access }}" @selected(($filters['access'] ?? null) === $access)>{{ __("library.access.$access") }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="resource-type">Resource type</label>
            <select id="resource-type" name="type">
                <option value="">All types</option>
                @foreach(App\Models\Resource::TYPES as $type)
                    <option value="{{ $type }}" @selected(($filters['type'] ?? null) === $type)>{{ __("library.types.$type") }}</option>
                @endforeach
            </select>
        </div>
        <button class="button button-primary" data-pending="Searching…">Search</button>
    </form>
    <div class="row between results-meta">
        <span>{{ trans_choice('{1} :count result|[0,*] :count results', $records->total()) }}</span>
        @if($level)<small dir="auto">{{ $level->title }}</small>@endif
        @if(isset($filters['selected']))<small>Your learning paths</small>@endif
    </div>
    @if($records->isEmpty())
        <div class="empty-state">
            <x-prototype.icon name="search"/>
            @if(isset($filters['search']) || isset($filters['type']) || isset($filters['access']) || $records->currentPage() > 1)
                <h2>No matching content</h2>
                <p>Try a different search or reset your filters.</p>
                <x-prototype.link :href="route('library.resources.index', array_intersect_key($filters, array_flip(['selected', 'level_id'])))">Reset filters</x-prototype.link>
            @elseif(isset($filters['selected']))
                <h2>No resources available for your learning paths</h2>
                <x-prototype.link :href="route('account.learning-goals.show')">Change learning paths</x-prototype.link>
            @else
                <h2>No resources available yet</h2>
            @endif
        </div>
    @else
        <div class="cards three">
            @foreach($records as $record)
                <x-prototype.card>
                    <span class="tile-icon"><x-prototype.icon name="file"/></span>
                    <div class="row between"><x-access-labels :record="$record"/></div>
                    <h2 class="card-title" dir="auto">{{ $record->title }}</h2>
                    @if($record->description)<p dir="auto">{{ $record->description }}</p>@endif
                    <small>{{ __("library.types.$record->type") }}@if($record->type !== 'pdf') · PDF @endif</small>
                    <x-prototype.link :href="route('library.resources.show', $record)">Explore <x-prototype.icon name="arrow"/></x-prototype.link>
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
