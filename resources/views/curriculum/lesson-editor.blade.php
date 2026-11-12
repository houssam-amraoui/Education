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
                <x-prototype.field label="Image or document attachment" name="lesson-attachment" type="file" disabled help="File attachments are unavailable."/>
                <x-prototype.field label="Embedded video URL" name="lesson-video" type="url" disabled help="Embedded videos are unavailable."/>
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
