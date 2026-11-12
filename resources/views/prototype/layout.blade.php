<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $live = $live ?? false;
@endphp
<html lang="{{ $locale }}" dir="{{ config("app.supported_locales.$locale.direction") }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $live ? 'English Atlas learning platform' : 'English learning platform visual prototype. Sample data only.' }}">
    <title>{{ $screen['title'] }} · English Atlas</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a href="#main" class="skip-link">{{ __('shared.controls.skip_to_content') }}</a>
@php
    $studentNav = [['S01','dashboard','grid'],['G02','learning_paths','book'],['G04','units','grid'],['G11','exercises','check'],['G13','exams','file'],['G16','resources','file'],['G19','videos','play'],['G09','topic_index','book'],['G08','search','search'],['S10','progress','chart'],['S13','messages','message'],['S16','rewards','award'],['S22','profile','user'],['S23','settings','grid']];
    $adminNav = [['A01','overview','grid'],['A02','users','user'],['A05','roles','lock'],['A07','learning_paths','book'],['A09','units','grid'],['A11','categories','grid'],['A13','lessons','book'],['A16','exercises','check'],['A20','exams','file'],['A25','resources','file'],['A27','videos','play'],['A29','topics','book'],['A31','conversations','message'],['A33','points_rules','award'],['A35','achievements','award'],['A37','streak_settings','clock'],['A38','access_rules','lock'],['A44','analytics','chart'],['A43','platform_settings','grid']];
    $publicNav = [['G01','home','grid'],['G02','learning_paths','book'],['G04','units','grid'],['G11','exercises','check'],['G13','exams','file'],['G16','resources','file'],['G19','videos','play'],['G09','topic_index','book'],['G08','search','search'],['G21','create_account','user'],['G22','sign_in','lock']];
    $nav = match($area) {'admin' => $adminNav, 'student' => $studentNav, default => $publicNav};
    $nav = array_filter($nav, fn ($item) => $live
        ? $item[0] !== 'A01' && ($item[0] !== 'G01' || request()->routeIs('curriculum.*')) && isset($destinations[$item[0]])
        : Route::has(\App\Support\Prototype::routeName($item[0])));
    $activeIds = [$screen['id'], ...array_reverse(array_column($breadcrumbs, 'id'))];
    $activeNavId = collect($activeIds)->first(fn ($id) => in_array($id, array_column($nav, 0), true));
@endphp
@unless($live)
<div class="prototype-strip"><span><span class="status-dot"></span> Visual prototype · Sample data only</span><a href="#screen-directory" data-open-directory>Screen directory <span aria-hidden="true">↗</span></a></div>
@endunless
<header class="topbar">
    <div class="row"><button class="icon-button mobile-menu" aria-label="{{ __('shared.controls.open_navigation') }}" data-open-nav><x-prototype.icon name="menu"/></button><a class="brand" href="{{ $url('G01') }}"><span class="brand-mark"><x-prototype.icon/></span>English Atlas<span class="brand-tag">Learn. Practice. Grow.</span></a></div>
    @if($live)
        @auth
            <nav class="role-switch" aria-label="Workspace"><a href="{{ $url('S01') }}" @if($area === 'student') aria-current="true" @endif>Account</a>@if(isset($destinations['A02']) || isset($destinations['A05']) || isset($destinations['A07']))<a href="{{ $url('A01') }}" @if($area === 'admin') aria-current="true" @endif>Admin</a>@endif</nav>
        @endauth
    @else
        <nav class="role-switch" aria-label="Prototype preview"><a href="{{ route('prototype.G01') }}" @if($area === 'public') aria-current="true" @endif>Explore</a><a href="{{ $url('S01') }}" @if($area === 'student') aria-current="true" @endif>Student</a><a href="{{ $url('A01') }}" @if($area === 'admin') aria-current="true" @endif>Admin</a></nav>
    @endif
    <div class="row header-tools">@unless($live)<a class="icon-button" href="{{ $url('G08') }}" aria-label="Search learning content"><x-prototype.icon name="search"/></a>@endunless<form method="post" action="{{ route($live ? 'account.locale' : 'locale.update') }}" class="language-label">
        @if($live)
            @csrf
            <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
        @else
        <input type="hidden" name="_token" value="{{ request()->session()->token() }}">
        <input type="hidden" name="screen" value="{{ $screen['id'] }}">
        <input type="hidden" name="query" value="{{ request()->getQueryString() }}">
        @endif
        <select name="locale" data-language aria-label="{{ __('shared.controls.language') }}">
            @foreach(['en' => 'EN', 'fr' => 'FR', 'ar' => 'العربية'] as $code => $label)
                <option value="{{ $code }}" lang="{{ $code }}" @selected($locale === $code)>{{ $label }}</option>
            @endforeach
        </select>
        <noscript><button type="submit" class="button button-secondary">{{ __('shared.controls.apply') }}</button></noscript>
    </form>@unless($live)<a class="avatar" href="{{ $url('S22') }}" aria-label="Open Amina's sample profile">AB</a>@endunless</div>
</header>
@if ($locale !== 'en' && ! $live)
    <p class="locale-notice">{{ __('shared.feedback.preview_language') }}</p>
@endif
<div class="app-shell">
    <aside class="sidebar" aria-label="{{ $area === 'admin' ? 'Administration' : 'Learning' }} navigation">
        <div class="sidebar-intro"><span class="eyebrow" data-i18n="workspace">{{ match($area) {'admin' => 'Administration', 'student' => 'Your learning space', default => 'Discover your next step'} }}</span><strong>{{ match($area) {'admin' => 'Manage English Atlas', 'student' => $live ? 'Your account' : '2nd Baccalaureate', default => 'Explore English Atlas'} }}</strong></div>
        @include('prototype.partials.shell-navigation', ['showCounts' => true])
        @if($live)
            @auth
                <form method="post" action="{{ route('account.logout') }}" class="sidebar-note" data-live-form>
                    @csrf
                    <button class="button button-secondary" data-pending="Signing out…">Sign out</button>
                </form>
            @endauth
        @else
            <div class="sidebar-note"><x-prototype.icon name="message"/><strong>A little help goes a long way.</strong><p>Your teacher is here when you need a hand.</p><a href="{{ $url('S14') }}">{{ __('shared.navigation.ask_teacher') }} <span aria-hidden="true">→</span></a></div>
        @endif
    </aside>
    <main id="main" tabindex="-1">
        <div @class(['content', '[overflow-wrap:anywhere]' => $live && request()->routeIs('curriculum.*', 'admin.curriculum.*')])>
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                @foreach($breadcrumbs as $crumb)
                    <a href="{{ $url($crumb['id']) }}" lang="en">{{ $crumb['title'] }}</a><span aria-hidden="true">/</span>
                @endforeach
                <span aria-current="page" lang="en">{{ $screen['title'] }}</span>
            </nav>
            @yield('content')
            <footer class="page-footer"><span>English Atlas · A little progress, every day.</span>@unless($live)<span>{{ $screen['id'] }} · Visual validation</span>@endunless</footer>
        </div>
    </main>
</div>
<dialog id="mobile-navigation" aria-label="Navigation">
    <div class="row between"><strong>Navigation</strong><button class="icon-button" data-close-dialog aria-label="{{ __('shared.controls.close_navigation') }}"><x-prototype.icon name="close"/></button></div>
    @include('prototype.partials.shell-navigation', ['showCounts' => false])
    @if($live)
        @auth
            <form method="post" action="{{ route('account.logout') }}" data-live-form>
                @csrf
                <button class="button button-secondary" data-pending="Signing out…">Sign out</button>
            </form>
        @endauth
    @endif
</dialog>
@unless($live)
<dialog id="screen-directory" aria-labelledby="directory-title"><div class="row between"><div><span class="eyebrow">Prototype map</span><h2 id="directory-title">Explore every screen</h2></div><button class="icon-button" data-close-dialog aria-label="Close screen directory"><x-prototype.icon name="close"/></button></div><p>Static previews. Forms and outcomes are simulated and do not save data.</p><label for="directory-filter">Find a screen</label><input id="directory-filter" type="search" placeholder="Search by name or screen ID" data-directory-filter><div class="directory-grid">@foreach(['G' => 'Public & shared','S' => 'Student','A' => 'Administration'] as $prefix => $group)<section><h3>{{ $group }}</h3>@foreach($screens as $entry)@if(str_starts_with($entry['id'],$prefix))<a class="directory-link" href="{{ $url($entry['id']) }}"><small>{{ $entry['id'] }}</small> {{ $entry['title'] }}</a>@endif @endforeach</section>@endforeach</div><p class="muted">Conditional subscriptions, leaderboards, advertising, and unresolved commercial workflows are outside this preview.</p></dialog>
@endunless
</body>
</html>
