@props([
    'variant' => 'primary',
    'type' => 'button',
    'size' => null,
    'modern' => false,
])

@if($modern)
    @php
        $base = 'btn-modern';
        $variantClass = match($variant) {
            'primary' => 'btn-primary-modern',
            'outline' => 'btn-outline-modern',
            'success' => 'btn-success-modern',
            'danger' => 'btn-danger-modern',
            default => 'btn-outline-modern',
        };
        $sizeClass = $size === 'sm' ? 'btn-sm-modern' : '';
    @endphp
    <button type="{{ $type }}" {{ $attributes->class([$base, $variantClass, $sizeClass]) }}>
        {{ $slot }}
    </button>
@else
    @php
        $base = 'btn';
        $variantClass = match($variant) {
            'outline' => 'btn-outline-primary',
            'ghost' => 'btn-ghost-primary',
            'success' => 'btn-success',
            'danger' => 'btn-danger',
            default => 'btn-primary',
        };
        $sizeClass = $size ? "btn-$size" : '';
    @endphp
    <button type="{{ $type }}" {{ $attributes->class([$base, $variantClass, $sizeClass]) }}>
        {{ $slot }}
    </button>
@endif
