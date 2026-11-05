@props(['to', 'variant' => 'secondary'])
<a href="{{ \App\Support\Prototype::url($to) }}" {{ $attributes->class(['button', 'button-'.$variant]) }}>{{ $slot }}</a>
