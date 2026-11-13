@extends('prototype.layout')

@section('content')
    @php
        $plural = $kind === 'category' ? 'categories' : $kind.'s';
        $prefix = 'admin.curriculum.'.$plural;
        $label = $kind === 'level' ? 'learning path' : $kind;
        $oldUnit = old('unit_id', $record->unit_id);
        $oldLevel = old('level_id', $record->level_id);
        $selectedUnit = $kind === 'category' && is_scalar($oldUnit) ? $parents->find($oldUnit) : null;
        $canPublish = $record->exists && $kind !== 'category' && $record->status !== App\Support\CurriculumStatus::Archived;
        $statusParameters = $kind === 'level' ? [$record] : [$record->level_id, $record];
    @endphp
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="split">
        <form class="stack" method="post" action="{{ $record->exists ? route($prefix.'.update', $record) : route($prefix.'.store') }}" data-live-form>
            @csrf
            @if($record->exists)@method('PUT')@endif
            <x-prototype.card>
                <div class="row between"><h2>{{ $record->exists ? 'Edit' : 'Create' }} {{ $label }}</h2></div>
                <x-account.field label="Title" name="title" :value="$record->title" maxlength="255" required dir="auto"/>
                <x-account.field label="Description" name="description" type="textarea" :value="$record->description" maxlength="5000" dir="auto"/>
                @if($kind === 'unit')
                    <div class="field">
                        <label for="level_id">Learning path</label>
                        <select id="level_id" name="level_id" required @error('level_id') aria-invalid="true" aria-describedby="level-error" @enderror>
                            <option value="">Select a learning path</option>
                            @foreach($parents as $option)
                                <option value="{{ $option->id }}" @selected(is_scalar($oldLevel) && (string) $oldLevel === (string) $option->id)>{{ $option->title }}</option>
                            @endforeach
                        </select>
                        @error('level_id')<small class="field-error" id="level-error">{{ $message }}</small>@enderror
                    </div>
                @elseif($kind === 'category')
                    <x-prototype.field label="Learning path" name="category-level" :value="$selectedUnit?->level->title" readonly disabled data-curriculum-level/>
                    <div class="field">
                        <label for="unit_id">Unit</label>
                        <select id="unit_id" name="unit_id" required data-curriculum-unit @error('unit_id') aria-invalid="true" aria-describedby="unit-error" @enderror>
                            <option value="">Select a unit</option>
                            @foreach($parents->groupBy('level_id') as $units)
                                <optgroup label="{{ $units->first()->level->title }}">
                                    @foreach($units as $option)
                                        <option value="{{ $option->id }}" @selected($option->is($selectedUnit))>{{ $option->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('unit_id')<small class="field-error" id="unit-error">{{ $message }}</small>@enderror
                    </div>
                @endif
                @if($parents?->isEmpty())
                    <p>{{ $kind === 'unit' ? 'Create a learning path first.' : 'Create a unit first.' }}</p>
                @endif
            </x-prototype.card>
            <div class="actions">
                <button class="button button-primary" data-pending="Saving…" @disabled($parents?->isEmpty())>Save {{ $label }}</button>
                <x-prototype.link :href="route($prefix.'.index', $context)">Cancel</x-prototype.link>
            </div>
        </form>
        <aside class="stack">
            <x-prototype.card>
                <h3>Record settings</h3>
                @if($canPublish)
                    <form method="post" action="{{ route($prefix.'.status', $statusParameters) }}" class="stack" data-live-form>
                        @csrf
                        @method('PATCH')
                        <div class="field">
                            <label for="status">State</label>
                            <select id="status" name="status">
                                @foreach([App\Support\CurriculumStatus::Draft, App\Support\CurriculumStatus::Published] as $state)
                                    <option value="{{ $state->value }}" @selected($record->status === $state)>{{ ucfirst($state->value) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="actions"><button class="button button-secondary" data-pending="Saving…">Save state</button></div>
                    </form>
                @else
                    <div class="field">
                        <label for="status">State</label>
                        <select id="status" disabled><option>{{ ucfirst($record->status->value) }}</option></select>
                    </div>
                @endif
                @if($record->exists)
                    <div class="actions">@include('curriculum.lifecycle', ['showPublication' => false])</div>
                @else
                    <p>Save this {{ $label }} to manage its state.</p>
                @endif
            </x-prototype.card>
            <x-prototype.card>
                <h3>Connected content</h3>
                @include('access.editor-link', ['accessRecord' => $record])
                @if($record->exists && $kind === 'level')
                    <x-prototype.link :href="route('admin.curriculum.units.index', ['level_id' => $record->id])">Units</x-prototype.link>
                @elseif($record->exists && $kind === 'unit')
                    <x-prototype.link :href="route('admin.curriculum.categories.index', ['unit_id' => $record->id])">Categories</x-prototype.link>
                @elseif($record->exists && $kind === 'category')
                    <x-prototype.link :href="route('admin.curriculum.lessons.index', ['category_id' => $record->id])">Lessons</x-prototype.link>
                @else
                    <p>Save this {{ $label }} to manage its {{ match ($kind) {'level' => 'units', 'unit' => 'categories', default => 'lessons'} }}.</p>
                @endif
            </x-prototype.card>
        </aside>
    </div>
@endsection
