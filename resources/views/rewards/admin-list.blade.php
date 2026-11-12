@extends('prototype.layout')

@section('content')
    @php
        $points = $screen['id'] === 'A33';
        $listUrl = $url($screen['id']);
    @endphp
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="row between wrap">
        <p>{{ $points ? 'Manage how students earn and spend points.' : 'Manage achievement criteria and rewards.' }}</p>
        <div class="actions">
            @if($points)
                <x-prototype.link :href="$url('A35')">Manage achievements</x-prototype.link>
                <x-prototype.link :href="$url('A37')">Streak settings</x-prototype.link>
            @endif
            <x-prototype.link :href="$url($points ? 'A34' : 'A36')" variant="primary">{{ $points ? 'Create points rule' : 'Create achievement' }}</x-prototype.link>
        </div>
    </div>
    <form class="filter-bar" method="get" action="{{ $listUrl }}" data-live-form>
        <label class="field grow" for="record-search">Find a record
            <input id="record-search" name="search" type="search" placeholder="Search records" value="{{ $search }}" maxlength="255">
        </label>
        <label class="field" for="record-status">Status
            <select id="record-status" name="status">
                <option value="">All statuses</option>
                <option value="enabled" @selected($status === 'enabled')>Enabled</option>
                <option value="disabled" @selected($status === 'disabled')>Disabled</option>
            </select>
        </label>
        <button class="button button-secondary" data-pending="Searching…">Search</button>
    </form>
    <x-prototype.paginated-table id="reward-records" :caption="$screen['title']" :columns="['Name', 'Context', 'Status', 'Actions']" :paginator="$records">
        @foreach($records as $row)
            @php
                $event = App\Support\RewardEvent::tryFrom($row->event);
                [$context, $edit] = match (true) {
                    !$points => [$row->required_count.' '.$event->criterion(), route('admin.rewards.achievements.edit', $row->id)],
                    $row->kind === 'cost' => ['Spend '.$row->points.' points', route('admin.rewards.costs.edit', $row->id)],
                    default => ['Earn '.$row->points.' points · '.$event->label(),
                        route($event === App\Support\RewardEvent::StreakMilestone ? 'admin.rewards.streak.edit' : 'admin.rewards.rules.edit', $row->id)],
                };
            @endphp
            <tr>
                <td><strong dir="auto">{{ $row->name }}</strong></td>
                <td>{{ $context }}</td>
                <td><x-prototype.badge :tone="$row->enabled ? 'success' : 'neutral'">{{ $row->enabled ? 'Enabled' : 'Disabled' }}</x-prototype.badge></td>
                <td><div class="actions"><a class="table-action" href="{{ $edit }}">Edit <span class="sr-only">{{ $row->name }}</span></a></div></td>
            </tr>
        @endforeach
    </x-prototype.paginated-table>
    @if($records->isEmpty())
        <div class="empty-state">
            @if($search !== null || $status !== null || !$records->onFirstPage())
                <h2>No matching records</h2><x-prototype.link :href="$listUrl">Reset filters</x-prototype.link>
            @else
                <h2>{{ $points ? 'No points rules yet' : 'No achievements yet' }}</h2>
            @endif
        </div>
    @endif
@endsection
