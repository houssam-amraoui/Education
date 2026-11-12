@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <div class="row between wrap attempt-meta">
        @if($summary['status'] === 'in_progress')
            <x-prototype.badge tone="info">{{ trans_choice(':count question|:count questions', $questions->count()) }}</x-prototype.badge>
            @if($deadline)
                <strong><x-prototype.icon name="clock"/> <time dir="ltr" data-attempt-deadline="{{ $deadline->getTimestamp() }}" data-server-time="{{ now()->getTimestamp() }}" datetime="{{ $deadline->toIso8601String() }}">{{ $deadline->format('H:i') }} UTC</time></strong>
            @elseif($exam)
                <strong>Untimed · No time limit</strong>
            @endif
        @endif
        <a href="{{ $url($parent) }}">Return to instructions</a>
    </div>
    @if($summary['status'] === 'completed')
        <div class="reading">
            <x-prototype.card>
                <x-prototype.badge tone="success">Completed attempt</x-prototype.badge>
                <h2>Your score</h2>
                <div class="score">{{ $summary['awarded_points'] }} <span>/ {{ $summary['possible_points'] }}</span></div>
            </x-prototype.card>
        </div>
    @elseif($summary['status'] === 'expired')
        <p class="notice warning" role="status">Time is up. This exam attempt has expired.</p>
    @elseif($questions->isEmpty())
        <x-questions.learner :questions="$questions"/>
    @else
        <form method="post" action="{{ $submitUrl }}" class="stack reading" data-learner-attempt data-result-url="{{ $resultUrl }}">
            @csrf
            <div class="validation-summary" role="alert" tabindex="-1" data-attempt-errors hidden>
                <strong>{{ __('shared.feedback.validation_summary') }}</strong>
                <ul></ul>
            </div>
            <p class="notice warning" role="status" data-attempt-time-up hidden>Time is up. Submit to close this attempt.</p>
            <x-questions.learner :questions="$questions"/>
            <button type="submit" class="button button-primary" data-attempt-submit disabled>
                <span>Submit answers</span> <x-prototype.icon name="arrow"/>
            </button>
            <noscript><p class="notice info">Enable JavaScript to submit your answers.</p></noscript>
        </form>
    @endif
@endsection
