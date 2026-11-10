<nav>
    @foreach($nav as [$id, $label, $icon])
        <a href="{{ $url($id) }}" @class(['nav-link', 'active' => $activeNavId === $id])
            @if($activeNavId === $id) aria-current="{{ request()->routeIs(\App\Support\Prototype::routeName($id)) ? 'page' : 'location' }}" @endif>
            <x-prototype.icon :name="$icon"/><span data-nav-id="{{ $id }}">{{ __('shared.navigation.'.$label) }}</span>@if($showCounts && in_array($id, ['S13', 'A31'], true))<span class="nav-count">2</span>@endif
        </a>
    @endforeach
</nav>
