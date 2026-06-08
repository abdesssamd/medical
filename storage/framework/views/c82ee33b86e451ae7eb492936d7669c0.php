<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" dir="<?php echo e($isRtl ?? false ? 'rtl' : 'ltr'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="/manifest.webmanifest">
    <title><?php echo e(config('app.name', 'DentalCare Pro')); ?> - <?php echo $__env->yieldContent('title', 'Dashboard'); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/scss/app.scss', 'resources/js/app.js']); ?>
    <?php echo $__env->yieldPushContent('head'); ?>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <span class="brand-icon"><i class="ti ti-tooth"></i></span>
                <span class="brand-text">DentalCare Pro</span>
            </div>

            <?php
                $user = auth()->user();
                $isAdmin = $user?->hasAnyRole(['super_admin', 'admin']) ?? false;
                $isPractitioner = $user?->hasAnyRole(['professional', 'doctor', 'medecin']) ?? false;
                $isSecretary = $user?->hasAnyRole(['secretary', 'secretaire']) ?? false;
                $isAssistant = $user?->hasAnyRole(['assistant']) ?? false;
                $risEnabled = (bool) config('ris.enabled', false);

                if (! $risEnabled && class_exists('Modules\\Queue\\Models\\AppSetting')) {
                    $risEnabled = filter_var((string) \Modules\Queue\Models\AppSetting::getValue('module.ris.enabled', false), FILTER_VALIDATE_BOOL) === true;
                }
            ?>

            <nav class="sidebar-nav">
                <a href="<?php echo e(route('home')); ?>" class="nav-item <?php echo e(request()->routeIs('home') ? 'active' : ''); ?>">
                    <span class="nav-icon"><i class="ti ti-home"></i></span>
                    <span class="nav-label">Accueil</span>
                </a>

                <?php if(auth()->guard()->check()): ?>
                    <?php if($isAdmin): ?>
                        <div class="nav-section">Queue</div>
                        <a href="<?php echo e(route('admin.dashboard')); ?>" class="nav-item <?php echo e(request()->routeIs('admin.*') && !request()->routeIs('admin.supervisor*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-users-group"></i></span>
                            <span class="nav-label">Admin Queue</span>
                        </a>
                        <a href="<?php echo e(route('admin.settings')); ?>" class="nav-item <?php echo e(request()->routeIs('admin.settings*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-settings"></i></span>
                            <span class="nav-label">Parametres cabinet</span>
                        </a>
                        <a href="<?php echo e(route('admin.supervisor')); ?>" class="nav-item <?php echo e(request()->routeIs('admin.supervisor*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-chart-bar"></i></span>
                            <span class="nav-label">Superviseur</span>
                        </a>
                        <a href="<?php echo e(route('agent.dashboard')); ?>" class="nav-item <?php echo e(request()->routeIs('agent.*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-user"></i></span>
                            <span class="nav-label">Agent</span>
                        </a>
                        <a href="<?php echo e(route('tickets.create')); ?>" class="nav-item <?php echo e(request()->routeIs('tickets.*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-ticket"></i></span>
                            <span class="nav-label">Billetterie</span>
                        </a>

                        <div class="nav-section">Planification</div>
                        <a href="<?php echo e(route('scheduling.dashboard')); ?>" class="nav-item <?php echo e(request()->routeIs('scheduling.dashboard') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-calendar-time"></i></span>
                            <span class="nav-label">Planning</span>
                        </a>
                        <a href="<?php echo e(route('scheduling.appointment-types')); ?>" class="nav-item <?php echo e(request()->routeIs('scheduling.appointment-types*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-tags"></i></span>
                            <span class="nav-label">Types d'actes</span>
                        </a>
                        <a href="<?php echo e(route('scheduling.availability-blocks')); ?>" class="nav-item <?php echo e(request()->routeIs('scheduling.availability-blocks*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-clock-hour-3"></i></span>
                            <span class="nav-label">Disponibilités</span>
                        </a>
                        <a href="<?php echo e(route('scheduling.multi-specialty')); ?>" class="nav-item <?php echo e(request()->routeIs('scheduling.multi-specialty*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-arrows-shuffle"></i></span>
                            <span class="nav-label">Multi-spécialités</span>
                        </a>
                    <?php endif; ?>

                    <?php if($isAdmin || $isPractitioner || $isSecretary || $isAssistant): ?>
                        <div class="nav-section">Clinical</div>
                        <a href="<?php echo e(route('clinical.patients')); ?>" class="nav-item <?php echo e(request()->routeIs('clinical.patients') || request()->routeIs('clinical.patient.*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-users"></i></span>
                            <span class="nav-label">Patients</span>
                        </a>
                    <?php endif; ?>

                    <?php if($isPractitioner || $isSecretary || $isAdmin): ?>
                        <?php ($sidebarPatientId = (int) (request()->route('patientId') ?? 1)); ?>
                        <a href="<?php echo e(route('clinical.patient.show', ['patientId' => $sidebarPatientId])); ?>" class="nav-item <?php echo e(request()->routeIs('clinical.patient.show') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-file-medical"></i></span>
                            <span class="nav-label">Dossier Patient</span>
                        </a>
                    <?php endif; ?>

                    <?php if($isPractitioner || $isSecretary): ?>
                        <div class="nav-section">Cabinet</div>
                        <a href="<?php echo e(route('care.module2.index')); ?>" class="nav-item <?php echo e(request()->routeIs('care.module2.*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-activity-heartbeat"></i></span>
                            <span class="nav-label">Flux Patient</span>
                        </a>
                    <?php endif; ?>

                    <?php if($isPractitioner || $isAssistant || $isAdmin): ?>
                        <a href="<?php echo e(route('care.module3.index')); ?>" class="nav-item <?php echo e(request()->routeIs('care.module3.*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-stethoscope"></i></span>
                            <span class="nav-label">Clinique 3D</span>
                        </a>
                    <?php endif; ?>

                    <?php if(($isPractitioner || $isSecretary || $isAssistant || $isAdmin) && $risEnabled): ?>
                        <a href="<?php echo e(route('ris.exams.index')); ?>" class="nav-item <?php echo e(request()->routeIs('ris.*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-camera"></i></span>
                            <span class="nav-label">RIS Radiologie</span>
                        </a>
                    <?php endif; ?>

                    <?php if($isPractitioner || $isSecretary || $isAssistant || $isAdmin): ?>
                        <a href="<?php echo e(route('care.module4.index')); ?>" class="nav-item <?php echo e(request()->routeIs('care.module4.*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-flask"></i></span>
                            <span class="nav-label">Stérile et Lab</span>
                        </a>
                    <?php endif; ?>

                    <?php if($isAdmin): ?>
                        <div class="nav-section">Finance</div>
                        <a href="<?php echo e(route('billing.dashboard')); ?>" class="nav-item <?php echo e(request()->routeIs('billing.dashboard') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-cash"></i></span>
                            <span class="nav-label">Facturation</span>
                        </a>
                        <a href="<?php echo e(route('billing.invoices')); ?>" class="nav-item <?php echo e(request()->routeIs('billing.invoices*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-file-invoice"></i></span>
                            <span class="nav-label">Factures</span>
                        </a>
                        <a href="<?php echo e(route('billing.insurance.claims')); ?>" class="nav-item <?php echo e(request()->routeIs('billing.insurance.*') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-shield-check"></i></span>
                            <span class="nav-label">Assurances</span>
                        </a>
                        <a href="<?php echo e(route('billing.insurance.companies')); ?>" class="nav-item <?php echo e(request()->routeIs('billing.insurance.companies') ? 'active' : ''); ?>">
                            <span class="nav-icon"><i class="ti ti-building"></i></span>
                            <span class="nav-label">Compagnies</span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="nav-section">Public</div>
                <a href="<?php echo e(route('display.open')); ?>" class="nav-item" target="_blank">
                    <span class="nav-icon"><i class="ti ti-device-tv"></i></span>
                    <span class="nav-label">Écran TV</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <?php if(auth()->guard()->check()): ?>
                    <div class="user-info">
                        <div class="user-avatar"><?php echo e(substr(auth()->user()->name, 0, 1)); ?></div>
                        <div class="user-details">
                            <div class="user-name"><?php echo e(auth()->user()->name); ?></div>
                            <div class="user-role"><?php echo e(str_replace('_', ' ', auth()->user()->canonicalRole())); ?></div>
                        </div>
                    </div>
                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn-logout">
                            <span><i class="ti ti-logout"></i></span> Déconnexion
                        </button>
                    </form>
                <?php else: ?>
                    <a href="<?php echo e(route('login')); ?>" class="btn-login">
                        <span><i class="ti ti-lock-open"></i></span> Connexion
                    </a>
                <?php endif; ?>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <button class="btn-menu-toggle" onclick="document.querySelector('.app-layout')?.classList.toggle('sidebar-collapsed')">
                        <i class="ti ti-menu-2"></i>
                    </button>
                    <h1 class="topbar-title"><?php echo $__env->yieldContent('page-title', ''); ?></h1>
                </div>
                <div class="topbar-right">
                    <div class="lang-switcher">
                        <a href="<?php echo e(route('lang.switch', 'fr')); ?>" class="lang-btn <?php echo e(app()->getLocale() === 'fr' ? 'active' : ''); ?>">FR</a>
                        <a href="<?php echo e(route('lang.switch', 'ar')); ?>" class="lang-btn <?php echo e(app()->getLocale() === 'ar' ? 'active' : ''); ?>">AR</a>
                    </div>
                </div>
            </header>

            <div class="page-content">
                <?php if(session('success')): ?>
                    <div class="alert alert-success">
                        <span class="alert-icon">OK</span>
                        <?php echo e(session('success')); ?>

                        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                <?php endif; ?>
                <?php if(session('error')): ?>
                    <div class="alert alert-danger">
                        <span class="alert-icon">ERR</span>
                        <?php echo e(session('error')); ?>

                        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                <?php endif; ?>
                <?php if($errors->any()): ?>
                    <div class="alert alert-danger">
                        <span class="alert-icon">WARN</span>
                        <ul class="error-list" style="margin: 0.5rem 0 0 1rem;">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                <?php endif; ?>

                <?php if (! empty(trim($__env->yieldContent('content')))): ?>
                    <?php echo $__env->yieldContent('content'); ?>
                <?php else: ?>
                    <?php echo $__env->yieldContent('page'); ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH D:\xampp8.2\htdocs\fils_attente\resources\views/layouts/app.blade.php ENDPATH**/ ?>