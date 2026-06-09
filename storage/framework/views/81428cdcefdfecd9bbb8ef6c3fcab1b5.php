<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name',
    'label' => null,
    'type' => 'text',
    'icon' => null,
    'value' => null,
    'placeholder' => null,
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
    'name',
    'label' => null,
    'type' => 'text',
    'icon' => null,
    'value' => null,
    'placeholder' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="mb-3">
    <?php if($label): ?>
        <label class="form-label" for="<?php echo e($name); ?>"><?php echo e($label); ?></label>
    <?php endif; ?>
    <div class="input-group input-group-flat">
        <?php if($icon): ?>
            <span class="input-group-text">
                <i class="ti ti-<?php echo e($icon); ?>"></i>
            </span>
        <?php endif; ?>
        <input
            id="<?php echo e($name); ?>"
            name="<?php echo e($name); ?>"
            type="<?php echo e($type); ?>"
            value="<?php echo e(old($name, $value)); ?>"
            placeholder="<?php echo e($placeholder); ?>"
            <?php echo e($attributes->class(['form-control'])); ?>

        >
    </div>
</div>
<?php /**PATH E:\xamp8.1\htdocs\medical\resources\views/components/tabler-input.blade.php ENDPATH**/ ?>