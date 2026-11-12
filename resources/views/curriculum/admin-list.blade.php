@extends('prototype.layout')

@section('content')
    @php
        $plural = $kind === 'category' ? 'categories' : $kind.'s';
        $prefix = 'admin.curriculum.'.$plural;
        $label = $kind === 'level' ? 'learning path' : $kind;
        $canCreate = $kind === 'level' || $parent;
        $listUrl = route($prefix.'.index', $context);
        $createUrl = route($prefix.'.create', $context);
    @endphp
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="row between wrap">
        <p>Manage {{ strtolower($screen['title']) }}.</p>
        @if($canCreate)<div class="actions"><x-prototype.link :href="$createUrl" variant="primary">Create {{ $label }}</x-prototype.link></div>@endif
    </div>
    <form class="filter-bar" method="get" action="{{ route($prefix.'.index') }}" data-live-form>
        @if($parents?->isNotEmpty())
            <div class="field">
                <label for="parent-filter">{{ $kind === 'unit' ? 'Learning path' : 'Unit' }}</label>
                <select id="parent-filter" name="{{ $kind === 'unit' ? 'level_id' : 'unit_id' }}">
                    @foreach($parents as $option)
                        <option value="{{ $option->id }}" @selected($option->is($parent))>{{ $kind === 'category' ? $option->level->title.' · ' : '' }}{{ $option->title }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="field grow">
            <label for="record-search">Find a record</label>
            <input id="record-search" name="search" type="search" placeholder="Search {{ $plural }}" value="{{ $search }}" maxlength="255">
        </div>
        <div class="field">
            <label for="record-status">Status</label>
            <select id="record-status" name="status">
                <option value="">All statuses</option>
                @foreach(App\Support\CurriculumStatus::cases() as $state)
                    @if($kind !== 'category' || $state !== App\Support\CurriculumStatus::Draft)
                        <option value="{{ $state->value }}" @selected($status === $state->value)>{{ ucfirst($state->value) }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <button class="button button-secondary" data-pending="Searching…">Search</button>
    </form>

    <x-prototype.paginated-table :id="$kind.'-list'" :caption="$screen['title']" :columns="['Name', 'Context', 'Status', 'Actions']" :paginator="$records">
        @foreach($records as $record)
            <tr>
                <td><strong dir="auto">{{ $record->title }}</strong></td>
                <td><bdi>{{ match ($kind) {
                    'level' => trans_choice('{1} :count unit|[0,*] :count units', $record->units_count),
                    'unit' => $parent->title.' · '.trans_choice('{1} :count category|[0,*] :count categories', $record->categories_count),
                    default => $parent->title,
                } }}</bdi></td>
                <td><x-prototype.badge :tone="$record->status === App\Support\CurriculumStatus::Published ? 'success' : 'neutral'">{{ ucfirst($record->status->value) }}</x-prototype.badge></td>
                <td>
                    <div class="actions">
                        <a class="table-action" href="{{ route($prefix.'.edit', $record) }}">Edit <span class="sr-only">{{ $record->title }}</span></a>
                        @include('curriculum.lifecycle', ['position' => $positions[$record->id] ?? null])
                    </div>
                </td>
            </tr>
        @endforeach
    </x-prototype.paginated-table>

    @if($records->isEmpty())
        <div class="empty-state">
            @if($search !== null || $status !== null || $records->currentPage() > 1)
                <h2>No matching records</h2><p>Try another name or status.</p>
                <x-prototype.link :href="$listUrl">Reset filters</x-prototype.link>
            @else
                <h2>No {{ $kind === 'level' ? 'learning paths' : $plural }} yet</h2>
                @if($canCreate)
                    <x-prototype.link :href="$createUrl" variant="primary">Create {{ $label }}</x-prototype.link>
                @else
                    <p>{{ $kind === 'unit' ? 'Create a learning path before adding units.' : 'Create a unit before adding categories.' }}</p>
                    <x-prototype.link :href="$url($kind === 'unit' ? 'A07' : 'A09')">Manage {{ $kind === 'unit' ? 'learning paths' : 'units' }}</x-prototype.link>
                @endif
            @endif
        </div>
    @endif
@endsection
