@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    @isset($student)
        <x-prototype.card>
            <div class="row">
                <span class="avatar large" aria-hidden="true">{{ Str::upper(Str::substr($student->name, 0, 1)) }}</span>
                <div><h2 dir="auto">{{ $student->name }}</h2><p>Student · <bdi>{{ $student->email }}</bdi></p></div>
            </div>
        </x-prototype.card>
    @endisset
    <div class="stat-grid">
        @foreach([
            ['Learning progress', $completion['percentage'].'%', $completion['completed'].' of '.$completion['total'].' lessons'],
            ['Exercises completed', $stats['exercises'], 'Distinct exercises'],
            ['Average score', $stats['average'] === null ? '—' : $stats['average'].'%', trans_choice(':count scored attempt|:count scored attempts', $stats['attempts'])],
            ['Learning streak', '—', 'Unavailable'],
        ] as [$label, $value, $hint])
            <div class="stat"><span>{{ $label }}</span><strong>{{ $value }}</strong><small>{{ $hint }}</small></div>
        @endforeach
    </div>
    <div class="split">
        <x-prototype.card>
            <div class="section-heading"><h2>{{ isset($level) ? 'Your units' : (isset($student) ? 'Selected learning paths' : 'Your learning paths') }}</h2></div>
            @forelse($groups as $group)
                <div class="progress-unit">
                    <x-prototype.progress :value="$group['completion']['percentage']" :label="$group['title']"/>
                    <a href="{{ $group['url'] }}">{{ $group['label'] }} →</a>
                </div>
            @empty
                <p class="muted">{{ isset($level) ? 'No published units yet.' : 'No learning paths to display.' }}</p>
            @endforelse
            @isset($resume)
                <div class="actions"><x-prototype.link :href="App\Support\ProgressPages::lessonUrl($resume->lesson)" variant="primary">Continue learning</x-prototype.link></div>
            @endisset
        </x-prototype.card>
        <x-prototype.card>
            <h3>A week of small wins</h3>
            @php($weekStart = now()->utc()->startOfWeek())
            @php($peak = max([1, ...array_values($week)]))
            <div class="week-chart">
                @foreach(range(0, 6) as $offset)
                    @php($day = $weekStart->copy()->addDays($offset))
                    @php($events = $week[$day->toDateString()] ?? 0)
                    <div aria-label="{{ $day->translatedFormat('l') }}: {{ trans_choice(':count activity|:count activities', $events) }}">
                        <span style="--bar:{{ round(100 * $events / $peak) }}%"></span>
                        <small>{{ $day->translatedFormat('D') }}</small>
                    </div>
                @endforeach
            </div>
            <p>{{ trans_choice(':count active day this week|:count active days this week', count($week)) }}</p>
        </x-prototype.card>
    </div>
    <div class="section-heading">
        <h2>{{ isset($student) ? 'Learning activity' : 'Recent activity' }}</h2>
        @isset($historyUrl)<a href="{{ $historyUrl }}">Full learning history →</a>@endisset
    </div>
    @include('progress.table')
@endsection
