@extends('prototype.layout')

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">{{ $area === 'admin' ? 'Teaching workspace' : 'Your English journey' }}</span>
            <h1>{{ match($panel) {'home' => 'Your account', 'restricted' => 'Access restricted', default => $screen['title']} }}</h1>
            @if($panel !== 'restricted')
                <p>{{ $area === 'admin' ? 'Organize the next step in your students’ learning.' : 'Build your confidence, one step at a time.' }}</p>
            @endif
        </div>
    </div>
    @if(session('status'))
        <p class="notice success" role="status">{{ session('status') }}</p>
    @endif
    @if($errors->any())
        <div class="validation-summary" role="alert" tabindex="-1" data-live-errors>
            <strong>{{ __('shared.feedback.validation_summary') }}</strong>
            <ul>
                @foreach($errors->getMessages() as $key => $messages)
                    @php($field = Str::before($key, '.'))
                    @foreach($messages as $message)
                        <li><a href="#{{ $field }}">{{ $message }}</a></li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif
    @if($panel === 'home')
        <x-prototype.card class="stack">
            <h2 dir="auto">{{ auth()->user()->name }}</h2>
            <p><bdi>{{ auth()->user()->email }}</bdi></p>
            <div class="actions">
                @if(isset($destinations['A02']))<a class="button button-primary" href="{{ $url('A02') }}">Manage users</a>@endif
                @if(isset($destinations['A05']))<a class="button button-secondary" href="{{ $url('A05') }}">Manage roles</a>@endif
                <form method="post" action="{{ route('account.logout') }}" data-live-form>
                    @csrf
                    <button class="button button-secondary" data-pending="Signing out…">Sign out</button>
                </form>
            </div>
        </x-prototype.card>
    @elseif($panel === 'restricted')
        <div class="notice danger" role="alert"><x-prototype.icon name="lock"/><p>Your account does not have permission to access this page or make this change.</p></div>
        <a class="button button-secondary" href="{{ route('account.home') }}">Back to your account</a>
    @else
        @include('account.'.$panel)
    @endif
@endsection
