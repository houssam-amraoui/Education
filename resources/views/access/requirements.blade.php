@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <x-prototype.card class="reading">
        <span class="tile-icon"><x-prototype.icon name="lock"/></span>
        <h2 dir="auto">{{ $targetTitle }}</h2>
        <div class="stack">
            @forelse($rules as $rule)
                <div class="notice info"><div>
                    <strong>{{ __("library.access.$rule->requirement") }}</strong>
                    @switch($rule->requirement)
                        @case('registered') <p>Sign in with a student account to continue.</p> @break
                        @case('level')
                            @if(isset($levels[$rule->required_level_id]))
                                <p>Select <bdi>{{ $levels[$rule->required_level_id] }}</bdi> in your learning goals.</p>
                            @else
                                <p>The required learning level is currently unavailable.</p>
                            @endif
                            @break
                        @case('prerequisite')
                            @php($lesson = $prerequisites[$rule->required_lesson_id] ?? null)
                            @if($lesson)
                                <p>Complete <bdi>{{ $lesson->title }}</bdi> before continuing.</p>
                                <x-prototype.link :href="App\Support\AccessTarget::destination('lesson', $lesson)">Open prerequisite lesson</x-prototype.link>
                            @else
                                <p>The prerequisite lesson is currently unavailable.</p>
                            @endif
                            @break
                        @case('points') <p>{{ $cost ? "This unlock costs {$cost->points} points." : ($isStudent ? 'This points unlock is currently unavailable.' : 'Sign in to check whether you can unlock this with points.') }}</p> @break
                        @case('premium') <p>This destination requires premium access.</p> @break
                        @default <p>This access requirement is currently unavailable.</p>
                    @endswitch
                </div></div>
            @empty
                <p class="notice info">Access is currently unavailable.</p>
            @endforelse
        </div>
        <div class="actions">
            @guest
                <x-prototype.link :href="route('account.login.show', ['return_to' => $returnTo])" variant="primary">Sign in</x-prototype.link>
                <x-prototype.link :href="route('account.register.show', ['return_to' => $returnTo])">Create an account</x-prototype.link>
            @endguest
            @if($isStudent && $levels->isNotEmpty())
                <x-prototype.link :href="route('account.learning-goals.show', ['return_to' => $returnTo])">Choose learning goals</x-prototype.link>
            @endif
            @if($unlockUrl)<x-prototype.link :href="$unlockUrl" variant="primary">Review points unlock</x-prototype.link>@endif
            <x-prototype.link :href="$back">Go back</x-prototype.link>
        </div>
    </x-prototype.card>
@endsection
