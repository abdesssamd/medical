@props([
    'name',
    'label' => null,
    'icon' => null,
    'value' => null,
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
        <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->class(['form-select']) }}>
            {{ $slot }}
        </select>
    </div>
</div>