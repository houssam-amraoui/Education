@extends('prototype.layout')

@section('content')
    @include('curriculum.heading', ['description' => 'Choose an assessment to manage its questions.'])
    <x-prototype.paginated-table id="question-owners" caption="Assessments" :columns="['Name', 'Context', 'Status', 'Actions']" :paginator="$owners">
        @foreach ($owners as $owner)
            <tr>
                <td><strong dir="auto">{{ $owner->title }}</strong></td>
                <td dir="auto">{{ $owner->unit->level->title }} · {{ $owner->unit->title }}</td>
                <td><x-prototype.badge :tone="$owner->status === App\Support\CurriculumStatus::Published ? 'success' : 'neutral'">{{ ucfirst($owner->status->value) }}</x-prototype.badge></td>
                <td><a class="table-action" href="{{ route('admin.curriculum.'.$resource.'.questions.index', $owner) }}">Manage questions <span class="sr-only">{{ $owner->title }}</span></a></td>
            </tr>
        @endforeach
    </x-prototype.paginated-table>
@endsection
