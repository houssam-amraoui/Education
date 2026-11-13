@extends('prototype.layout')

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">Teaching workspace</span>
            <h1>A clear view of your classroom.</h1>
            <p>Help your learners take their next step.</p>
        </div>
        @isset($destinations['A13'])
            <a class="button button-primary" href="{{ $url('A13') }}">Create a lesson</a>
        @endisset
    </div>
    <div class="stat-grid">
        @foreach([
            ['Active students', null, 'This month'],
            ['Published lessons', $publishedLessons, 'Learning content'],
            ['Average score', null, 'Exercise results'],
            ['Awaiting a reply', $awaitingReply, 'Student conversations'],
        ] as [$label, $value, $hint])
            <div class="stat"><span>{{ $label }}</span><strong>{{ $value ?? '—' }}</strong><small>{{ $value === null ? 'Unavailable' : $hint }}</small></div>
        @endforeach
    </div>
    <div class="split">
        <div>
            <div class="section-heading"><h2>Manage your learning content</h2></div>
            <div class="cards two">
                @php
                    $modules = [
                        'A07' => ['Learning structure', 'Levels, units, and categories.', 'grid'],
                        'A13' => ['Lessons', 'Create a clear path to understanding.', 'book'],
                        'A16' => ['Exercises & questions', 'Turn understanding into confidence.', 'check'],
                        'A20' => ['Exams', 'Prepare students for the next milestone.', 'file'],
                        'A25' => ['Resources', 'Study guides, books, and worksheets.', 'file'],
                        'A27' => ['Videos', 'Bring your explanations to life.', 'play'],
                    ];
                @endphp
                @forelse(array_intersect_key($modules, $destinations) as $target => [$title, $description, $icon])
                    <x-prototype.card>
                        <x-prototype.icon :name="$icon"/>
                        <h3>{{ $title }}</h3>
                        <p>{{ $description }}</p>
                        <a href="{{ $url($target) }}">Manage →</a>
                    </x-prototype.card>
                @empty
                    <p>No learning content modules are available to your account.</p>
                @endforelse
            </div>
        </div>
        <div class="stack">
            @isset($destinations['A31'])
                <x-prototype.card>
                    <h3>Students need a hand</h3>
                    @if($latestThread)
                        <p><bdi>{{ $latestThread->student->name }}</bdi> · <bdi>{{ $latestThread->subject }}</bdi></p>
                        <a class="button button-primary" href="{{ route('admin.messages.show', $latestThread) }}">Open conversation</a>
                    @else
                        <p>No conversations are awaiting a reply.</p>
                        <a class="button button-primary" href="{{ $url('A31') }}">Open conversations</a>
                    @endif
                </x-prototype.card>
            @endisset
            <x-prototype.card>
                <h3>Learning at a glance</h3>
                @isset($destinations['A44'])
                    <x-prototype.link :href="$url('A44')">Explore analytics</x-prototype.link>
                @else
                    <p>Analytics are currently unavailable.</p>
                @endisset
            </x-prototype.card>
            <x-prototype.card>
                <h3>Platform essentials</h3>
                @php
                    $essentials = ['A02' => 'Users and roles', 'A33' => 'Points and rewards', 'A38' => 'Content access', 'A43' => 'Platform settings'];
                    if (! isset($destinations['A02']) && isset($destinations['A05'])) {
                        $essentials = ['A05' => 'Roles'] + $essentials;
                    }
                @endphp
                @forelse(array_intersect_key($essentials, $destinations) as $target => $label)
                    <a href="{{ $url($target) }}">{{ $label }} →</a>
                @empty
                    <p>No platform management modules are available to your account.</p>
                @endforelse
            </x-prototype.card>
        </div>
    </div>
@endsection
