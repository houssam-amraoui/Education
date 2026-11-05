<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="English learning platform visual prototype. Sample data only.">
    <title>{{ $screen['title'] }} · English Atlas</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a href="#main" class="skip-link">Skip to content</a>
@php
    $studentNav = [['S01','Dashboard','grid'],['G02','Learning paths','book'],['G04','Units','grid'],['G11','Exercises','check'],['G13','Exams','file'],['G16','Resources','file'],['G19','Videos','play'],['G09','Topic index','book'],['G08','Search','search'],['S10','Progress','chart'],['S13','Messages','message'],['S16','Rewards','award'],['S22','Profile','user'],['S23','Settings','grid']];
    $adminNav = [['A01','Overview','grid'],['A02','Users','user'],['A05','Roles','lock'],['A07','Learning paths','book'],['A09','Units','grid'],['A11','Categories','grid'],['A13','Lessons','book'],['A16','Exercises','check'],['A20','Exams','file'],['A25','Resources','file'],['A27','Videos','play'],['A29','Topics','book'],['A31','Conversations','message'],['A33','Points rules','award'],['A35','Achievements','award'],['A37','Streak settings','clock'],['A38','Access rules','lock'],['A44','Analytics','chart'],['A43','Platform settings','grid']];
    $publicNav = [['G01','Home','grid'],['G02','Learning paths','book'],['G04','Units','grid'],['G11','Exercises','check'],['G13','Exams','file'],['G16','Resources','file'],['G19','Videos','play'],['G09','Topic index','book'],['G08','Search','search'],['G21','Create account','user'],['G22','Sign in','lock']];
    $nav = match($area) {'admin' => $adminNav, 'student' => $studentNav, default => $publicNav};
    $activeIds = array_column($breadcrumbs, 'id');
    $activeIds[] = $screen['id'];
    $activeNavId = collect(array_reverse($activeIds))->first(fn($id) => in_array($id, array_column($nav, 0)));
@endphp
<div class="prototype-strip"><span><span class="status-dot"></span> Visual prototype · Sample data only</span><a href="#screen-directory" data-open-directory>Screen directory <span aria-hidden="true">↗</span></a></div>
<header class="topbar">
    <div class="row"><button class="icon-button mobile-menu" aria-label="Open navigation" data-open-nav><x-prototype.icon name="menu"/></button><a class="brand" href="{{ $url('G01') }}"><span class="brand-mark"><x-prototype.icon/></span>English Atlas<span class="brand-tag">Learn. Practice. Grow.</span></a></div>
    <nav class="role-switch" aria-label="Prototype preview"><a href="{{ route('prototype.G01') }}" @if($area === 'public') aria-current="true" @endif>Explore</a><a href="{{ $url('S01') }}" @if($area === 'student') aria-current="true" @endif>Student</a><a href="{{ $url('A01') }}" @if($area === 'admin') aria-current="true" @endif>Admin</a></nav>
    <div class="row header-tools"><a class="icon-button" href="{{ $url('G08') }}" aria-label="Search learning content"><x-prototype.icon name="search"/></a><label class="language-label"><span class="sr-only">Preview interface language</span><select data-language aria-label="Preview interface language"><option value="en">EN</option><option value="fr">FR</option><option value="ar">العربية</option></select></label><a class="avatar" href="{{ $url('S22') }}" aria-label="Open Amina's sample profile">AB</a></div>
</header>
<div class="app-shell">
    <aside class="sidebar" aria-label="{{ $area === 'admin' ? 'Administration' : 'Learning' }} navigation">
        <div class="sidebar-intro"><span class="eyebrow" data-i18n="workspace">{{ match($area) {'admin' => 'Administration', 'student' => 'Your learning space', default => 'Discover your next step'} }}</span><strong>{{ match($area) {'admin' => 'Manage English Atlas', 'student' => '2nd Baccalaureate', default => 'Explore English Atlas'} }}</strong></div>
        <nav>@foreach($nav as [$id,$label,$icon])<a href="{{ $url($id) }}" @class(['nav-link','active' => $activeNavId === $id]) @if($activeNavId === $id) aria-current="{{ $screen['id'] === $id ? 'page' : 'location' }}" @endif><x-prototype.icon :name="$icon"/><span data-nav-id="{{ $id }}">{{ $label }}</span>@if(in_array($id,['S13','A31']))<span class="nav-count">2</span>@endif</a>@endforeach</nav>
        <div class="sidebar-note"><x-prototype.icon name="message"/><strong>A little help goes a long way.</strong><p>Your teacher is here when you need a hand.</p><a href="{{ $url('S14') }}">Ask your teacher <span aria-hidden="true">→</span></a></div>
    </aside>
    <main id="main" tabindex="-1">
        <div class="content">
            <nav class="breadcrumbs" aria-label="Breadcrumb">@foreach($breadcrumbs as $crumb)<a href="{{ $url($crumb['id']) }}">{{ $crumb['title'] }}</a><span aria-hidden="true">/</span>@endforeach<span aria-current="page">{{ $screen['title'] }}</span></nav>
            @yield('content')
            <footer class="page-footer"><span>English Atlas · A little progress, every day.</span><span>{{ $screen['id'] }} · Visual validation</span></footer>
        </div>
    </main>
</div>
<dialog id="mobile-navigation" aria-label="Navigation"><div class="row between"><strong>Navigation</strong><button class="icon-button" data-close-dialog aria-label="Close navigation"><x-prototype.icon name="close"/></button></div><nav>@foreach($nav as [$id,$label,$icon])<a class="nav-link" href="{{ $url($id) }}" @if($activeNavId === $id) aria-current="location" @endif><x-prototype.icon :name="$icon"/><span data-nav-id="{{ $id }}">{{ $label }}</span></a>@endforeach</nav></dialog>
<dialog id="screen-directory" aria-labelledby="directory-title"><div class="row between"><div><span class="eyebrow">Prototype map</span><h2 id="directory-title">Explore every screen</h2></div><button class="icon-button" data-close-dialog aria-label="Close screen directory"><x-prototype.icon name="close"/></button></div><p>Static previews. Forms and outcomes are simulated and do not save data.</p><label for="directory-filter">Find a screen</label><input id="directory-filter" type="search" placeholder="Search by name or screen ID" data-directory-filter><div class="directory-grid">@foreach(['G' => 'Public & shared','S' => 'Student','A' => 'Administration'] as $prefix => $group)<section><h3>{{ $group }}</h3>@foreach($screens as $entry)@if(str_starts_with($entry['id'],$prefix))<a class="directory-link" href="{{ $url($entry['id']) }}"><small>{{ $entry['id'] }}</small> {{ $entry['title'] }}</a>@endif @endforeach</section>@endforeach</div><p class="muted">Conditional subscriptions, leaderboards, advertising, and unresolved commercial workflows are outside this preview.</p></dialog>
</body>
</html>
