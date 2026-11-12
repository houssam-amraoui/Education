@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <x-prototype.card class="reading">
        <x-prototype.badge>Document exam</x-prototype.badge>
        <h2>Practise at your own pace.</h2>
        @if(filled($exam->instructions))
            <p dir="auto">{!! nl2br(e($exam->instructions)) !!}</p>
        @endif
        <div class="list-row">
            <div><h3>Exam paper</h3><small>PDF document</small></div>
            @if($paperAvailable)
                <x-prototype.link :href="route('curriculum.exams.document', [$exam, 'paper'])" variant="primary">Open exam</x-prototype.link>
            @else
                <span class="muted">File unavailable</span>
            @endif
        </div>
        <div class="list-row">
            <div><h3>Correction sheet</h3><small>PDF document</small></div>
            @if($correctionAvailable)
                <x-prototype.link :href="route('curriculum.exams.document', [$exam, 'correction'])">Open correction</x-prototype.link>
            @else
                <span class="muted">File unavailable</span>
            @endif
        </div>
        <x-prototype.link :href="route('library.resources.index', ['level_id' => $exam->unit->level_id])" variant="tertiary">Related resources →</x-prototype.link>
    </x-prototype.card>
@endsection
