@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <nav class="route-tabs" aria-label="Analytics reports">
        @foreach(['A44' => 'Overview', 'A45' => 'Acquisition', 'A46' => 'Engagement', 'A47' => 'Learning', 'A48' => 'Communication', 'A49' => 'Business'] as $target => $label)
            <a href="{{ $url($target) }}" @if($screen['id'] === $target) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <form class="filter-bar" method="get" action="{{ url()->current() }}" data-live-form>
        <x-account.field name="from" label="From (UTC)" type="date" :value="$dates['from']" required />
        <x-account.field name="through" label="Through (UTC)" type="date" :value="$dates['through']" required />
        <button class="button button-primary" data-pending="Loading report…">Apply period</button>
    </form>

    @if($errors->hasAny(['from', 'through']))
        <p class="notice" role="alert">Enter a valid reporting period to view results.</p>
    @elseif($report === 'business' && ! $metrics['available'])
        <div class="empty-state">
            <x-prototype.icon name="chart"/>
            <h2>Business metrics aren’t available yet.</h2>
            <p>{{ $metrics['reason'] }}</p>
            <ul>
                @foreach($metrics['missing_sources'] as $metric => $reason)
                    <li><strong>{{ $metric }}</strong> · {{ $reason }}</li>
                @endforeach
            </ul>
            <x-prototype.link :href="$url('A44')">Back to analytics overview</x-prototype.link>
        </div>
    @else
        @php
            $percent = fn ($value) => $value === null ? null : $value.'%';
            $stats = match ($report) {
                'acquisition' => [
                    ['Visitors', $metrics['visitors']],
                    ['Registrations', $metrics['registrations']],
                    ['Active students', $metrics['active_students']],
                    ['New learners', $metrics['recent_registrations'], 'Last '.count($chart).' days of the period'],
                ],
                'engagement' => [
                    ['Peak daily active students', max([0, ...array_values($metrics['daily_active_users'])])],
                    ['Lessons opened', $metrics['lesson_opens']],
                    ['Exercise attempts', $metrics['exercise_starts']],
                    ['Resources used', $metrics['resource_opens']],
                ],
                'learning' => [
                    ['Lesson completion', $percent($metrics['progress']['percentage'])],
                    ['Average exercise score', $percent($metrics['exercises']['average_score'])],
                    ['Average exam score', $percent($metrics['exams']['average_score'])],
                    ['Completed exams', $metrics['exams']['submissions']],
                ],
                'communication' => [
                    ['Teacher questions', $metrics['ask_teacher_requests']],
                    ['Messages', $metrics['messages']],
                    ['Teacher replies', $metrics['teacher_replies']],
                    ['Awaiting a reply', $metrics['awaiting_reply'], 'Current status of conversations active in this period'],
                ],
                'business' => [
                    ['Paid-content conversions', $metrics['paid_content_conversions']],
                    ['Teacher-service leads', $metrics['teacher_service_leads']],
                    ['Subscription conversions', $metrics['subscription_conversions']],
                    ['Advertising engagement', $metrics['advertising_engagement']],
                ],
                default => [
                    ['Active students', $metrics['acquisition']['active_students']],
                    ['Lesson completion', $percent($metrics['learning']['progress']['percentage'])],
                    ['Average score', $percent($metrics['learning']['exercises']['average_score'])],
                    ['Teacher questions', $metrics['communication']['ask_teacher_requests']],
                ],
            };
            $peak = max($chart ?: [0]);
        @endphp
        <div class="notice info">Reporting period · <bdi>{{ $dates['from'] }} – {{ $dates['through'] }}</bdi> · UTC</div>
        <div class="stat-grid">
            @foreach($stats as $stat)
                @php([$label, $value] = $stat)
                <div class="stat">
                    <span>{{ $label }}</span>
                    <strong>{{ $value ?? '—' }}</strong>
                    <small>{{ $value === null ? 'No data' : ($stat[2] ?? 'Selected period') }}</small>
                </div>
            @endforeach
        </div>
        @unless($report === 'business')
            <div class="split">
                <x-prototype.card>
                    <h2>{{ $report === 'communication' ? 'Communication activity' : 'Learning activity' }}</h2>
                    @if($peak)
                        <div class="week-chart large-chart" role="img" aria-label="{{ collect($chart)->map(fn ($value, $day) => $day.' '.$value)->implode(', ') }}">
                            @foreach($chart as $day => $value)
                                <div>
                                    @if($value)<span style="--bar:{{ round(100 * $value / $peak) }}%"></span>@endif
                                    <small dir="ltr">{{ substr($day, 5) }}</small>
                                </div>
                            @endforeach
                        </div>
                        <p>{{ $report === 'communication' ? 'Messages per day' : 'Active students per day' }} · Last {{ count($chart) }} days of the period</p>
                    @else
                        <p>{{ $report === 'communication' ? 'No messages' : 'No student activity' }} in the last {{ count($chart) }} days of the period.</p>
                    @endif
                </x-prototype.card>
                <x-prototype.card>
                    <h3>Where students need support</h3>
                    @include('admin.analytics-details')
                    @if($student)
                        <x-prototype.link :href="route('admin.accounts.users.progress', $student)">Inspect student activity</x-prototype.link>
                    @endif
                    @if($conversation)
                        <x-prototype.link :href="route('admin.messages.show', $conversation)">Open conversation</x-prototype.link>
                    @elseif(isset($destinations['A31']))
                        <x-prototype.link :href="$url('A31')">Open conversations</x-prototype.link>
                    @endif
                </x-prototype.card>
            </div>
        @endunless
    @endif
@endsection
