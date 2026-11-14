@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    @include('curriculum.feedback')
    @php
        $choices = array_map('strval', array_filter((array) old('next_lesson_ids', $selected->all()), 'is_scalar'));
        $resourceChoices = array_map('strval', array_filter((array) old('resource_ids', $attached->all()), 'is_scalar'));
    @endphp
    <form method="post" class="reading stack card" action="{{ route('admin.curriculum.lessons.next-lessons.update', [$category, $lesson]) }}" data-live-form>
        @csrf
        @method('PUT')
        <input type="hidden" name="next_lesson_ids" value="">
        <h2 dir="auto">{{ $lesson->title }}</h2>
        <p>Choose related content and recommended preparation.</p>
        @include('curriculum.prerequisite-fields')
        <fieldset id="resource_ids">
            <legend>Related learning content</legend>
            @foreach(['exercise_ids' => ['Exercise', 'exercises'], 'exam_ids' => ['Exam', 'exams']] as $field => [$label, $plural])
                @php
                    [$linkedIds, $options] = $assessments[$field];
                    $checkedIds = array_map('strval', array_filter((array) old($field, $linkedIds->all()), 'is_scalar'));
                @endphp
                <div id="{{ $field }}">
                    <input type="hidden" name="{{ $field }}" value="">
                    @forelse($options as $option)
                        @php($archived = $option->status === App\Support\CurriculumStatus::Archived)
                        <label class="choice">
                            <input type="checkbox" name="{{ $field }}[]" value="{{ $option->id }}"
                                @checked(in_array((string) $option->id, $checkedIds, true)) @disabled($archived)>
                            <span><bdi>{{ $option->title }}</bdi><br>
                                <small>{{ $label }} · <bdi>{{ $option->unit->level->title }} · {{ $option->unit->title }}</bdi>
                                    · {{ $archived ? 'Archived (removed on save)' : ucfirst($option->status->value) }}</small></span>
                        </label>
                    @empty
                        <p>No {{ $plural }} are available to link.</p>
                    @endforelse
                </div>
            @endforeach
            <input type="hidden" name="resource_ids" value="">
            @forelse($resources as $resource)
                <label class="choice">
                    <input type="checkbox" name="resource_ids[]" value="{{ $resource->id }}" @checked(in_array((string) $resource->id, $resourceChoices, true))>
                    <span><bdi>{{ $resource->title }}</bdi><br>
                        <small>{{ __("library.types.$resource->type") }} · {{ ucfirst($resource->status->value) }}</small></span>
                </label>
            @empty
                <p>No resources are available to attach.</p>
                <x-prototype.link :href="$url('A25')">Manage resources</x-prototype.link>
            @endforelse
        </fieldset>
        <fieldset><legend>Topics</legend><small class="muted">Unavailable</small></fieldset>
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
