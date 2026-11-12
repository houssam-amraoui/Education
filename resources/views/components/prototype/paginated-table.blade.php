@props(['id', 'caption', 'columns', 'paginator'])

<div class="stack">
    <div class="table-scroll" role="region" aria-labelledby="{{ $id }}-caption" tabindex="0">
        <table>
            <caption id="{{ $id }}-caption" class="sr-only">{{ $caption }}</caption>
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th scope="col">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @if ($paginator->isEmpty())
                    <tr><td colspan="{{ count($columns) }}">{{ __('shared.feedback.no_results') }}</td></tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>
    @if ($paginator->isNotEmpty())
        <div class="row between wrap">
            <p>Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
            @if ($paginator->hasPages())
                <nav class="row wrap" aria-label="{{ $caption }} pagination">
                    @if ($paginator->onFirstPage())
                        <button type="button" class="button button-secondary" disabled>{{ __('shared.controls.previous') }}</button>
                    @else
                        <a class="button button-secondary" href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('shared.controls.previous') }}</a>
                    @endif
                    <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
                    @if ($paginator->hasMorePages())
                        <a class="button button-secondary" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('shared.controls.next') }}</a>
                    @else
                        <button type="button" class="button button-secondary" disabled>{{ __('shared.controls.next') }}</button>
                    @endif
                </nav>
            @endif
        </div>
    @endif
</div>
