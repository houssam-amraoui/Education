@extends('prototype.layout')

@section('content')
    @php
        $prefix = 'admin.curriculum.exams';
        $editing = $exam->exists;
        $value = fn ($field) => is_scalar(old($field, $exam->$field)) ? old($field, $exam->$field) : '';
    @endphp
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="split">
        <form id="exam-editor" class="stack" method="post" enctype="multipart/form-data" action="{{ $editing ? route($prefix.'.update', $exam) : route($prefix.'.store') }}" data-live-form>
            @csrf
            <input type="hidden" name="type" value="{{ $exam->type }}">
            @if($editing) @method('PUT') @endif
            <x-prototype.card>
                <div class="row between">
                    <h2>{{ $editing ? 'Edit' : 'Create' }} {{ $document ? 'document exam' : 'interactive exam' }}</h2>
                    <x-prototype.badge>{{ ucfirst($exam->status->value) }}</x-prototype.badge>
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
                    @if($units->isEmpty())<small>Create a unit before adding an exam.</small>@endif
                </div>
                @if($document)
                    @foreach(['exam_file' => ['Exam document', $exam->exam_file_id], 'correction_file' => ['Correction document', $exam->correction_file_id]] as $field => [$label, $fileId])
                        <x-prototype.field :label="$label" :name="$field" type="file" accept=".pdf,application/pdf"
                            :required="!$editing" :error="$errors->first($field)"
                            :help="$fileId ? 'PDF on file. Leave blank to keep it or choose a replacement up to 10 MiB.' : 'Choose a PDF up to 10 MiB.'"/>
                    @endforeach
                @else
                    <x-prototype.field label="Instructions" name="instructions" type="textarea" :value="$value('instructions')" :required="true" :error="$errors->first('instructions')" maxlength="10000" dir="auto"/>
                    <div class="field">
                        <label for="timing_mode">Timing</label>
                        <select id="timing_mode" name="timing_mode" required @if($errors->has('timing_mode')) aria-invalid="true" aria-describedby="timing-error" @endif>
                            <option value="timed" @selected(old('timing_mode', $exam->duration_minutes === null ? 'untimed' : 'timed') === 'timed')>Timed</option>
                            <option value="untimed" @selected(old('timing_mode', $exam->duration_minutes === null ? 'untimed' : 'timed') === 'untimed')>Untimed</option>
                        </select>
                        @error('timing_mode')<small class="field-error" id="timing-error">{{ $message }}</small>@enderror
                    </div>
                    <x-prototype.field label="Duration (minutes)" name="duration_minutes" type="number" :value="$value('duration_minutes')" min="1" max="65535"
                        help="Required for timed exams." :error="$errors->first('duration_minutes')"/>
                @endif
                @foreach(['available_from' => 'Available from (UTC)', 'available_until' => 'Available until (UTC)'] as $field => $label)
                    @php($date = old($field, $exam->$field?->utc()->format('Y-m-d\TH:i:s')))
                    <x-prototype.field :label="$label" :name="$field" type="datetime-local"
                        :value="is_string($date) ? $date : ''" step="1" :error="$errors->first($field)"/>
                @endforeach
            </x-prototype.card>
            <div class="actions">
                <button class="button button-primary" data-pending="Saving…" @disabled($units->isEmpty())>Save exam</button>
                <x-prototype.link :href="$url('A20')">Cancel</x-prototype.link>
            </div>
        </form>
        <aside class="stack">
            <x-prototype.card>
                <h3>Record settings</h3>
                <div class="field">
                    <label for="access">Access</label>
                    <select id="access" name="access" form="exam-editor" @if($errors->has('access')) aria-invalid="true" aria-describedby="access-error" @endif>
                        <option value="public" @selected($value('access') === 'public')>Public</option>
                        <option value="registered" @selected($value('access') === 'registered')>Registered users</option>
                    </select>
                    @error('access')<small class="field-error" id="access-error">{{ $message }}</small>@enderror
                </div>
                @if($editing && $exam->status->value !== 'archived')
                    <form method="post" action="{{ route($prefix.'.status', [$exam->unit_id, $exam]) }}" class="stack" data-live-form>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="return_to" value="editor">
                        <label class="field" for="status">State
                            <select id="status" name="status">
                                @foreach(['draft', 'published'] as $state)
                                    <option value="{{ $state }}" @selected($exam->status->value === $state)>{{ ucfirst($state) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="button button-secondary" data-pending="Saving…">Save state</button>
                    </form>
                    @include('curriculum.lifecycle', ['kind' => 'exam', 'record' => $exam, 'showPublication' => false, 'returnTo' => 'editor'])
                @else
                    <label class="field" for="status">State
                        <select id="status" disabled><option>{{ ucfirst($exam->status->value) }}</option></select>
                    </label>
                @endif
            </x-prototype.card>
            @if(!$document || $editing)
                <x-prototype.card>
                    <h3>Connected content</h3>
                @include('access.editor-link', ['accessRecord' => $exam])
                    @if($editing && !$document)
                        <x-prototype.link :href="route($prefix.'.questions.index', $exam)">Manage questions</x-prototype.link>
                    @elseif(!$editing)
                        <p>Save the exam to add questions.</p>
                    @endif
                    @if($editing)
                        @include('curriculum.prerequisite-form', ['prerequisiteAction' => route($prefix.'.prerequisites.update', $exam)])
                    @endif
                </x-prototype.card>
            @endif
        </aside>
    </div>
@endsection
