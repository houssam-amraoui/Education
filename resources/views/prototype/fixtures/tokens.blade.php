<x-prototype.fixture-layout title="Design tokens">
    <section class="stack" aria-labelledby="colors">
        <h2 id="colors">Colors</h2>
        <ul class="grid gap-(--space-3) medium:grid-cols-2 large:grid-cols-3">
            @foreach (['canvas', 'surface', 'surface-muted', 'text', 'text-muted', 'border', 'border-control',
                'primary', 'primary-hover', 'primary-active', 'primary-soft', 'on-primary', 'success', 'success-soft',
                'warning', 'warning-soft', 'danger', 'danger-soft', 'danger-hover', 'danger-active', 'info', 'info-soft'] as $token)
                <li class="row">
                    <span class="size-8 shrink-0 rounded-(--radius-control) border border-(--border)"
                        style="background-color: var(--{{ $token }})" aria-hidden="true"></span>
                    <code>{{ $token }}</code>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="stack" aria-labelledby="typography">
        <h2 id="typography">Typography</h2>
        <p class="text-(length:--type-reading) leading-(--leading-reading)">Reading text uses a larger size and more line spacing.</p>
        <p>Body text uses Instrument Sans.</p>
        <p lang="ar" dir="rtl" class="font-arabic leading-(--leading-arabic)">مثال على النص العربي بخط نوتو سانس العربي.</p>
        <p class="muted">Supporting text</p>
    </section>

    <section class="stack" aria-labelledby="spacing">
        <h2 id="spacing">Spacing</h2>
        <ul class="stack">
            @foreach ([1, 2, 3, 4, 6, 8, 12, 16] as $step)
                <li class="row">
                    <code>space-{{ $step }}</code>
                    <span class="h-2 bg-(--primary)" style="inline-size: var(--space-{{ $step }})" aria-hidden="true"></span>
                </li>
            @endforeach
        </ul>
    </section>
</x-prototype.fixture-layout>
