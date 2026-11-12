@extends('prototype.layout')

@section('content')
    @php($prefix = 'admin.library.topics')
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="row between wrap">
        <p>Manage the concepts in the Topic Index.</p>
        <div class="actions"><x-prototype.link :href="route($prefix.'.create')" variant="primary">Create topic</x-prototype.link></div>
    </div>
    <form class="filter-bar" method="get" action="{{ route($prefix.'.index') }}" data-live-form>
        <div class="field grow">
            <label for="record-search">Find a record</label>
            <input id="record-search" name="search" type="search" placeholder="Search topics" value="{{ $filters['search'] ?? '' }}" maxlength="255" dir="auto">
        </div>
        <div class="field">
            <label for="record-status">Status</label>
            <select id="record-status" name="status">
                <option value="">All statuses</option>
                @foreach(\App\Support\CurriculumStatus::cases() as $state)
                    <option value="{{ $state->value }}" @selected(($filters['status'] ?? null) === $state->value)>{{ ucfirst($state->value) }}</option>
                @endforeach
            </select>
        </div>
        <button class="button button-secondary" data-pending="Searching…">Search</button>
    </form>
    <x-prototype.paginated-table id="topic-list" :caption="$screen['title']" :columns="['Name', 'Context', 'Status', 'Actions']" :paginator="$records">
        @foreach($records as $record)
            @php($count = collect(array_keys(\App\Models\Topic::CONTENT))->sum(fn ($type) => $record->{$type.'s_count'}))
            <tr>
                <td><strong dir="auto">{{ $record->title }}</strong></td>
                <td>{{ trans_choice('{1} :count related item|[0,*] :count related items', $count) }}</td>
                <td><x-prototype.badge :tone="$record->status->value === 'published' ? 'success' : 'neutral'">{{ ucfirst($record->status->value) }}</x-prototype.badge></td>
                <td><div class="actions">
                    <a class="table-action" href="{{ route($prefix.'.edit', $record) }}">Edit <span class="sr-only">{{ $record->title }}</span></a>
                    @include('curriculum.lifecycle', ['kind' => 'topic'])
                </div></td>
            </tr>
        @endforeach
    </x-prototype.paginated-table>
    @if($records->isEmpty())
        <div class="empty-state">
            @if(array_filter($filters) || $records->currentPage() > 1)
                <h2>No matching records</h2><p>Try another name or status.</p>
                <x-prototype.link :href="route($prefix.'.index')">Reset filters</x-prototype.link>
            @else
                <h2>No topics yet</h2>
                <x-prototype.link :href="route($prefix.'.create')" variant="primary">Create topic</x-prototype.link>
            @endif
        </div>
    @endif
@endsection
