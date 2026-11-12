@php
    $users = $screen['id'] === 'A02';
    $model = $users ? App\Models\User::class : App\Models\Role::class;
    $kind = $users ? 'user' : 'role';
    $routePrefix = 'admin.accounts.'.$kind.'s';
    $filterName = $users ? 'membership' : 'type';
    $options = $users ? ['assigned' => 'Roles assigned', 'unassigned' => 'No roles assigned'] : ['built-in' => 'Built-in', 'custom' => 'Custom'];
@endphp
<div class="row between wrap">
    <p>{{ $users ? 'Manage platform accounts and their roles.' : 'Manage the roles and abilities available on the platform.' }}</p>
    @can('create', $model)
        <div class="actions"><a class="button button-primary" href="{{ route($routePrefix.'.create') }}">Create {{ $kind }}</a></div>
    @endcan
</div>
<form class="filter-bar" method="get" action="{{ route($routePrefix.'.index') }}" data-live-form>
    <div class="field grow">
        <label for="search">Find a record</label>
        <input id="search" name="search" type="search" placeholder="Search {{ $kind }}s" value="{{ $search }}" maxlength="255">
    </div>
    <div class="field">
        <label for="{{ $filterName }}">{{ $users ? 'Role membership' : 'Role type' }}</label>
        <select id="{{ $filterName }}" name="{{ $filterName }}">
            <option value="">All {{ $kind }}s</option>
            @foreach($options as $value => $label)
                <option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button class="button button-secondary" data-pending="Searching…">Search</button>
</form>
<x-prototype.paginated-table :id="$kind.'-list'" :caption="$screen['title']" :columns="['Name', 'Context', $users ? 'Roles' : 'Type', 'Actions']" :paginator="$records">
    @foreach($records as $record)
        <tr>
            <td><strong dir="auto">{{ $record->name }}</strong></td>
            <td><bdi>{{ $users ? $record->email : ($record->description ?: '—') }}</bdi></td>
            <td>
                @if($users)
                    @forelse($record->roles as $role)
                        <x-prototype.badge><bdi>{{ $role->name }}</bdi></x-prototype.badge>
                    @empty
                        <span class="muted">No roles</span>
                    @endforelse
                @else
                    <x-prototype.badge>{{ $record->key ? 'Built-in' : 'Custom' }}</x-prototype.badge>
                @endif
            </td>
            <td>
                @can('update', $record)
                    <a class="table-action" href="{{ route($routePrefix.'.edit', $record) }}">Edit <span class="sr-only">{{ $record->name }}</span></a>
                @else
                    <span class="muted">Read only</span>
                @endcan
                @if($users && $record->roles->contains('key', App\Models\Role::STUDENT))
                    @can('viewStudentProgress', $record)
                        <a class="table-action" href="{{ route('admin.accounts.users.progress', $record) }}">Activity <span class="sr-only">{{ $record->name }}</span></a>
                    @endcan
                @endif
            </td>
        </tr>
    @endforeach
</x-prototype.paginated-table>
@if($records->isEmpty())
    <div class="empty-state">
        <h2>{{ $records->total() ? 'No records on this page' : ($search !== null || $filter !== null ? 'No matching records' : 'No '.$kind.'s yet') }}</h2>
        @if($search !== null || $filter !== null || $records->currentPage() > 1)
            <a class="button button-secondary" href="{{ route($routePrefix.'.index') }}">Reset filters</a>
        @else
            @can('create', $model)<a class="button button-primary" href="{{ route($routePrefix.'.create') }}">Create {{ $kind }}</a>@endcan
        @endif
    </div>
@endif
