@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    @isset($preview)
        <p class="notice info">Management preview · {{ ucfirst($lesson->status->value) }}</p>
    @endisset
    <div class="split">
        <article class="reading">
            <div class="row wrap">
                <x-prototype.badge tone="info"><bdi>{{ $lesson->category->title }}</bdi></x-prototype.badge>
                @empty($preview)<x-prototype.badge>Free</x-prototype.badge>@endempty
            </div>
            @php($body = App\Support\LessonRichText::clean($lesson->body ?? ''))
            @if(trim($body) !== '')
                <div class="lesson-body [overflow-wrap:anywhere]" dir="auto">{!! $body !!}</div>
            @else
                <div class="empty-state"><h2>No lesson content yet</h2></div>
            @endif
        </article>
        <aside class="stack">
            <x-prototype.card>
                <h3>In this lesson</h3>
                <p dir="auto">{{ $lesson->title }}</p>
                <small dir="auto">{{ $lesson->category->unit->title }}</small>
            </x-prototype.card>
            @if($lesson->nextLessons->isNotEmpty())
                <x-prototype.card>
                    <h3>Next lessons</h3>
                    @foreach($lesson->nextLessons as $next)
                        <a href="{{ route('curriculum.lesson', [$next->category->unit->level, $next->category->unit, $next->category, $next]) }}" dir="auto">{{ $next->title }}</a>
                    @endforeach
                </x-prototype.card>
            @endif
            @isset($preview)
                <x-prototype.link :href="$url('A14')">Back to lesson editor</x-prototype.link>
            @else
                <x-prototype.link :href="$url('G06')">Browse next lessons <x-prototype.icon name="arrow"/></x-prototype.link>
            @endisset
        </aside>
    </div>
@endsection
