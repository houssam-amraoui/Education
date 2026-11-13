@props(['label', 'name', 'type' => 'text', 'value' => '', 'required' => false, 'help' => null, 'error' => null, 'fieldName' => null])
@php($description = implode(' ', array_filter([$help ? $name.'-help' : null, $error ? $name.'-error' : null])))
<div class="field">
    <label for="{{ $name }}">{{ $label }}{{ $required ? ' (required)' : '' }}</label>
    @if($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $fieldName ?? $name }}" @required($required) {{ $attributes }} @if($description) aria-describedby="{{ $description }}" @endif @if($error) aria-invalid="true" @endif>{{ $value }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $fieldName ?? $name }}" type="{{ $type }}" value="{{ $value }}" @required($required) {{ $attributes }} @if($description) aria-describedby="{{ $description }}" @endif @if($error) aria-invalid="true" @endif>
    @endif
    @if($help)<small id="{{ $name }}-help">{{ $help }}</small>@endif
    @if($error)<small id="{{ $name }}-error" class="field-error">{{ $error }}</small>@endif
</div>
