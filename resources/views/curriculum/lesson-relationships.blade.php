@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    @include('curriculum.feedback')
    @php($choices = array_map('strval', array_filter((array) old('next_lesson_ids', $selected->all()), 'is_scalar')))
    <form method="post" class="reading stack card" action="{{ route('admin.curriculum.lessons.next-lessons.update', [$category, $lesson]) }}" data-live-form>
        @csrf
        @method('PUT')
        <input type="hidden" name="next_lesson_ids" value="">
        <h2 dir="auto">{{ $lesson->title }}</h2>
        <p>Choose related content and recommended preparation.</p>
        @foreach(['Prerequisite lessons', 'Related learning content', 'Topics'] as $label)
            <fieldset><legend>{{ $label }}</legend><small class="muted">Unavailable</small></fieldset>
        @endforeach
        <fieldset id="next_lesson_ids">
            <legend>Next lesson</legend>
            @forelse($candidates as $candidate)
                @php($archived = $candidate->status === App\Support\CurriculumStatus::Archived)
                <label class="choice">
                    <input type="checkbox" name="next_lesson_ids[]" value="{{ $candidate->id }}"
                        @checked(in_array((string) $candidate->id, $choices, true)) @disabled($archived)>
                    <span><bdi>{{ $candidate->title }}</bdi><br>
                        <small><bdi>{{ $candidate->category->unit->level->title }} · {{ $candidate->category->unit->title }} · {{ $candidate->category->title }}</bdi>
                            @if($archived) · Archived (removed on save) @endif</small></span>
                </label>
            @empty
                <p>No other lessons are available to link.</p>
            @endforelse
        </fieldset>
        <div class="actions">
            <button class="button button-primary" data-pending="Saving…">Save relationships</button>
            <x-prototype.link :href="$url('A14')">Back to lesson editor</x-prototype.link>
        </div>
    </form>
@endsection
