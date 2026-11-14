@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <x-prototype.card class="reading">
        <span class="tile-icon"><x-prototype.icon :name="$canOpen ? 'check' : 'lock'"/></span>
        <h2 dir="auto">{{ $targetTitle }}</h2>
        @if($unlocked)
            <p class="notice success" role="status">You can now access this destination.</p>
            <dl class="details">
                <div><dt>Your balance</dt><dd>{{ $balance }} points</dd></div>
            </dl>
            <x-prototype.link :href="$destination" variant="primary">Open unlocked content</x-prototype.link>
        @elseif($canOpen)
            <p class="notice info" role="status">You already have access to this destination.</p>
            <x-prototype.link :href="$destination" variant="primary">Open content</x-prototype.link>
        @elseif($cost)
            <p>Use points to unlock this destination.</p>
            <dl class="details">
                <div><dt>Your balance</dt><dd>{{ $balance }} points</dd></div>
                <div><dt>Unlock cost</dt><dd>{{ $cost->points }} points</dd></div>
                @if($balance >= $cost->points)<div><dt>Balance after unlock</dt><dd>{{ $balance - $cost->points }} points</dd></div>@endif
            </dl>
            @if($balance < $cost->points)<p class="notice info" role="status">Insufficient point balance.</p>@endif
            <div class="actions">
                <form method="post" action="{{ $submit }}" data-live-form>
                    @csrf
                    <input type="hidden" name="points" value="{{ $cost->points }}">
                    <button class="button button-primary" data-pending="Unlocking…" @disabled($balance < $cost->points)>Unlock for {{ $cost->points }} points</button>
                </form>
                <x-prototype.link :href="$back" variant="tertiary">Cancel</x-prototype.link>
            </div>
        @else
            <p class="notice info" role="status">This points unlock is currently unavailable.</p>
            <x-prototype.link :href="$back">Back to access requirements</x-prototype.link>
        @endif
    </x-prototype.card>
@endsection
