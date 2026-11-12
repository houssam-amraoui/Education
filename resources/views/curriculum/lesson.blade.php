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
            @if($canTrack ?? false)
                <div class="actions">
                    @if($completed)
                        <x-prototype.badge tone="success"><x-prototype.icon name="check"/> Completed</x-prototype.badge>
                    @else
                        <form method="post" action="{{ route('curriculum.lesson.complete', [$lesson->category->unit->level_id, $lesson->category->unit_id, $lesson->category_id, $lesson]) }}" data-live-form>
                            @csrf
                            <button class="button button-primary" data-pending="Saving…"><x-prototype.icon name="check"/> Mark as complete</button>
                        </form>
                    @endif
                </div>
                @include('curriculum.feedback')
            @endif
        </article>
        <aside class="stack">
            <x-prototype.card>
                <h3>In this lesson</h3>
                <p dir="auto">{{ $lesson->title }}</p>
                <small dir="auto">{{ $lesson->category->unit->title }}</small>
                @if($canTrack ?? false)
                    <x-prototype.progress :value="$completed ? 100 : 0" label="Lesson completion"/>
                @endif
            </x-prototype.card>
            @if($lesson->relationLoaded('videos') && $lesson->videos->isNotEmpty())
                <x-prototype.card>
                    <h3>Videos</h3>
                    @foreach($lesson->videos as $video)
                        <x-prototype.link :href="route('library.videos.show', $video)"><bdi>{{ $video->title }}</bdi></x-prototype.link>
                    @endforeach
                </x-prototype.card>
            @endif
            @if($lesson->relationLoaded('resources') && $lesson->resources->isNotEmpty())
                <x-prototype.card>
                    <h3>Documents and resources</h3>
                    @foreach($lesson->resources as $resource)
                        <x-prototype.link :href="route('library.lessons.resources.document', [$lesson, $resource])"><bdi>{{ $resource->title }}</bdi></x-prototype.link>
                    @endforeach
                </x-prototype.card>
            @endif
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
