@props([
    'name',
    'label' => null,
    'rows' => 4,
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
    @if($icon)
        <div class="input-group input-group-flat">
            <span class="input-group-text">
                <i class="ti ti-{{ $icon }}"></i>
            </span>
            <textarea
                id="{{ $name }}"
                name="{{ $name }}"
                rows="{{ $rows }}"
                placeholder="{{ $placeholder }}"
                {{ $attributes->class([$inputClass]) }}
            >{{ old($name, $value) }}</textarea>
        </div>
    @else
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->class([$inputClass]) }}
        >{{ old($name, $value) }}</textarea>
    @endif
</div>
