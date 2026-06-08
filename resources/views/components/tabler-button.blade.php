@props([
    'variant' => 'primary', // primary | outline | ghost
    'type' => 'button',
    'size' => null,
])

@php
    $base = 'btn';
    $variantClass = match($variant) {
        'outline' => 'btn-outline-primary',
        'ghost' => 'btn-ghost-primary',
        default => 'btn-primary',
    };
    $sizeClass = $size ? "btn-$size" : '';
@endphp

<button type="{{ $type }}" {{ $attributes->class([$base, $variantClass, $sizeClass]) }}>
    {{ $slot }}
</button>
