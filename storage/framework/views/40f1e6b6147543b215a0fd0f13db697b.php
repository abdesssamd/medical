<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123d36">
    <link rel="manifest" href="/manifest.webmanifest">
    <title><?php echo e(__('queue.login')); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/scss/app.scss', 'resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="d-flex flex-column bg-body-tertiary">
<div class="page page-center">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <a href="<?php echo e(route('home')); ?>" class="navbar-brand navbar-brand-autodark fs-2 fw-bold text-decoration-none">
                <?php echo e(__('queue.app_name')); ?>

            </a>
        </div>

        <div class="card card-md">
            <div class="card-body">
                <h2 class="h2 text-center mb-3"><?php echo e(__('queue.login')); ?></h2>
                <p class="text-secondary text-center"><?php echo e(__('queue.login_hint')); ?></p>

                <form method="POST" action="<?php echo e(route('login.attempt')); ?>" autocomplete="off" novalidate>
                    <?php echo csrf_field(); ?>
                    <?php if (isset($component)) { $__componentOriginalb2e58112aec649fe6720fd8615da95b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb2e58112aec649fe6720fd8615da95b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-input','data' => ['name' => 'email','type' => 'email','label' => 'Email','icon' => 'mail','value' => old('email'),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'email','type' => 'email','label' => 'Email','icon' => 'mail','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('email')),'required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $attributes = $__attributesOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $component = $__componentOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__componentOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginalb2e58112aec649fe6720fd8615da95b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb2e58112aec649fe6720fd8615da95b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-input','data' => ['name' => 'password','type' => 'password','label' => __('queue.password'),'icon' => 'lock','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'password','type' => 'password','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('queue.password')),'icon' => 'lock','required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $attributes = $__attributesOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $component = $__componentOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__componentOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember">
                        <span class="form-check-label"><?php echo e(__('queue.remember_me')); ?></span>
                    </label>
                    <div class="form-footer mt-3">
                        <?php if (isset($component)) { $__componentOriginal63df0cb894d3cbc65acc12f6ba52c916 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal63df0cb894d3cbc65acc12f6ba52c916 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-button','data' => ['type' => 'submit','class' => 'w-100']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','class' => 'w-100']); ?><?php echo e(__('queue.login')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal63df0cb894d3cbc65acc12f6ba52c916)): ?>
<?php $attributes = $__attributesOriginal63df0cb894d3cbc65acc12f6ba52c916; ?>
<?php unset($__attributesOriginal63df0cb894d3cbc65acc12f6ba52c916); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal63df0cb894d3cbc65acc12f6ba52c916)): ?>
<?php $component = $__componentOriginal63df0cb894d3cbc65acc12f6ba52c916; ?>
<?php unset($__componentOriginal63df0cb894d3cbc65acc12f6ba52c916); ?>
<?php endif; ?>
                    </div>
                </form>

                <?php if(isset($errors) && $errors->any()): ?>
                    <div class="alert alert-danger mt-3 mb-0"><?php echo e($errors->first()); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center text-secondary mt-3 small">
            Admin demo: <strong>admin@queue.local / password</strong><br>
            Agent demo: <strong>agent1@CITY-001.local / password</strong><br>
            Pro demo: <strong>pro@rdv.local / password</strong><br>
            Secretaire demo: <strong>secretary@rdv.local / password</strong>
        </div>
    </div>
</div>
</body>
</html>
<?php /**PATH D:\xampp8.2\htdocs\fils_attente\Modules\Queue\Resources\views/auth/login.blade.php ENDPATH**/ ?>