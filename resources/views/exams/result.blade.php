@extends('prototype.layout')

@section('content')
    @php
        $history = route('curriculum.exams.attempts.index', $exam);
        $unit = route('curriculum.unit', [$exam->unit->level_id, $exam->unit]);
    @endphp
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="split">
        <div class="stack">
            <x-prototype.card>
                @if($result['status'] === 'expired')
                    <x-prototype.badge tone="warning">Expired attempt</x-prototype.badge>
                    <h2>Time is up.</h2>
                    <p role="status">This exam attempt has expired. No answers were graded.</p>
                @else
                    <x-prototype.badge tone="success">Completed attempt</x-prototype.badge>
                    <h2>Your score</h2>
                    <div class="score">{{ $result['awarded_points'] }} <span>/ {{ $result['possible_points'] }}</span></div>
                    @if($result['possible_points'] > 0)
                        <p>{{ round(100 * $result['awarded_points'] / $result['possible_points']) }}% · Score</p>
                    @endif
                @endif
                <div class="actions">
                    <form method="post" action="{{ route('curriculum.exams.attempts.store', $exam) }}" data-live-form>
                        @csrf
                        <button class="button button-primary" data-pending="Starting…">Try again</button>
                    </form>
                    <x-prototype.link :href="$unit">Review unit</x-prototype.link>
                </div>
            </x-prototype.card>
            @if($result['status'] === 'completed')
                <h2>Corrections</h2>
            @endif
            @foreach($result['questions'] as $question)
                <x-prototype.card>
                    <x-prototype.badge :tone="$question['correct'] ? 'success' : 'warning'">{{ $question['correct'] ? 'Correct' : 'Needs review' }}</x-prototype.badge>
                    <h3>Question {{ $question['position'] }}</h3>
                    <p dir="auto">{{ $question['prompt'] }}</p>
                    <p><strong>Your answer:</strong> <x-exercises.answer :question="$question"/></p>
                    <p><strong>Correct answer:</strong> <x-exercises.answer :question="$question" correct/></p>
                    @if(filled($question['explanation']))
                        <p dir="auto">{{ $question['explanation'] }}</p>
                    @endif
                    <small>{{ $question['awarded_points'] }} / {{ $question['points'] }} points</small>
                </x-prototype.card>
            @endforeach
        </div>
        <aside class="stack">
            <x-prototype.card>
                <h3>Where to next?</h3>
                @include('progress.recommended-lessons')
                <x-prototype.link :href="$url('G15')">Return to instructions</x-prototype.link>
                <x-prototype.link :href="$unit">Continue learning</x-prototype.link>
                <x-prototype.link :href="$history" variant="tertiary">View attempt history →</x-prototype.link>
            </x-prototype.card>
        </aside>
    </div>
@endsection
