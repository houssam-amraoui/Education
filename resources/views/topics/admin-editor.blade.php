@extends('prototype.layout')

@section('content')
    @php
        $prefix = 'admin.library.topics';
        $editing = $topic->exists;
    @endphp
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="split">
        <form class="stack" method="post" action="{{ $editing ? route($prefix.'.update', $topic) : route($prefix.'.store') }}" data-live-form>
            @csrf
            @if($editing) @method('PUT') @endif
            <x-prototype.card>
                <div class="row between">
                    <h2>{{ $editing ? 'Edit' : 'Create' }} topic</h2>
                    @if($editing)<x-prototype.badge>{{ ucfirst($topic->status->value) }}</x-prototype.badge>@endif
                </div>
                <x-account.field label="Title" name="title" :value="$topic->title" maxlength="255" required dir="auto"/>
                <x-account.field label="Description" name="description" type="textarea" :value="$topic->description" maxlength="10000" dir="auto"/>
            </x-prototype.card>
            <div class="actions">
                <button class="button button-primary" data-pending="Saving…">Save topic</button>
                <x-prototype.link :href="$url('A29')">Cancel</x-prototype.link>
            </div>
        </form>
        <aside class="stack">
            <x-prototype.card>
                <h3>Record settings</h3>
                @if($editing && $topic->status->value !== 'archived')
                    <form method="post" action="{{ route($prefix.'.status', $topic) }}" class="stack" data-live-form>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="return_to" value="editor">
                        <div class="field">
                            <label for="status">State</label>
                            <select id="status" name="status">
                                @foreach(['draft', 'published'] as $state)
                                    <option value="{{ $state }}" @selected($topic->status->value === $state)>{{ ucfirst($state) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="button button-secondary" data-pending="Saving…">Save state</button>
                    </form>
                    @include('curriculum.lifecycle', ['kind' => 'topic', 'record' => $topic, 'showPublication' => false, 'returnTo' => 'editor'])
                @else
                    <div class="field">
                        <label for="status">State</label>
                        <select id="status" disabled><option>{{ ucfirst($topic->status->value) }}</option></select>
                    </div>
                @endif
            </x-prototype.card>
            <x-prototype.card>
                <h3>Connected content</h3>
                @if($editing)
                    <x-prototype.link :href="route($prefix.'.relationships', $topic)">Relationships &amp; prerequisites</x-prototype.link>
                @else
                    <p>Save this topic to manage its relationships.</p>
                @endif
            </x-prototype.card>
        </aside>
    </div>
@endsection
