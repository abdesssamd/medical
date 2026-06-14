@props([
    'name',
    'label' => null,
    'type' => 'text',
    'icon' => null,
    'value' => null,
    'placeholder' => null,
    'modern' => false,
])

@php
    $wrapperClass = $modern ? 'form-group' : 'mb-3';
    $inputClass = $modern ? 'form-control-modern' : 'form-control';
@endphp

<div class="{{ $wrapperClass }}">
    @if($label)
        <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    @endif
    @if($icon && $modern)
        <div class="input-with-icon">
            <i class="ti ti-{{ $icon }}"></i>
            <input
                id="{{ $name }}"
                name="{{ $name }}"
                type="{{ $type }}"
                value="{{ old($name, $value) }}"
                placeholder="{{ $placeholder }}"
                {{ $attributes->class([$inputClass]) }}
            >
        </div>
    @elseif($icon)
        <div class="input-group input-group-flat">
            <span class="input-group-text">
                <i class="ti ti-{{ $icon }}"></i>
            </span>
            <input
                id="{{ $name }}"
                name="{{ $name }}"
                type="{{ $type }}"
                value="{{ old($name, $value) }}"
                placeholder="{{ $placeholder }}"
                {{ $attributes->class([$inputClass]) }}
            >
        </div>
    @else
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->class([$inputClass]) }}
        >
    @endif
</div>
