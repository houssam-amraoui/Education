@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <div class="split">
        <div>
            @include('videos.player')
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
