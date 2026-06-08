<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'variant' => 'primary', // primary | outline | ghost
    'type' => 'button',
    'size' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'variant' => 'primary', // primary | outline | ghost
    'type' => 'button',
    'size' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $base = 'btn';
    $variantClass = match($variant) {
        'outline' => 'btn-outline-primary',
        'ghost' => 'btn-ghost-primary',
        default => 'btn-primary',
    };
    $sizeClass = $size ? "btn-$size" : '';
?>

<button type="<?php echo e($type); ?>" <?php echo e($attributes->class([$base, $variantClass, $sizeClass])); ?>>
    <?php echo e($slot); ?>

</button>
<?php /**PATH D:\xampp8.2\htdocs\fils_attente\resources\views/components/tabler-button.blade.php ENDPATH**/ ?>