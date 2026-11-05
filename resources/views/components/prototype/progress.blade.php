@props(['value', 'label'])
<div class="progress-block"><div class="row between"><span>{{ $label }}</span><strong>{{ $value }}%</strong></div><progress value="{{ $value }}" max="100" aria-label="{{ $label }}">{{ $value }}%</progress></div>
