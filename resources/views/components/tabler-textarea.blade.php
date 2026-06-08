@props([
    'name',
    'label' => null,
    'rows' => 4,
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
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->class(['form-control']) }}
        >{{ old($name, $value) }}</textarea>
    </div>
</div>