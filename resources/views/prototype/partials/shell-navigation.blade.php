<nav>
    @foreach($nav as [$id, $label, $icon])
        <a href="{{ $url($id) }}" @class(['nav-link', 'active' => $activeNavId === $id])
            @if($activeNavId === $id) aria-current="{{ ($live ? $screen['id'] === $id : request()->routeIs(\App\Support\Prototype::routeName($id))) ? 'page' : 'location' }}" @endif>
            <x-prototype.icon :name="$icon"/><span data-nav-id="{{ $id }}">{{ $live && $id === 'S01' && !isset($destinations['S10']) ? 'Your account' : __('shared.navigation.'.$label) }}</span>@if(!$live && $showCounts && in_array($id, ['S13', 'A31'], true))<span class="nav-count">2</span>@endif
        </a>
    @endforeach
</nav>
