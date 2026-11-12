@php
    $archived = $record->status === App\Support\CurriculumStatus::Archived;
    $published = $record->status === App\Support\CurriculumStatus::Published;
    $parameters = match ($kind) {
        'level', 'resource' => [$record],
        'unit' => [$record->level_id, $record],
        'lesson' => [$record->category_id, $record],
        default => [$record->unit_id, $record],
    };
    $archiveDialog = 'archive-'.$kind.'-'.$record->id;
@endphp
@if(!$archived)
    @if($kind !== 'category' && ($showPublication ?? true))
        <form method="post" action="{{ route($prefix.'.status', $parameters) }}" data-live-form>
            @csrf
            @method('PATCH')
            @if(isset($returnTo))<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
            <input type="hidden" name="status" value="{{ $published ? 'draft' : 'published' }}">
            <button class="button button-tertiary" data-pending="Saving…" aria-label="{{ ($published ? 'Unpublish ' : 'Publish ').$record->title }}">
                {{ $published ? 'Unpublish' : 'Publish' }}
            </button>
        </form>
    @endif
    <button type="button" class="button button-tertiary" data-open-dialog="{{ $archiveDialog }}">
        Archive <span class="sr-only">{{ $record->title }}</span>
    </button>
    <x-prototype.dialog :id="$archiveDialog" title="Archive record">
        <p>Archive <bdi>{{ $record->title }}</bdi>? It will no longer appear in the public catalogue.</p>
        <form method="post" action="{{ route($prefix.($kind === 'category' ? '.archive' : '.status'), $parameters) }}" data-live-form>
            @csrf
            @method('PATCH')
            @if(isset($returnTo))<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
            @if($kind !== 'category')<input type="hidden" name="status" value="archived">@endif
            <button class="button button-primary" data-pending="Archiving…">Archive</button>
        </form>
    </x-prototype.dialog>
@endif
@if(($position ?? 0) > 0)
    <form method="post" action="{{ route($prefix.'.reorder', $parameters) }}" data-live-form>
        @csrf
        @method('PATCH')
        <input type="hidden" name="position" value="{{ $position }}">
        <button class="button button-tertiary" aria-label="Move {{ $record->title }} up" data-pending="Moving…">Move up</button>
    </form>
@endif
