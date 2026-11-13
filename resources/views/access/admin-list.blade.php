@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="row between wrap">
        <p>{{ $type ? ($targetName ?? 'Unavailable content') : 'Manage content and feature access.' }}</p>
        <div class="actions">
            @if($type)<x-prototype.link :href="route('admin.access-rules.index')">All access rules</x-prototype.link>@endif
            <x-prototype.link :href="route('admin.access-rules.create', array_filter(['type' => $type, 'target' => $id], fn ($value) => $value !== null))" variant="primary">Create access rule</x-prototype.link>
        </div>
    </div>
    <form class="filter-bar" action="{{ route('admin.access-rules.index') }}">
        @if($type)<input type="hidden" name="type" value="{{ $type }}"><input type="hidden" name="target" value="{{ $id }}">@endif
        <div class="field grow"><label for="search">Find a record</label><input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}"></div>
        <div class="field"><label for="requirement">Access classification</label>
            <select id="requirement" name="requirement">
                <option value="">All classifications</option>
                @foreach(App\Support\AccessTarget::REQUIREMENTS as $requirement)
                    <option value="{{ $requirement }}" @selected(($filters['requirement'] ?? null) === $requirement)>{{ __("library.access.$requirement") }}</option>
                @endforeach
            </select>
        </div>
        <button class="button button-secondary">Search</button>
    </form>
    @if($records->isEmpty())
        <div class="empty-state"><h2>{{ filled($filters['search'] ?? null) || filled($filters['requirement'] ?? null) ? 'No matching records' : 'No access rules yet' }}</h2></div>
    @else
        <x-prototype.paginated-table id="access-rules" caption="Access rules" :columns="['Name', 'Context', 'Status', 'Actions']" :paginator="$records">
                    @foreach($records as $rule)
                        @php($name = $rule->target_type === App\Support\AccessTarget::FEATURE ? 'Ask Teacher' : ($rule->target?->title ?? 'Unavailable content'))
                        <tr><td><strong dir="auto">{{ $name }}</strong></td>
                            <td>{{ __(str($rule->target_type)->replace('_', ' ')->headline()->toString()) }} · {{ __("library.access.$rule->requirement") }}</td>
                            <td><x-prototype.badge>{{ $rule->requirement === 'public' ? 'Free' : 'Restricted' }}</x-prototype.badge></td>
                            <td><a class="table-action" href="{{ route('admin.access-rules.edit', $rule) }}">Edit <span class="sr-only">{{ $name }} {{ __("library.access.$rule->requirement") }}</span></a></td></tr>
                    @endforeach
        </x-prototype.paginated-table>
    @endif
@endsection
