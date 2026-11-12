@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <div class="actions history-actions">
        <x-prototype.link :href="$url('G15')" variant="primary">Back to exam instructions</x-prototype.link>
    </div>
    @if($records->total() === 0)
        <div class="empty-state">
            <x-prototype.icon name="chart"/>
            <h2>Your next step starts here.</h2>
            <p>Finalized attempts for this exam will appear here.</p>
            <x-prototype.link :href="$url('G13')" variant="primary">Explore exams</x-prototype.link>
        </div>
    @elseif($records->isEmpty())
        <div class="empty-state">
            <h2>No attempts on this page</h2>
            <x-prototype.link :href="route('curriculum.exams.attempts.index', $exam)">Back to attempt history</x-prototype.link>
        </div>
    @else
        <x-prototype.paginated-table id="exam-history" :caption="$screen['title']" :columns="['Attempt', 'Date', 'Score', 'Status', 'Review']" :paginator="$records">
            @foreach($records as $record)
                @php($date = $record['submitted_at'] ?? $record['expired_at'])
                <tr>
                    <td><strong>Attempt {{ $records->total() - $records->firstItem() - $loop->index + 1 }}</strong><small dir="auto">{{ $exam->title }}</small></td>
                    <td><time datetime="{{ $date->toIso8601String() }}">{{ $date->translatedFormat('j M Y') }}</time></td>
                    <td class="numeric">{{ $record['status'] === 'completed' ? $record['awarded_points'].' / '.$record['possible_points'] : '—' }}</td>
                    <td><x-prototype.badge :tone="$record['status'] === 'completed' ? 'success' : 'warning'">{{ ucfirst($record['status']) }}</x-prototype.badge></td>
                    <td><a class="table-action" href="{{ route('curriculum.exams.attempts.show', [$exam, $record['id']]) }}" aria-label="Review attempt {{ $records->total() - $records->firstItem() - $loop->index + 1 }}">Review →</a></td>
                </tr>
            @endforeach
        </x-prototype.paginated-table>
    @endif
@endsection
