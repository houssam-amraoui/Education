@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <div class="actions history-actions">
        <x-prototype.link :href="$url('S10')">Back to progress</x-prototype.link>
        <x-prototype.link :href="$url('G11')" variant="primary">Explore exercises</x-prototype.link>
    </div>
    @include('progress.table', ['empty' => 'Completed learning and scores will appear here.'])
@endsection
