@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="split">
        <x-prototype.card class="reading">
            <x-prototype.badge tone="info">{{ $description }}</x-prototype.badge>
            <h2>Ready to put it into practice?</h2>
            @if(filled($assessment->instructions))
                <p dir="auto">{!! nl2br(e($assessment->instructions)) !!}</p>
            @endif
            <div class="stat-grid compact">
                <div><strong>{{ $questionCount }}</strong><small>Questions</small></div>
                <div><strong>{{ $exam && $assessment->duration_minutes !== null ? $assessment->duration_minutes.' min' : 'Untimed' }}</strong><small>Time limit</small></div>
            </div>
            <h3>Before you start</h3>
            <ol><li>Read each question carefully.</li><li>Select or enter your answer using the labeled controls.</li></ol>
            @if($questionCount === 0)
                <p class="notice info">This assessment has no questions yet.</p>
            @elseif($canStart)
                <form method="post" action="{{ $startUrl }}" data-live-form>
                    @csrf
                    <button class="button button-primary" data-pending="Starting…">Start {{ $exam ? 'exam' : 'exercise' }} <x-prototype.icon name="arrow"/></button>
                </form>
            @endif
        </x-prototype.card>
        <aside class="stack">
            <x-prototype.card>
                <h3>Access information</h3>
                @guest
                    <p>Sign in with a student account to start an attempt.</p>
                    <a class="button button-secondary" href="{{ route('account.login.show', ['return_to' => request()->getPathInfo()]) }}">Sign in</a>
                @else
                    <p>{{ $canStart ? 'This attempt will be saved to your account.' : 'A student account is required to start an attempt.' }}</p>
                @endguest
            </x-prototype.card>
        </aside>
    </div>
@endsection
