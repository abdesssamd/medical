@props([
    'name',
    'label' => null,
    'icon' => null,
    'value' => null,
    'modern' => false,
])

@php
    $wrapperClass = $modern ? 'form-group' : 'mb-3';
    $selectClass = $modern ? 'form-control-modern' : 'form-select';
@endphp

<div class="{{ $wrapperClass }}">
    @if($label)
        <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    @endif
    @if($icon)
        <div class="input-group input-group-flat">
            <span class="input-group-text">
                <i class="ti ti-{{ $icon }}"></i>
            </span>
            <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->class([$selectClass]) }}>
                {{ $slot }}
            </select>
        </div>
    @else
        <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->class([$selectClass]) }}>
            {{ $slot }}
        </select>
    @endif
</div>
