@props([
    'name',
    'label' => null,
    'type' => 'text',
    'icon' => null,
    'value' => null,
    'placeholder' => null,
])

<div class="mb-3">
    @if($label)
        <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    @endif
    <div class="input-group input-group-flat">
        @if($icon)
            <span class="input-group-text">
                <i class="ti ti-{{ $icon }}"></i>
            </span>
        @endif
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->class(['form-control']) }}
        >
    </div>
</div>
