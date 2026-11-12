@extends('prototype.layout')

@section('content')
    @php
        $isSearch = $panel === 'search';
        $isIndex = $panel === 'topics';
        $placement = isset($level) ? ['level_id' => $level->id] : [];
        $reset = route(request()->route()->getName(), request()->route()->parameters() + $placement);
        $hasQuery = isset($filters['q']);
        $oldQuery = old('q', $filters['q'] ?? '');
        $hasFilters = $hasQuery || isset($filters['type']) || isset($filters['access']);
    @endphp
    @include('curriculum.heading', ['headingBadge' => $level->title ?? null])
    @include('curriculum.feedback')

    <form class="filter-bar" method="get" action="{{ url()->current() }}" data-live-form>
        @isset($level)<input type="hidden" name="level_id" value="{{ $level->id }}">@endisset
        <div class="field grow">
            <label for="q">{{ $isSearch ? 'Search all learning content' : 'Find in '.$screen['title'] }}</label>
            <input id="q" type="search" name="q" placeholder="Try passive voice" value="{{ is_string($oldQuery) ? $oldQuery : '' }}" maxlength="255" dir="auto" @if($errors->has('q')) aria-invalid="true" @endif>
        </div>
        <div class="field">
            @if($isSearch)
                <label for="type">Content type</label>
                <select id="type" name="type">
                    <option value="">All types</option>
                    @foreach(\App\Queries\GlobalSearch::TYPES as $type)
                        <option value="{{ $type }}" @selected(($filters['type'] ?? null) === $type)>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            @else
                <label for="access">Access</label>
                <select id="access" name="access">
                    <option value="">All content</option>
                    <option value="public" @selected(($filters['access'] ?? null) === 'public')>Free</option>
                    <option disabled>Points required</option>
                    <option disabled>Premium</option>
                </select>
            @endif
        </div>
        <button class="button button-primary" data-pending="Searching…">Search</button>
    </form>

    @if(!$isSearch || $hasQuery)
        <div class="row between results-meta">
            <span>{{ trans_choice('{1} :count result|[0,*] :count results', $panel === 'topic' ? $records->count() : $records->total()) }}</span>
            @if($isSearch)<small>Lessons, exercises, exams, resources, videos &amp; topics</small>@endif
        </div>
    @endif

    @if($records->isEmpty())
        <div class="empty-state">
            <x-prototype.icon name="search"/>
            @if($isSearch && !$hasQuery)
                <h2>Search learning content</h2><p>Enter a search term to find lessons, practice, and study material.</p>
            @elseif($hasFilters || ($panel !== 'topic' && $records->currentPage() > 1))
                <h2>No matching content</h2><p>Try a different search or reset your filters.</p>
                <x-prototype.link :href="$reset">Reset filters</x-prototype.link>
            @else
                <h2>{{ $isIndex ? 'No topics yet' : 'No related content' }}</h2>
                <p>Available learning content will appear here.</p>
            @endif
        </div>
    @else
        <div @class(['cards', 'two' => $isSearch, 'three' => !$isSearch])>
            @foreach($records as $record)
                @php
                    if ($isIndex) {
                        $count = collect(array_keys(\App\Models\Topic::CONTENT))->sum(fn ($type) => $record->{$type.'s_count'});
                        $target = route('curriculum.topics.show', ['topic' => $record->id] + $placement + array_intersect_key($filters, ['access' => true]));
                    } else {
                        $target = route($record['destination']['route'], $record['destination']['parameters']);
                    }
                @endphp
                <x-prototype.card>
                    <span class="tile-icon"><x-prototype.icon name="book"/></span>
                    <div class="row between"><x-prototype.badge>{{ $isIndex ? 'Topic' : ($record['type'] === 'topic' ? 'Topic' : __("library.access.{$record['access']}")) }}</x-prototype.badge></div>
                    <h2 class="card-title" dir="auto">{{ $isIndex ? $record->title : $record['title'] }}</h2>
                    @if($isIndex)
                        @if($record->description)<p dir="auto">{{ $record->description }}</p>@endif
                        <small>{{ trans_choice('{1} :count learning resource|[0,*] :count learning resources', $count) }}</small>
                    @else
                        <small>{{ ucfirst($record['type']) }}@foreach($record['context'] as $context) · <bdi>{{ $context }}</bdi>@endforeach</small>
                    @endif
                    <x-prototype.link :href="$target">Explore <x-prototype.icon name="arrow"/></x-prototype.link>
                </x-prototype.card>
            @endforeach
        </div>
    @endif

    @if($panel !== 'topic' && $records->hasPages())
        <nav class="row between results-meta" aria-label="Pages">
            <span>Page {{ $records->currentPage() }} of {{ $records->lastPage() }}</span>
            <div class="actions">
                @if($records->previousPageUrl())<x-prototype.link :href="$records->previousPageUrl()" rel="prev">Previous</x-prototype.link>@endif
                @if($records->nextPageUrl())<x-prototype.link :href="$records->nextPageUrl()" rel="next">Next</x-prototype.link>@endif
            </div>
        </nav>
    @endif

    @if($panel === 'topic')
        <div class="notice info"><x-prototype.icon name="message"/><div><strong>Need a hand finding your next step?</strong><p>Return to your learning dashboard.</p><div class="actions"><x-prototype.link :href="route('account.home')" variant="tertiary">Open dashboard</x-prototype.link></div></div></div>
    @endif
@endsection
