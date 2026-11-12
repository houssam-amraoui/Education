@extends('prototype.layout')

@section('content')
    @php
        $prefix = 'admin.curriculum.exercises';
        $editing = $exercise->exists;
        $value = fn ($field) => is_scalar(old($field, $exercise->$field)) ? old($field, $exercise->$field) : '';
    @endphp
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="split">
        <form class="stack" method="post" action="{{ $editing ? route($prefix.'.update', $exercise) : route($prefix.'.store') }}" data-live-form>
            @csrf
            @if($editing) @method('PUT') @endif
            <x-prototype.card>
                <div class="row between">
                    <h2>{{ $editing ? 'Edit' : 'Create' }} exercise</h2>
                    <x-prototype.badge>{{ ucfirst($exercise->status->value) }}</x-prototype.badge>
                </div>
                <x-prototype.field label="Title" name="title" :value="$value('title')" :required="true" :error="$errors->first('title')" maxlength="255" dir="auto"/>
                <div class="field">
                    <label for="unit_id">Learning path and unit</label>
                    <select id="unit_id" name="unit_id" required @disabled($units->isEmpty()) @if($errors->has('unit_id')) aria-invalid="true" aria-describedby="unit-error" @endif>
                        <option value="">Choose a unit</option>
                        @foreach($units as $pathUnits)
                            <optgroup label="{{ $pathUnits->first()->level->title }}">
                                @foreach($pathUnits as $unit)
                                    <option value="{{ $unit->id }}" @selected((string) $value('unit_id') === (string) $unit->id)>{{ $unit->title }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('unit_id')<small id="unit-error" class="field-error">{{ $message }}</small>@enderror
                    @if($units->isEmpty())<small>Create a unit before adding an exercise.</small>@endif
                </div>
                <x-prototype.field label="Instructions" name="instructions" type="textarea" :value="$value('instructions')" :required="true" :error="$errors->first('instructions')" maxlength="10000" dir="auto"/>
            </x-prototype.card>
            <div class="actions">
                <button class="button button-primary" data-pending="Saving…" @disabled($units->isEmpty())>Save exercise</button>
                <x-prototype.link :href="$url('A16')">Cancel</x-prototype.link>
            </div>
        </form>
        <aside class="stack">
            <x-prototype.card>
                <h3>Record settings</h3>
                @if($editing && $exercise->status->value !== 'archived')
                    <form method="post" action="{{ route($prefix.'.status', [$exercise->unit_id, $exercise]) }}" class="stack" data-live-form>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="return_to" value="editor">
                        <label class="field" for="status">State
                            <select id="status" name="status">
                                @foreach(['draft', 'published'] as $state)
                                    <option value="{{ $state }}" @selected($exercise->status->value === $state)>{{ ucfirst($state) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="button button-secondary" data-pending="Saving…">Save state</button>
                    </form>
                    @include('curriculum.lifecycle', ['kind' => 'exercise', 'record' => $exercise, 'showPublication' => false, 'returnTo' => 'editor'])
                @else
                    <label class="field" for="status">State
                        <select id="status" disabled><option>{{ ucfirst($exercise->status->value) }}</option></select>
                    </label>
                @endif
            </x-prototype.card>
            <x-prototype.card>
                <h3>Connected content</h3>
                @if($editing)
                    <x-prototype.link :href="route($prefix.'.questions.index', $exercise)">Manage questions</x-prototype.link>
                @else
                    <p>Save the exercise to add questions.</p>
                @endif
            </x-prototype.card>
        </aside>
    </div>
@endsection
