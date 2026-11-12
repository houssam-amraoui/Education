@extends('prototype.layout')

@section('content')
    @include('curriculum.heading', ['description' => $resource->title])
    <div class="document-toolbar row between wrap">
        <x-prototype.link :href="$back">← Back to {{ $lesson ? 'lesson' : 'resource' }}</x-prototype.link>
        <span>PDF document</span>
        @if($available)
            <div class="actions">
                <x-prototype.link :href="$inline">Open PDF</x-prototype.link>
                <x-prototype.link :href="$download" variant="primary">Download PDF</x-prototype.link>
            </div>
        @endif
    </div>
    @if($available)
        <p class="notice info">If the PDF does not display, use Download PDF.</p>
        <article class="document-sheet reading">
            <object data="{{ $inline }}" type="application/pdf" width="100%" height="700" aria-label="{{ $resource->title }}">
                <p>Inline viewing is unavailable. <a href="{{ $download }}">Download the PDF</a>.</p>
            </object>
        </article>
    @else
        <p class="notice info" role="status">This file is currently unavailable.</p>
    @endif
@endsection
