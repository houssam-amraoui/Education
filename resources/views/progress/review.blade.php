@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <div class="split">
        <div class="stack">
            <x-prototype.card>
                @if($result['status'] === 'expired')
                    <x-prototype.badge tone="warning">Expired attempt</x-prototype.badge>
                    <p>No answers were graded.</p>
                @else
                    <x-prototype.badge tone="success">Completed attempt</x-prototype.badge>
                    <h2>Score</h2>
                    <div class="score">{{ $result['awarded_points'] }} <span>/ {{ $result['possible_points'] }}</span></div>
                @endif
            </x-prototype.card>
            @if($result['status'] === 'completed')
                <h2>Corrections</h2>
                @forelse($result['questions'] as $question)
                    <x-prototype.card>
                        <x-prototype.badge :tone="$question['correct'] ? 'success' : 'warning'">{{ $question['correct'] ? 'Correct' : 'Needs review' }}</x-prototype.badge>
                        <h3>Question {{ $question['position'] }}</h3>
                        <p dir="auto">{{ $question['prompt'] }}</p>
                        <p><strong>Student answer:</strong> <x-exercises.answer :question="$question"/></p>
                        <p><strong>Correct answer:</strong> <x-exercises.answer :question="$question" correct/></p>
                        @if(filled($question['explanation']))<p dir="auto">{{ $question['explanation'] }}</p>@endif
                        <small>{{ $question['awarded_points'] }} / {{ $question['points'] }} points</small>
                    </x-prototype.card>
                @empty
                    <p class="muted">No saved corrections for this attempt.</p>
                @endforelse
            @endif
        </div>
        <aside class="stack">
            <x-prototype.card><x-prototype.link :href="$url('A04')">Back to student activity</x-prototype.link></x-prototype.card>
        </aside>
    </div>
@endsection
