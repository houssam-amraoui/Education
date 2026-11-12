@if($history->isEmpty())
    <div class="empty-state">
        <x-prototype.icon name="chart"/>
        <p>{{ $history->total() ? 'No activity on this page.' : ($empty ?? 'No learning activity yet.') }}</p>
        @if($history->total())
            <a class="button button-secondary" href="{{ $history->url(1) }}">Back to first page</a>
        @endif
    </div>
@else
    <x-prototype.paginated-table id="learning-activity" caption="Learning activity" :columns="['Learning activity', 'Date', 'Score', 'Status', 'Review']" :paginator="$history">
        @foreach($history as $event)
            <tr>
                <td><strong dir="auto">{{ $event['title'] }}</strong><small dir="auto">{{ $event['context'] }}</small></td>
                <td><time datetime="{{ $event['date']->toIso8601String() }}">{{ $event['date']->translatedFormat('j M Y') }}</time></td>
                <td class="numeric" dir="ltr">{{ $event['score'] }}</td>
                <td><x-prototype.badge :tone="$event['tone']">{{ $event['status'] }}</x-prototype.badge></td>
                <td>
                    @if($event['url'])
                        <a class="table-action" href="{{ $event['url'] }}">Review <span class="sr-only">{{ $event['title'] }}</span> →</a>
                    @else
                        <span class="muted">—</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-prototype.paginated-table>
@endif
