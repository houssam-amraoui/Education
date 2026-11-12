@props(['to' => null, 'href' => null, 'variant' => 'secondary'])
<a href="{{ $href ?? \App\Support\Prototype::url($to) }}" {{ $attributes->class(['button', 'button-'.$variant]) }}>{{ $slot }}</a>
