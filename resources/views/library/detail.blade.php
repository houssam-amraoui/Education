@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <div class="split">
        <x-prototype.card class="reading">
            <span class="tile-icon"><x-prototype.icon name="file"/></span>
            <x-access-labels :record="$resource" :suffix='__("library.types.$resource->type")'/>
            <h2 dir="auto">{{ $resource->title }}</h2>
            @if($resource->description)<p dir="auto">{{ $resource->description }}</p>@endif
            <dl class="details">
                <div><dt>Level</dt><dd dir="auto">{{ $resource->levels->pluck('title')->implode(', ') ?: 'All learning paths' }}</dd></div>
                <div><dt>Format</dt><dd>PDF</dd></div>
            </dl>
            @if($available)
                <x-prototype.link :href="route('library.resources.document', $resource)" variant="primary">Open document <x-prototype.icon name="arrow"/></x-prototype.link>
                <x-prototype.link :href="route('library.resources.file', [$resource, 'attachment'])">Download PDF</x-prototype.link>
            @else
                <p class="notice info" role="status">This file is currently unavailable.</p>
            @endif
        </x-prototype.card>
        <x-prototype.card>
            <h3>Put it into context</h3>
            @forelse($resource->lessons as $lesson)
                <x-prototype.link :href="route('curriculum.lesson', [$lesson->category->unit->level, $lesson->category->unit, $lesson->category, $lesson])"><bdi>{{ $lesson->title }}</bdi></x-prototype.link>
            @empty
                <p>No related lessons yet.</p>
            @endforelse
        </x-prototype.card>
    </div>
@endsection
