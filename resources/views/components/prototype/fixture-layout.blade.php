@props(['title'])
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · English Atlas</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="mx-auto grid max-w-7xl gap-(--space-8) p-(--space-4) medium:p-(--space-6) large:p-(--space-8)">
        <header class="stack">
            <h1>{{ $title }}</h1>
            <p>Visual prototype · Sample data only</p>
        </header>
        {{ $slot }}
    </main>
</body>
</html>
