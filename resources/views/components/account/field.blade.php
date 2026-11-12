@props(['name', 'label', 'type' => 'text', 'value' => '', 'required' => false, 'help' => null])
@php($input = $type === 'password' ? '' : old($name, $value))
<x-prototype.field :$name :$label :$type :value="is_scalar($input) ? $input : ''" :$required :$help :error="$errors->first($name)" {{ $attributes }}/>
