@extends('prototype.layout')

@section('content')
    @php
        $prefix = 'admin.library.resources';
        $editing = $resource->exists;
        $value = fn ($field) => is_scalar(old($field, $resource->$field)) ? old($field, $resource->$field) : '';
        $levelIds = array_map('strval', array_filter((array) old('level_ids', $resource->levels->modelKeys()), 'is_scalar'));
        $lessonIds = array_map('strval', array_filter((array) old('lesson_ids', $resource->lessons->modelKeys()), 'is_scalar'));
    @endphp
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="split">
        <form id="resource-editor" class="stack" method="post" enctype="multipart/form-data" action="{{ $editing ? route($prefix.'.update', $resource) : route($prefix.'.store') }}" data-live-form>
            @csrf
            @if($editing) @method('PUT') @endif
            <x-prototype.card>
                <div class="row between">
                    <h2>{{ $editing ? 'Edit' : 'Create' }} resource</h2>
                    @if($editing)<x-prototype.badge>{{ ucfirst($resource->status->value) }}</x-prototype.badge>@endif
                </div>
                <x-prototype.field label="Title" name="title" :value="$value('title')" :required="true" :error="$errors->first('title')" maxlength="255" dir="auto"/>
                <x-prototype.field label="Description" name="description" type="textarea" :value="$value('description')" :error="$errors->first('description')" maxlength="10000" dir="auto"/>
                <div class="field">
                    <label for="level_ids">Learning path</label>
                    <input type="hidden" name="level_ids" value="">
                    <select id="level_ids" name="level_ids[]" multiple size="3" aria-describedby="level-help" @disabled($levels->isEmpty())>
                        @foreach($levels as $level)
                            <option value="{{ $level->id }}" @selected(in_array((string) $level->id, $levelIds, true))>{{ $level->title }}</option>
                        @endforeach
                    </select>
                    <small id="level-help">{{ $levels->isEmpty() ? 'No learning paths are available.' : 'Leave unselected to make this resource available across all learning paths.' }}</small>
                </div>
                @if($resource->storedFile)<p>Current file <strong>PDF · {{ number_format($resource->storedFile->size / 1024, 1) }} KiB</strong></p>@endif
                <x-prototype.field label="Resource file" name="file" type="file" accept=".pdf,application/pdf"
                    :help="$resource->storedFile ? 'Choose a PDF up to 10 MiB to replace the current file.' : 'Choose a PDF up to 10 MiB. You can save a draft without a file.'"
                    :error="$errors->first('file')"/>
                <div class="field">
                    <label for="type">Resource type</label>
                    <select id="type" name="type" required @if($errors->has('type')) aria-invalid="true" aria-describedby="type-error" @endif>
                        @foreach(App\Models\Resource::TYPES as $type)
                            <option value="{{ $type }}" @selected($value('type') === $type)>{{ __("library.types.$type") }}</option>
                        @endforeach
                    </select>
                    @error('type')<small id="type-error" class="field-error">{{ $message }}</small>@enderror
                </div>
            </x-prototype.card>
            <div class="actions">
                <button class="button button-primary" data-pending="Saving…">Save resource</button>
                <x-prototype.link :href="$url('A25')">Cancel</x-prototype.link>
            </div>
        </form>
        <aside class="stack">
            <x-prototype.card>
                <h3>Record settings</h3>
                <div class="field">
                    <label for="access">Access</label>
                    <select id="access" name="access" form="resource-editor" aria-describedby="access-help{{ $errors->has('access') ? ' access-error' : '' }}" @if($errors->has('access')) aria-invalid="true" @endif>
                        @foreach(App\Models\Resource::ACCESS as $access)
                            <option value="{{ $access }}" @selected($value('access') === $access)>{{ __("library.access.$access") }}</option>
                        @endforeach
                    </select>
                    <small id="access-help">Access also depends on configured rules.</small>
                    @error('access')<small id="access-error" class="field-error">{{ $message }}</small>@enderror
                </div>
                @if($editing && $resource->status->value !== 'archived')
                    <form method="post" action="{{ route($prefix.'.status', $resource) }}" class="stack" data-live-form>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="return_to" value="editor">
                        <div class="field">
                            <label for="status">State</label>
                            <select id="status" name="status">
                                @foreach(['draft', 'published'] as $state)
                                    <option value="{{ $state }}" @selected($resource->status->value === $state)>{{ ucfirst($state) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="button button-secondary" data-pending="Saving…">Save state</button>
                    </form>
                    @include('curriculum.lifecycle', ['kind' => 'resource', 'record' => $resource, 'showPublication' => false, 'returnTo' => 'editor'])
                @else
                    <div class="field">
                        <label for="status">State</label>
                        <select id="status" disabled><option>{{ ucfirst($resource->status->value) }}</option></select>
                    </div>
                @endif
            </x-prototype.card>
            <x-prototype.card>
                <h3>Connected content</h3>
                @include('access.editor-link', ['accessRecord' => $resource])
                <div class="field">
                    <label for="lesson_ids">Lessons</label>
                    <input type="hidden" name="lesson_ids" form="resource-editor" value="">
                    @if($lessons->isEmpty())
                        <p>No lessons are available to link.</p>
                    @else
                        <select id="lesson_ids" name="lesson_ids[]" form="resource-editor" multiple size="5">
                            @foreach($lessons as $lesson)
                                <option value="{{ $lesson->id }}" @selected(in_array((string) $lesson->id, $lessonIds, true))>{{ $lesson->title }} · {{ $lesson->category->unit->level->title }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </x-prototype.card>
        </aside>
    </div>
@endsection
