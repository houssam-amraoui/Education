@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <div class="split">
        <div>
            @if($embed = $video->youtubeUrl(embed: true))
                <div class="video-stage video-stage-embed">
                    <iframe src="{{ $embed }}" title="{{ $video->title }}" loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </div>
                <p class="muted">If playback is unavailable, <a href="{{ $video->youtubeUrl() }}">watch on YouTube</a>.</p>
            @else
                <div class="video-stage">
                    <x-prototype.icon name="play"/>
                    <h2>Video unavailable</h2>
                    <p>This video cannot be played right now.</p>
                </div>
            @endif
            @if($video->description)
                <div class="section-heading"><h2>About this video</h2></div>
                <p dir="auto">{{ $video->description }}</p>
            @endif
        </div>
        <x-prototype.card>
            <h3>Keep learning</h3>
            @foreach($video->lessons as $lesson)
                <x-prototype.link :href="route('curriculum.lesson', [$lesson->category->unit->level, $lesson->category->unit, $lesson->category, $lesson])">
                    <bdi>{{ $lesson->title }}</bdi>
                </x-prototype.link>
            @endforeach
            @foreach($resources as $resource)
                <x-prototype.link :href="route('library.resources.show', $resource)"><bdi>{{ $resource->title }}</bdi></x-prototype.link>
            @endforeach
            @if($video->lessons->isEmpty() && $resources->isEmpty())
                <p>No related learning material is available yet.</p>
            @endif
            <x-prototype.link :href="$url('G19')" variant="tertiary">Browse videos →</x-prototype.link>
        </x-prototype.card>
    </div>
@endsection
