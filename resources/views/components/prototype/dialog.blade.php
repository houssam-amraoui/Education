@props(['id', 'title', 'variant' => 'modal'])

<dialog id="{{ $id }}" aria-labelledby="{{ $id }}-title"
    @class(['prototype-modal' => $variant === 'modal', 'prototype-drawer' => $variant === 'drawer'])>
    <div class="stack">
        <h2 id="{{ $id }}-title">{{ $title }}</h2>
        {{ $slot }}
        <form method="dialog" class="actions">
            <button type="submit" class="button button-secondary" autofocus>{{ __('shared.controls.close') }}</button>
        </form>
    </div>
</dialog>
