@props(['record', 'suffix' => null])
@php($labels = app(App\Queries\AccessDestination::class)->labels($record))
@forelse($labels as $classification)
    <x-prototype.badge>{{ __("library.access.$classification") }}@if($loop->first && $suffix) · {{ $suffix }}@endif</x-prototype.badge>
@empty
    <x-prototype.badge>{{ __('library.access.public') }}@if($suffix) · {{ $suffix }}@endif</x-prototype.badge>
@endforelse
