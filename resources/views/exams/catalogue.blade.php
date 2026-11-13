@extends('prototype.layout')

@section('content')
    @php($scope = array_intersect_key($filters, array_flip(['selected', 'level_id'])))
    @php($index = route('curriculum.exams.index'))
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <form class="filter-bar" method="get" action="{{ $index }}" data-live-form>
        @if($level)<input type="hidden" name="level_id" value="{{ $level->id }}">@endif
        @if(isset($filters['selected']))<input type="hidden" name="selected" value="1">@endif
        <div class="field grow">
            <label for="content-search">Find in {{ $screen['title'] }}</label>
            <input id="content-search" name="search" type="search" placeholder="Search exams" value="{{ $filters['search'] ?? '' }}" maxlength="255">
        </div>
        <label class="field" for="content-type">Content type
            <select id="content-type" name="type">
                <option value="">All content</option>
                <option value="document" @selected(($filters['type'] ?? null) === 'document')>Document</option>
                <option value="interactive" @selected(($filters['type'] ?? null) === 'interactive')>Interactive</option>
            </select>
        </label>
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
            @if(filled($filters['search'] ?? null) || filled($filters['type'] ?? null) || $records->currentPage() > 1)
                <h2>No matching content</h2><p>Try a different search or reset your filters.</p>
                <x-prototype.link :href="route('curriculum.exams.index', $scope)">Reset filters</x-prototype.link>
            @elseif(isset($filters['selected']))
                <h2>No exams available for your learning paths</h2>
                <x-prototype.link :href="route('account.learning-goals.show')">Change learning paths</x-prototype.link>
            @else
                <h2>No exams available yet</h2>
            @endif
        </div>
    @else
        <div class="cards three">
            @foreach($records as $record)
                <x-prototype.card>
                    <span class="tile-icon"><x-prototype.icon name="file"/></span>
                    <div class="row between"><x-access-labels :record="$record"/></div>
                    <h2 class="card-title" dir="auto">{{ $record->title }}</h2>
                    <p><bdi>{{ $record->unit->level->title }}</bdi> · <bdi>{{ $record->unit->title }}</bdi></p>
                    <small>{{ $record->type === 'document' ? 'Document exam' : 'Interactive · '.($record->duration_minutes ? $record->duration_minutes.' min' : 'Untimed') }}</small>
                    <x-prototype.link :href="route('curriculum.exams.instructions', $record)">Explore <x-prototype.icon name="arrow"/></x-prototype.link>
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
