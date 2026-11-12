@php($registering = $screen['id'] === 'G21')
<div class="auth-layout">
    <div class="auth-intro">
        <span class="tile-icon"><x-prototype.icon/></span>
        <h2>Your next chapter starts with a small step.</h2>
        <p>Learn at your pace, practise with purpose, and find support when you need it.</p>
        <blockquote>“A little progress, every day.”</blockquote>
    </div>
    <x-prototype.card>
        <form method="post" action="{{ route($registering ? 'account.register' : 'account.login') }}" class="stack" data-live-form>
            @csrf
            <h2>{{ $registering ? 'Start your learning journey' : 'Continue your learning journey' }}</h2>
            @if($registering)
                <x-account.field label="Full name" name="name" :required="true" autocomplete="name" maxlength="255"/>
            @endif
            <x-account.field label="Email address" name="email" type="email" :required="true" autocomplete="email" maxlength="255"/>
            <x-account.field label="Password" name="password" type="password" :required="true"
                :autocomplete="$registering ? 'new-password' : 'current-password'"
                :minlength="$registering ? 8 : null" :help="$registering ? 'Use at least 8 characters.' : null"/>
            @if($registering)
                <x-account.field label="Confirm password" name="password_confirmation" type="password" :required="true" autocomplete="new-password"/>
            @endif
            <button class="button button-primary" data-pending="{{ $registering ? 'Creating account…' : 'Signing in…' }}">{{ $registering ? 'Create account' : 'Sign in' }}</button>
            <a href="{{ $url($registering ? 'G22' : 'G21') }}">{{ $registering ? 'Already have an account? Sign in' : 'New here? Create an account' }}</a>
        </form>
    </x-prototype.card>
</div>
