@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    @include('curriculum.feedback')
    @php
        $prefix = 'admin.curriculum.lessons';
        $parameters = [$category, $lesson];
        $archived = $lesson->status === App\Support\CurriculumStatus::Archived;
    @endphp
    <div class="split">
        <form class="stack" method="post" action="{{ $lesson->exists ? route($prefix.'.update', $parameters) : route($prefix.'.store', $category) }}" data-live-form>
            @csrf
            @if($lesson->exists)@method('PUT')@endif
            <x-prototype.card>
                <div class="row between"><h2>{{ $lesson->exists ? 'Edit' : 'Create' }} lesson</h2></div>
                <x-account.field label="Title" name="title" :value="$lesson->title" maxlength="255" required dir="auto"/>
                <x-prototype.field label="Learning path" name="placement" :value="$category->unit->level->title" readonly dir="auto"/>
                <x-prototype.field label="Unit" name="unit-placement" :value="$category->unit->title" readonly dir="auto"/>
                <x-prototype.field label="Category" name="category-placement" :value="$category->title" readonly dir="auto"/>
                <x-account.field label="Lesson content" name="body" type="textarea" :value="$lesson->body" rows="8" maxlength="500000" dir="auto" help="Use plain text or HTML for headings, paragraphs, lists, and links."/>
                <div class="field">
                    <strong>Image or document attachment</strong>
                    @if($lesson->exists)
                        @forelse($lesson->resources as $resource)
                            <small dir="auto">{{ $resource->title }}</small>
                        @empty
                            <small>No resources attached.</small>
                        @endforelse
                        <a href="{{ route($prefix.'.relationships', $parameters) }}#resource_ids">Manage resource attachments</a>
                    @else
                        <small>Save this lesson to attach resources.</small>
                    @endif
                </div>
                <div class="field">
                    <label for="video_ids">Embedded videos</label>
                    <input type="hidden" name="video_ids" value="">
                    @php($videoIds = array_map('strval', array_filter((array) old('video_ids', $selectedVideos), 'is_scalar')))
                    <select id="video_ids" name="video_ids[]" multiple size="3" aria-describedby="lesson-videos-help" @disabled($videos->isEmpty()) @if($errors->hasAny(['video_ids', 'video_ids.*'])) aria-invalid="true" @endif>
                        @foreach($videos as $video)
                            <option value="{{ $video->id }}" @selected(in_array((string) $video->id, $videoIds, true))>{{ $video->title }} · {{ ucfirst($video->status->value) }}</option>
                        @endforeach
                    </select>
                    <small id="lesson-videos-help">{{ $videos->isEmpty() ? 'No videos are available to link.' : 'Only videos the learner can access appear in the lesson.' }}</small>
                    @if($errors->hasAny(['video_ids', 'video_ids.*']))<small class="field-error">{{ $errors->first('video_ids') ?: $errors->first('video_ids.*') }}</small>@endif
                </div>
            </x-prototype.card>
            <div class="actions">
                <button class="button button-primary" data-pending="Saving…">Save lesson</button>
                <x-prototype.link :href="$url('A13')">Cancel</x-prototype.link>
            </div>
        </form>
        <aside class="stack">
            <x-prototype.card>
                <h3>Record settings</h3>
                @if($lesson->exists && !$archived)
                    <form method="post" action="{{ route($prefix.'.status', $parameters) }}" class="stack" data-live-form>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="return_to" value="editor">
                        <div class="field">
                            <label for="status">State</label>
                            <select id="status" name="status">
                                @foreach(['draft', 'published'] as $state)
                                    <option value="{{ $state }}" @selected($lesson->status->value === $state)>{{ ucfirst($state) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="button button-secondary" data-pending="Saving…">Save state</button>
                    </form>
                    @include('curriculum.lifecycle', ['kind' => 'lesson', 'record' => $lesson, 'showPublication' => false, 'returnTo' => 'editor'])
                @else
                    <div class="field">
                        <label for="status">State</label>
                        <select id="status" disabled><option>{{ ucfirst($lesson->status->value) }}</option></select>
                    </div>
                    @unless($lesson->exists)<p>Save this lesson to manage its state.</p>@endunless
                @endif
            </x-prototype.card>
            <x-prototype.card>
                <h3>Connected content</h3>
                @if($lesson->exists)
                    <x-prototype.link :href="route($prefix.'.relationships', $parameters)">Relationships &amp; prerequisites</x-prototype.link>
                    <x-prototype.link :href="route($prefix.'.preview', $parameters)" variant="tertiary">Preview lesson →</x-prototype.link>
                @else
                    <p>Save this lesson to manage its relationships.</p>
                @endif
            </x-prototype.card>
        </aside>
    </div>
@endsection
