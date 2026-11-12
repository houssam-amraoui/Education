@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <form method="post" action="{{ route('admin.library.topics.relationships.update', $topic) }}" class="reading stack card" data-live-form>
        @csrf
        @method('PUT')
        <h2 dir="auto">{{ $topic->title }}</h2>
        <p>Choose related learning content.</p>
        @foreach($options as $type => $items)
            @php
                $field = $type.'_ids';
                $checked = array_map('strval', array_filter((array) old($field, $selected[$type]), 'is_scalar'));
                $invalid = $errors->has($field) || $errors->has($field.'.*');
            @endphp
            <fieldset id="{{ $field }}" @if($invalid) aria-describedby="{{ $field }}-error" @endif>
                <legend>{{ ucfirst($type) }}s</legend>
                <input type="hidden" name="{{ $field }}" value="">
                @forelse($items as $item)
                    <label class="choice">
                        <input type="checkbox" name="{{ $field }}[]" value="{{ $item->id }}" @checked(in_array((string) $item->id, $checked, true))>
                        <bdi>{{ $item->title }}</bdi>
                    </label>
                @empty
                    <p>No {{ $type }}s are available to link.</p>
                @endforelse
                @if($invalid)<p id="{{ $field }}-error" class="field-error">{{ $errors->first($field) ?: $errors->first($field.'.*') }}</p>@endif
            </fieldset>
        @endforeach
        <div class="actions">
            <button class="button button-primary" data-pending="Saving…">Save relationships</button>
            <x-prototype.link :href="$url('A30')">Back to topic editor</x-prototype.link>
        </div>
    </form>
@endsection
