@props(['label', 'name', 'type' => 'text', 'value' => '', 'required' => false, 'help' => null])
<div class="field">
    <label for="{{ $name }}">{{ $label }}{{ $required ? ' (required)' : '' }}</label>
    @if($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" @required($required) {{ $attributes }} @if($help) aria-describedby="{{ $name }}-help" @endif>{{ $value }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" @required($required) {{ $attributes }} @if($help) aria-describedby="{{ $name }}-help" @endif>
    @endif
    @if($help)<small id="{{ $name }}-help">{{ $help }}</small>@endif
</div>
