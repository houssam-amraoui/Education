@extends('prototype.layout')

@section('content')
    @php
        $prefix = 'admin.library.videos';
        $editing = $video->exists;
        $value = fn ($field) => is_scalar(old($field, $video->$field)) ? old($field, $video->$field) : '';
        $levelIds = array_map('strval', array_filter((array) old('level_ids', $video->levels->modelKeys()), 'is_scalar'));
        $lessonIds = array_map('strval', array_filter((array) old('lesson_ids', $video->lessons->modelKeys()), 'is_scalar'));
    @endphp
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="split">
        <form id="video-editor" class="stack" method="post" action="{{ $editing ? route($prefix.'.update', $video) : route($prefix.'.store') }}" data-live-form>
            @csrf
            @if($editing) @method('PUT') @endif
            <x-prototype.card>
                <div class="row between">
                    <h2>{{ $editing ? 'Edit' : 'Create' }} video</h2>
                    @if($editing)<x-prototype.badge>{{ ucfirst($video->status->value) }}</x-prototype.badge>@endif
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
                    <small id="level-help">{{ $levels->isEmpty() ? 'No learning paths are available.' : 'Leave unselected to make this video available across all learning paths.' }}</small>
                </div>
                <div class="field">
                    <label for="video-source">Video source</label>
                    <select id="video-source"><option>YouTube</option></select>
                </div>
                <x-account.field label="Video URL" name="source_url" type="url" :value="$video->youtubeUrl()"
                    placeholder="https://www.youtube.com/watch?v=…" maxlength="2048" required dir="ltr"/>
            </x-prototype.card>
            <div class="actions">
                <button class="button button-primary" data-pending="Saving…">Save video</button>
                <x-prototype.link :href="$url('A27')">Cancel</x-prototype.link>
            </div>
        </form>
        <aside class="stack">
            <x-prototype.card>
                <h3>Record settings</h3>
                <div class="field">
                    <label for="access">Access</label>
                    <select id="access" name="access" form="video-editor" aria-describedby="access-help{{ $errors->has('access') ? ' access-error' : '' }}" @if($errors->has('access')) aria-invalid="true" @endif>
                        @foreach(App\Models\Video::ACCESS as $access)
                            <option value="{{ $access }}" @selected($value('access') === $access)>{{ __("library.access.$access") }}</option>
                        @endforeach
                    </select>
                    <small id="access-help">Learners can open published Free and Registered-user only videos.</small>
                    @error('access')<small id="access-error" class="field-error">{{ $message }}</small>@enderror
                </div>
                @if($editing && $video->status->value !== 'archived')
                    <form method="post" action="{{ route($prefix.'.status', $video) }}" class="stack" data-live-form>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="return_to" value="editor">
                        <div class="field">
                            <label for="status">State</label>
                            <select id="status" name="status">
                                @foreach(['draft', 'published'] as $state)
                                    <option value="{{ $state }}" @selected($video->status->value === $state)>{{ ucfirst($state) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="button button-secondary" data-pending="Saving…">Save state</button>
                    </form>
                    @include('curriculum.lifecycle', ['kind' => 'video', 'record' => $video, 'showPublication' => false, 'returnTo' => 'editor'])
                @else
                    <div class="field">
                        <label for="status">State</label>
                        <select id="status" disabled><option>{{ ucfirst($video->status->value) }}</option></select>
                    </div>
                @endif
            </x-prototype.card>
            <x-prototype.card>
                <h3>Connected content</h3>
                <div class="field">
                    <label for="lesson_ids">Lessons</label>
                    <input type="hidden" name="lesson_ids" form="video-editor" value="">
                    @if($lessons->isEmpty())
                        <p>No lessons are available to link.</p>
                    @else
                        <select id="lesson_ids" name="lesson_ids[]" form="video-editor" multiple size="5">
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
