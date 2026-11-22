@props([
    'id',
    'name' => null,
    'value' => '',
    'maxlength' => 64,
])

<input {{ $attributes->merge([
    'class' => 'form-control',
    'id' => $id,
    'name' => $name,
    'type' => 'tel',
    'maxlength' => $maxlength,
    'autocomplete' => 'tel',
    'inputmode' => 'tel',
    'data-phone-input' => '',
]) }} value="{{ $value }}">
