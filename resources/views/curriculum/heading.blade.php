<div class="page-heading">
    <div>
        <span class="eyebrow">{{ $area === 'admin' ? 'Teaching workspace' : 'Your English journey' }}</span>
        <h1 dir="auto">{{ $screen['title'] }}</h1>
        <p dir="auto">{{ $description ?? ($area === 'admin' ? 'Organize the next step in your students’ learning.' : 'Build your confidence, one step at a time.') }}</p>
    </div>
    @isset($headingBadge)
        <x-prototype.badge tone="info"><span dir="auto">{{ $headingBadge }}</span></x-prototype.badge>
    @endisset
</div>
