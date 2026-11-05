@props(['name' => 'book'])
@php
    $paths = [
        'book' => 'M4 4h6a3 3 0 0 1 3 3v14a4 4 0 0 0-4-3H4V4Zm9 3a3 3 0 0 1 3-3h5v14h-4a4 4 0 0 0-4 3',
        'grid' => 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
        'search' => 'M21 21l-5-5M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0',
        'chart' => 'M4 3v18h18M8 16v-5M13 16V7M18 16V4',
        'message' => 'M21 4H3v13h4v4l5-4h9V4Z',
        'award' => 'M8 14 6 22l6-3 6 3-2-8M19 8a7 7 0 1 1-14 0 7 7 0 0 1 14 0',
        'user' => 'M20 21v-3a5 5 0 0 0-5-5H9a5 5 0 0 0-5 5v3M16 5a4 4 0 1 1-8 0 4 4 0 0 1 8 0',
        'play' => 'M9 7l8 5-8 5V7ZM22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',
        'check' => 'M5 12l4 4L20 5',
        'arrow' => 'M4 12h16M14 6l6 6-6 6',
        'menu' => 'M3 6h18M3 12h18M3 18h18',
        'close' => 'M5 5l14 14M5 19 19 5',
        'file' => 'M14 2H4v20h16V8l-6-6ZM14 2v6h6M8 13h8M8 17h8',
        'clock' => 'M12 6v6l4 2M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',
        'lock' => 'M5 10h14v12H5zM8 10V6a4 4 0 0 1 8 0v4',
    ];
@endphp
<svg {{ $attributes->class(['icon', 'directional' => $name === 'arrow']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['book'] }}"/></svg>
