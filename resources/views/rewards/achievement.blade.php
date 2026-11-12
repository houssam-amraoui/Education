@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <x-prototype.card class="reading achievement-detail">
        <span class="achievement-icon"><x-prototype.icon name="award"/></span>
        <x-prototype.badge :tone="$award ? 'success' : 'neutral'">{{ $award ? 'Earned' : 'In progress' }}</x-prototype.badge>
        <h2 dir="auto">{{ $achievement->name }}</h2>
        <p dir="auto">{{ $achievement->description }}</p>
        <x-prototype.progress :value="(int) round(100 * $count / $achievement->required_count)"
            :label="$count.' of '.$achievement->required_count.' '.$achievement->event->criterion()"/>
        @if($achievement->minimum_score_percent !== null)
            <p>Minimum exercise score: {{ $achievement->minimum_score_percent }}%</p>
        @endif
        @if($reward > 0 || $award)
            <p>@if($reward > 0)Reward: {{ $reward }} points @endif @if($award)· Earned {{ $award->awarded_at->translatedFormat('j M Y') }}@endif</p>
        @endif
        <div class="actions">
            <x-prototype.link :href="$url($achievement->event->learningScreen())" variant="primary">Keep learning</x-prototype.link>
            <x-prototype.link :href="$url('S16')">All achievements</x-prototype.link>
        </div>
    </x-prototype.card>
@endsection
