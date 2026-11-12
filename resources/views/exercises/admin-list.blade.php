@extends('prototype.layout')

@section('content')
    @php($prefix = 'admin.curriculum.exercises')
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="row between wrap">
        <p>Manage practice exercises.</p>
        <div class="actions"><x-prototype.link :href="route($prefix.'.create')" variant="primary">Create exercise</x-prototype.link></div>
    </div>
    <form class="filter-bar" method="get" action="{{ route($prefix.'.index') }}" data-live-form>
        <div class="field grow">
            <label for="record-search">Find a record</label>
            <input id="record-search" name="search" type="search" placeholder="Search exercises" maxlength="255" value="{{ $filters['search'] ?? '' }}">
        </div>
        <div class="field">
            <label for="record-status">Status</label>
            <select id="record-status" name="status">
                <option value="">All statuses</option>
                @foreach(App\Support\CurriculumStatus::cases() as $state)
                    <option value="{{ $state->value }}" @selected(($filters['status'] ?? null) === $state->value)>{{ ucfirst($state->value) }}</option>
                @endforeach
            </select>
        </div>
        <button class="button button-secondary" data-pending="Searching…">Search</button>
    </form>
    @if($records->isEmpty())
        <div class="empty-state">
            @if(array_filter($filters, fn ($value) => $value !== null) || $records->currentPage() > 1)
                <h2>No matching records</h2><p>Try another name or status.</p>
                <x-prototype.link :href="route($prefix.'.index')">Reset filters</x-prototype.link>
            @else
                <h2>No exercises yet</h2>
                <x-prototype.link :href="route($prefix.'.create')" variant="primary">Create exercise</x-prototype.link>
            @endif
        </div>
    @else
        <x-prototype.paginated-table id="exercise-list" :caption="$screen['title']" :columns="['Name', 'Context', 'Status', 'Actions']" :paginator="$records">
            @foreach($records as $record)
                <tr>
                    <td><strong dir="auto">{{ $record->title }}</strong></td>
                    <td><bdi>{{ $record->unit->level->title }}</bdi> · <bdi>{{ $record->unit->title }}</bdi></td>
                    <td><x-prototype.badge :tone="$record->status->value === 'published' ? 'success' : 'neutral'">{{ ucfirst($record->status->value) }}</x-prototype.badge></td>
                    <td><div class="actions">
                        <a class="table-action" href="{{ route($prefix.'.edit', $record) }}">Edit <span class="sr-only">{{ $record->title }}</span></a>
                        @include('curriculum.lifecycle', ['kind' => 'exercise', 'position' => $positions[$record->unit_id][$record->id] ?? null])
                    </div></td>
                </tr>
            @endforeach
        </x-prototype.paginated-table>
    @endif
@endsection
