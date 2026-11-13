@extends('prototype.layout')

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow"><time datetime="{{ today()->toDateString() }}">{{ today()->translatedFormat('l, j F') }}</time></span>
            <h1>Hello, <bdi>{{ $student->name }}</bdi> <span class="greeting-dot" aria-hidden="true">☀</span></h1>
            <p>Build your confidence, one step at a time.</p>
            <small><bdi>{{ $student->email }}</bdi></small>
        </div>
        <x-prototype.link :href="$url('S02')">Change learning path</x-prototype.link>
    </div>
    @include('curriculum.feedback')
    <div class="stat-grid">
        @foreach([
            ['Overall progress', $completion['percentage'].'%', $completion['completed'].' of '.$completion['total'].' lessons complete', 'chart'],
            ['Learning streak', trans_choice(':count day|:count days', $streakDays), 'Consecutive learning days', 'clock'],
            ['Your points', $pointBalance.' pts', $pointsThisWeek.' points earned this week', 'award'],
            ['Average score', $stats['average'] === null ? '—' : $stats['average'].'%', trans_choice(':count scored attempt|:count scored attempts', $stats['attempts']), 'check'],
        ] as [$label, $value, $hint, $icon])
            <div class="stat"><div class="row between"><span>{{ $label }}</span><x-prototype.icon :name="$icon"/></div><strong>{{ $value }}</strong><small>{{ $hint }}</small></div>
        @endforeach
    </div>
    <div class="split">
        <div>
            <section class="continue-card">
                <div class="row between">
                    <span class="eyebrow">Pick up where you left off</span>
                    @if($resume)<x-prototype.badge tone="info">In progress</x-prototype.badge>@endif
                </div>
                <div class="row">
                    <span class="large-icon"><x-prototype.icon/></span>
                    @if($resume)
                        <div><small dir="auto">{{ $resume->lesson->category->unit->title }} · {{ $resume->lesson->category->title }}</small><h2 dir="auto">{{ $resume->lesson->title }}</h2></div>
                    @else
                        <div><h2>No lesson to resume</h2><p>Open a lesson in one of your learning paths to pick up here later.</p></div>
                    @endif
                </div>
                @if($resume)
                    <x-prototype.link :href="App\Support\ProgressPages::lessonUrl($resume->lesson)" variant="primary">Continue learning <x-prototype.icon name="arrow"/></x-prototype.link>
                @else
                    <x-prototype.link :href="$url('G04')" variant="primary">Browse units <x-prototype.icon name="arrow"/></x-prototype.link>
                @endif
            </section>
            <div class="section-heading"><h2>Your next steps</h2><a href="{{ $url('G04') }}">All units →</a></div>
            @if($recommendedExercises->isEmpty() && $recommendedLessons->isEmpty())
                <div class="empty-state">
                    <x-prototype.icon name="book"/>
                    <h3>No recommendations right now</h3>
                    <x-prototype.link :href="$url('G04')">Browse units</x-prototype.link>
                </div>
            @else
                <div class="cards two">
                    @foreach($recommendedExercises as $exercise)
                        <x-prototype.card>
                            <x-prototype.badge>Practice · <bdi>{{ $exercise->unit->title }}</bdi></x-prototype.badge>
                            <h3 dir="auto">{{ $exercise->title }}</h3>
                            <p>{{ $exercise->recommendation_reason === 'retry' ? 'Try again to improve your most recent score.' : 'This exercise is ready for practice in your learning path.' }}</p>
                            <x-prototype.link :href="route('curriculum.exercises.instructions', $exercise)">Open exercise <x-prototype.icon name="arrow"/></x-prototype.link>
                        </x-prototype.card>
                    @endforeach
                    @foreach($recommendedLessons as $recommendedLesson)
                        <x-prototype.card>
                            <x-prototype.badge>Recommended · <bdi>{{ $recommendedLesson->category->title }}</bdi></x-prototype.badge>
                            <h3 dir="auto">{{ $recommendedLesson->title }}</h3>
                            <p>An unfinished lesson in your learning path.</p>
                            <x-prototype.link :href="App\Support\ProgressPages::lessonUrl($recommendedLesson)">Open lesson <x-prototype.icon name="arrow"/></x-prototype.link>
                        </x-prototype.card>
                    @endforeach
                </div>
            @endif
            <div class="section-heading"><h2>Recent lessons</h2><a href="{{ $url('G04') }}">All units →</a></div>
            @if($recent->isEmpty())
                <div class="empty-state"><p>No recent lessons in your selected paths.</p></div>
            @else
                <div class="cards two">
                    @foreach($recent as $item)
                        <x-prototype.card>
                            <x-prototype.badge :tone="$item->completed_at ? 'success' : 'info'">{{ $item->completed_at ? 'Completed' : 'In progress' }}</x-prototype.badge>
                            <h3 dir="auto">{{ $item->lesson->title }}</h3>
                            <p dir="auto">{{ $item->lesson->category->unit->title }} · {{ $item->lesson->category->title }}</p>
                            <x-prototype.link :href="App\Support\ProgressPages::lessonUrl($item->lesson)">Open lesson <x-prototype.icon name="arrow"/></x-prototype.link>
                        </x-prototype.card>
                    @endforeach
                </div>
            @endif
            <div class="section-heading"><h2>Recent practice</h2><a href="{{ $url('S12') }}">View history →</a></div>
            @include('progress.table', ['empty' => 'No scores yet.'])
        </div>
        <div class="stack">
            <x-prototype.card>
                <div class="row between"><h3>Your learning path</h3><x-prototype.icon name="book"/></div>
                @forelse($paths as $path)
                    <div class="progress-unit">
                        <p dir="auto">{{ $path['level']->title }}</p>
                        <x-prototype.progress :value="$path['completion']['percentage']" :label="$path['completion']['completed'].' of '.$path['completion']['total'].' lessons'"/>
                        <a href="{{ route('account.progress.level', $path['level']) }}">View your progress →</a>
                    </div>
                @empty
                    <p>No learning paths selected.</p>
                    <a href="{{ $url('S02') }}">Choose a learning path →</a>
                @endforelse
            </x-prototype.card>
            <x-prototype.card>
                <span class="eyebrow">A note from your teacher</span>
                @if($recentTeacherReplies->isNotEmpty())
                    <h3>You’ve got a reply</h3>
                    <div class="stack">
                        @foreach($recentTeacherReplies as $reply)
                            <div>
                                <p class="[overflow-wrap:anywhere]" dir="auto">{{ Str::limit($reply->body, 160) }}</p>
                                <small>About: <bdi>{{ $reply->conversation->subject }}</bdi> · {{ $reply->created_at->diffForHumans() }}</small>
                                <x-prototype.link :href="route('account.messages.show', $reply->conversation)">Read the reply</x-prototype.link>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p>No teacher replies yet.</p>
                @endif
            </x-prototype.card>
            <x-prototype.card class="reward-card">
                <x-prototype.icon name="award"/><h3>Keep showing up</h3>
                @if($recentAchievement)
                    <p dir="auto">{{ $recentAchievement->achievement->name }}</p>
                    <x-prototype.link :href="route('account.rewards.show', $recentAchievement->achievement)">View achievement</x-prototype.link>
                @else
                    <p>No achievements earned yet.</p>
                @endif
                <x-prototype.link :href="$url('S16')">View rewards</x-prototype.link>
            </x-prototype.card>
            <div class="quick-links"><a href="{{ route('curriculum.exams.index', ['selected' => 1]) }}"><x-prototype.icon name="file"/>Exam preparation →</a><a href="{{ route('library.resources.index', ['selected' => 1]) }}"><x-prototype.icon name="book"/>Study resources →</a></div>
        </div>
    </div>
@endsection
