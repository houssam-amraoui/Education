@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <div class="stat-grid compact">
        <div class="stat"><x-prototype.icon name="award"/><strong>{{ $balance }} pts</strong><span>Your balance</span></div>
        <div class="stat"><x-prototype.icon name="clock"/><strong>{{ trans_choice(':count day|:count days', $streak) }}</strong><span>Your learning streak</span></div>
        <div class="stat"><x-prototype.icon name="check"/><strong>{{ trans_choice(':count badge|:count badges', $awardCount) }}</strong><span>Small steps worth celebrating</span></div>
    </div>
    <div class="section-heading"><h2>Every step counts</h2></div>
    @if($definitions->isEmpty())
        <div class="empty-state"><p>No achievements are available yet.</p></div>
    @else
        <div class="cards three">
            @foreach($definitions as $definition)
                <x-prototype.card class="reward-card">
                    <span class="achievement-icon"><x-prototype.icon name="award"/></span>
                    <x-prototype.badge :tone="isset($awards[$definition->id]) ? 'success' : 'neutral'">{{ isset($awards[$definition->id]) ? 'Earned' : 'In progress' }}</x-prototype.badge>
                    <h3 dir="auto">{{ $definition->name }}</h3>
                    <p dir="auto">{{ $definition->description }}</p>
                    <x-prototype.link :href="route('account.rewards.show', $definition)">View achievement</x-prototype.link>
                </x-prototype.card>
            @endforeach
        </div>
    @endif
    @if($definitions->hasPages())
        <nav class="row wrap results-meta" aria-label="Achievements pagination">
            @if($definitions->previousPageUrl())
                <x-prototype.link :href="$definitions->previousPageUrl()" rel="prev">{{ __('shared.controls.previous') }}</x-prototype.link>
            @endif
            <span>Page {{ $definitions->currentPage() }} of {{ $definitions->lastPage() }}</span>
            @if($definitions->nextPageUrl())
                <x-prototype.link :href="$definitions->nextPageUrl()" rel="next">{{ __('shared.controls.next') }}</x-prototype.link>
            @endif
        </nav>
    @endif
    <div class="section-heading"><h2>Put your points to good use</h2></div>
    <x-prototype.card><p>No points unlocks are available yet.</p></x-prototype.card>
@endsection
