@extends('prototype.layout')

@section('content')
    @include('curriculum.heading', ['description' => match ($panel) {
        'level' => $level->description,
        default => isset($category) ? $category->description : ($unit->description ?? null),
    }])

    @if($panel === 'level')
        <div class="notice info">
            <x-prototype.icon name="book"/>
            <div>
                <strong>Build your confidence, one step at a time.</strong>
                <p>Explore this learning path’s published units and categories.</p>
                @if(isset($destinations['S02']))
                    <x-prototype.link :href="route('account.learning-goals.show', ['level_id' => $level->id])">Select this learning path</x-prototype.link>
                @endif
            </div>
        </div>
        <div class="cards three">
            @foreach([
                ['Units', 'Learn step by step through your course.', trans_choice('{1} :count structured unit|[0,*] :count structured units', $level->units_count), route('curriculum.level.units', $level)],
                ['Exercises', 'Practice what you have learned.', 'Grammar, vocabulary, and more', route('curriculum.exercises.index', ['level_id' => $level->id])],
                ['Exams', 'Prepare with confidence.', 'Documents and interactive practice', null],
                ['Exams', 'Prepare with confidence.', 'Documents and interactive practice', route('curriculum.exams.index', ['level_id' => $level->id])],
                ['Resources', 'Keep useful study material close.', 'Guides and worksheets', route('library.resources.index', ['level_id' => $level->id])],
                ['Videos', 'See the explanation in action.', 'Teacher-led lessons', route('library.videos.index', ['level_id' => $level->id])],
                ['Topic index', 'Find a concept quickly.', 'Browse by topic', route('curriculum.topics.index', ['level_id' => $level->id])],
            ] as [$title, $copy, $meta, $target])
                <x-prototype.card>
                    <span class="tile-icon"><x-prototype.icon name="book"/></span>
                    <x-prototype.badge>{{ $target ? 'Free' : 'Unavailable' }}</x-prototype.badge>
                    <h2 class="card-title">{{ $title }}</h2><p>{{ $copy }}</p><small>{{ $meta }}</small>
                    @if($target)<x-prototype.link :href="$target">Explore <x-prototype.icon name="arrow"/></x-prototype.link>@endif
                </x-prototype.card>
            @endforeach
        </div>
    @else
        @php
            $kind = match ($screen['id']) {'G02' => 'path', 'G04' => 'unit', 'G06' => 'lesson', default => 'category'};
            $plural = $kind === 'category' ? 'categories' : $kind.'s';
            $listUrl = url()->current();
            $hasSearch = $search !== null;
        @endphp
        <form class="filter-bar" method="get" action="{{ $listUrl }}" data-live-form>
            <div class="field grow">
                <label for="content-search">Find in {{ $screen['title'] }}</label>
                <input id="content-search" type="search" name="search" value="{{ $search }}" maxlength="255">
            </div>
            <div class="field"><label for="content-type">Access</label><select id="content-type" disabled><option>All content</option></select></div>
            <button class="button button-primary" data-pending="Searching…">Search</button>
        </form>
        @if($errors->any())<p class="notice danger" role="alert">{{ $errors->first() }}</p>@endif
        <div class="row between results-meta">
            <span>{{ trans_choice('{1} :count result|[0,*] :count results', $records->total()) }}</span>
            @if($level)<small dir="auto">{{ $level->title }}</small>@endif
        </div>
        @if($records->isEmpty())
            <div class="empty-state">
                <x-prototype.icon :name="$hasSearch ? 'search' : 'book'"/>
                <h2>{{ $records->total() ? 'No results on this page' : ($hasSearch ? 'No matching content' : 'No '.$plural.' yet') }}</h2>
                @if($hasSearch || $records->currentPage() > 1)
                    <x-prototype.link :href="$listUrl">Reset filters</x-prototype.link>
                @elseif($kind === 'lesson')
                    <p>Explore another category in this unit.</p>
                    <x-prototype.link :href="$url('G05')">Back to unit categories</x-prototype.link>
                @elseif($level)
                    <x-prototype.link :href="$kind === 'unit' ? $url('G03') : $url('G04')">Back to {{ $kind === 'unit' ? 'learning path' : 'units' }}</x-prototype.link>
                @endif
            </div>
        @else
            <div class="cards three">
                @foreach($records as $record)
                    @php
                        [$target, $meta] = match ($kind) {
                            'path' => [route('curriculum.level', $record), trans_choice('{1} :count unit|[0,*] :count units', $record->units_count)],
                            'unit' => [route('curriculum.unit', [$record->level, $record]), $record->level->title.' · '.trans_choice('{1} :count category|[0,*] :count categories', $record->categories_count)],
                            'lesson' => [route('curriculum.lesson', [$level, $unit, $category, $record]), $category->title],
                            default => [route('curriculum.category', [$level, $unit, $record]), $unit->title],
                        };
                    @endphp
                    <x-prototype.card>
                        <span class="tile-icon"><x-prototype.icon name="book"/></span>
                        <x-access-labels :record="$record"/>
                        <h2 class="card-title" dir="auto">{{ $record->title }}</h2>
                        @if($kind !== 'lesson' && $record->description)<p dir="auto">{{ $record->description }}</p>@endif
                        <small dir="auto">{{ $meta }}</small>
                        <x-prototype.link :href="$target">{{ $kind === 'lesson' ? 'Open lesson' : 'Explore' }} <x-prototype.icon name="arrow"/></x-prototype.link>
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
    @endif
@endsection
