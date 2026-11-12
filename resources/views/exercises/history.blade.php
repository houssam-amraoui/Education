@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <div class="actions history-actions">
        <x-prototype.link :href="$url('G12')" variant="primary">Start another practice</x-prototype.link>
    </div>
    @if($records->total() === 0)
        <div class="empty-state">
            <x-prototype.icon name="chart"/>
            <h2>Your next step starts here.</h2>
            <p>Completed attempts and scores will appear here.</p>
            <x-prototype.link :href="$url('G11')" variant="primary">Explore exercises</x-prototype.link>
        </div>
    @elseif($records->isEmpty())
        <div class="empty-state">
            <h2>No attempts on this page</h2>
            <x-prototype.link :href="route('curriculum.exercises.attempts.index', $exercise)">Back to attempt history</x-prototype.link>
        </div>
    @else
        <x-prototype.paginated-table id="exercise-history" :caption="$screen['title']" :columns="['Attempt', 'Date', 'Score', 'Status', 'Review']" :paginator="$records">
            @foreach($records as $record)
                <tr>
                    <td><strong>Attempt {{ $records->total() - $records->firstItem() - $loop->index + 1 }}</strong><small dir="auto">{{ $exercise->title }}</small></td>
                    <td><time datetime="{{ $record['submitted_at']->toIso8601String() }}">{{ $record['submitted_at']->translatedFormat('j M Y') }}</time></td>
                    <td class="numeric">{{ $record['awarded_points'] }} / {{ $record['possible_points'] }}</td>
                    <td><x-prototype.badge tone="success">Completed</x-prototype.badge></td>
                    <td><a class="table-action" href="{{ route('curriculum.exercises.attempts.show', [$exercise, $record['id']]) }}" aria-label="Review attempt {{ $records->total() - $records->firstItem() - $loop->index + 1 }}">Review →</a></td>
                </tr>
            @endforeach
        </x-prototype.paginated-table>
    @endif
@endsection
