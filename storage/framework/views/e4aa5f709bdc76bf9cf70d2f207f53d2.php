<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="/manifest.webmanifest">
    
    <title><?php echo $__env->yieldContent('title', 'MediOffice'); ?> | Workspace</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500&display=swap" rel="stylesheet">
    
    <style>
        /* ========================================
           DESIGN SYSTEM REFONTE COMPLETE
           ======================================== */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --primary-glow: rgba(37, 99, 235, 0.15);
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            
            --dark-bg: #0f172a;
            --dark-surface: #1e293b;
            --dark-border: #334155;
            
            --light-bg: #f8fafc;
            --light-surface: #ffffff;
            --light-border: #e2e8f0;
            
            --glass-dark: rgba(15, 23, 42, 0.96);
            --glass-light: rgba(255, 255, 255, 0.7);
            --glass-blur: blur(16px);
            
            --sidebar-width: 280px;
            --header-height: 72px;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #0f172a;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Scrollbar moderne */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Layout principal */
        .app-layout {
            min-height: 100vh;
        }

        /* Sidebar moderne */
        .sidebar-modern {
            width: var(--sidebar-width);
            background: var(--glass-dark);
            backdrop-filter: var(--glass-blur);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
        }

        .sidebar-brand {
            padding: 28px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .brand-link {
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }

        .brand-icon i {
            font-size: 24px;
            color: white;
        }

        .brand-text {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, #94a3b8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
        }

        .role-badge {
            margin: 20px 16px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 14px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .role-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 14px;
        }

        .role-text {
            font-size: 13px;
            font-weight: 500;
            color: #cbd5e1;
        }

        /* Navigation */
        .nav-menu {
            flex: 1;
            padding: 16px 12px;
        }

        .nav-item {
            list-style: none;
            margin-bottom: 4px;
        }

        .nav-link-modern {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 12px;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 14px;
        }

        .nav-link-modern i {
            font-size: 20px;
            width: 24px;
        }

        .nav-link-modern:hover {
            background: rgba(255, 255, 255, 0.06);
            color: white;
            transform: translateX(4px);
        }

        .nav-link-modern.active {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(37, 99, 235, 0.1));
            color: white;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .badge-modern {
            background: #ef4444;
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
            margin-left: auto;
        }

        /* Sous-menus (Dropdown Sidebar) */
        .submenu-list {
            display: none;
            list-style: none;
            padding-left: 36px;
            margin-top: 4px;
            margin-bottom: 8px;
        }

        .submenu-list.show {
            display: block;
            animation: fadeInDown 0.2s ease-out;
        }

        .submenu-list.nested-submenu {
            padding-left: 20px;
            margin-bottom: 4px;
        }

        .submenu-link {
            display: block;
            padding: 10px 16px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            border-radius: 10px;
            transition: all 0.2s ease;
            position: relative;
        }

        .submenu-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #64748b;
            transition: all 0.2s ease;
        }

        .submenu-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.04);
            transform: translateX(4px);
        }

        .submenu-link:hover::before, .submenu-link.active::before {
            background: #3b82f6;
            box-shadow: 0 0 8px rgba(59, 130, 246, 0.6);
        }

        .submenu-link.active {
            color: white;
            font-weight: 600;
        }

        .nav-link-modern .submenu-icon {
            font-size: 16px;
            width: auto;
            transition: transform 0.3s ease;
        }

        .nav-link-modern.submenu-open .submenu-icon {
            transform: rotate(180deg);
        }

        .sidebar-footer {
            padding: 20px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 11px;
            color: #64748b;
        }

        /* Main Content */
        .main-content {
            width: calc(100vw - var(--sidebar-width));
            max-width: calc(100vw - var(--sidebar-width));
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            min-width: 0;
        }

        /* Header moderne */
        .header-modern {
            background: var(--glass-light);
            backdrop-filter: var(--glass-blur);
            border-bottom: 1px solid var(--light-border);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 0 32px;
            width: 100%;
        }

        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: var(--header-height);
        }

        .page-title-section {
            display: flex;
            align-items: baseline;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-pretitle {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #3b82f6;
        }

        .page-title {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #0f172a, #334155);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
        }

        /* Patient Context Chip */
        .patient-context-chip {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: white;
            border-radius: 100px;
            padding: 6px 8px 6px 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--light-border);
            transition: all 0.2s ease;
        }

        .patient-context-chip:hover {
            box-shadow: var(--shadow-md);
            border-color: #cbd5e1;
        }

        .context-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #475569;
        }

        .context-item i {
            font-size: 16px;
            color: #3b82f6;
        }

        .context-name {
            font-weight: 700;
            color: #0f172a;
        }

        .context-sep {
            width: 1px;
            height: 20px;
            background: #e2e8f0;
        }

        .context-release {
            background: transparent;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #94a3b8;
            transition: all 0.2s;
            font-size: 12px;
            font-weight: 600;
        }

        .context-release:hover {
            background: #fee2e2;
            color: #ef4444;
        }

        /* User Section */
        .user-section {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .date-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #64748b;
            background: #f1f5f9;
            padding: 6px 14px;
            border-radius: 40px;
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 40px;
            transition: all 0.2s;
            background: white;
            border: 1px solid var(--light-border);
        }

        .user-dropdown:hover {
            background: #f8fafc;
            box-shadow: var(--shadow-sm);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }

        .user-info {
            text-align: left;
        }

        .user-name {
            font-weight: 700;
            font-size: 13px;
            color: #0f172a;
        }

        .user-status {
            font-size: 11px;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Page Body */
        .page-body-modern {
            padding: 32px;
            background: transparent;
            width: 100%;
        }

        /* Cards & Content area */
        .content-card {
            background: white;
            border-radius: 24px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        #content-area {
            animation: fadeInUp 0.4s ease-out;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar-modern {
                transform: translateX(-100%);
            }
            .sidebar-modern.mobile-open {
                transform: translateX(0);
            }
            .main-content {
                width: 100%;
                max-width: 100%;
                margin-left: 0;
            }
            .header-modern {
                padding: 0 16px;
            }
            .page-title {
                font-size: 22px;
            }
            .page-body-modern {
                padding: 20px;
            }
            .context-item.optional,
            .context-sep.optional {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .user-info {
                display: none;
            }
            .date-badge {
                display: none;
            }
            .patient-context-chip {
                padding: 4px 6px 4px 12px;
                font-size: 11px;
            }
            .context-item {
                font-size: 11px;
            }
        }

        /* Bouton mobile toggle */
        .mobile-toggle {
            display: none;
            background: white;
            border: 1px solid var(--light-border);
            border-radius: 12px;
            padding: 8px;
            cursor: pointer;
            margin-right: 12px;
        }

        @media (max-width: 992px) {
            .mobile-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        /* Dropdown personnalisé */
        .dropdown-menu-custom {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--light-border);
            padding: 8px;
            min-width: 200px;
        }

        .dropdown-item-custom {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 10px;
            color: #334155;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 14px;
        }

        .dropdown-item-custom:hover {
            background: #f1f5f9;
        }

        .dropdown-item-custom.text-danger:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        hr {
            margin: 8px 0;
            border-color: #e2e8f0;
        }
    </style>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/scss/app.scss', 'resources/js/app.js']); ?>
    <?php echo $__env->yieldPushContent('head'); ?>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>

<body class="app-layout <?php echo $__env->yieldContent('body_class'); ?>">

<?php
    $user = auth()->user();
    $canonicalRole = $user?->canonicalRole() ?? 'guest';
    
    $isAdmin = $user?->hasAnyRole(['super_admin', 'admin']) ?? false;
    $isPractitioner = $user?->hasAnyRole(['professional', 'doctor', 'medecin']) ?? false;
    $isSecretary = $user?->hasAnyRole(['secretary', 'secretaire']) ?? false;
    $isAssistant = $user?->hasAnyRole(['assistant']) ?? false;

    $risEnabled = (bool) config('ris.enabled', false);
    if (!$risEnabled && class_exists('Modules\\Queue\\Models\\AppSetting')) {
        $risEnabled = filter_var((string) \Modules\Queue\Models\AppSetting::getValue('module.ris.enabled', false), FILTER_VALIDATE_BOOL);
    }

    $homeRoute = match (true) {
        $isAdmin => route('care.module1.index'),
        $isPractitioner => route('appointment.pro.dashboard'),
        $isSecretary => route('appointment.sec.dashboard'),
        $isAssistant => route('care.module3.index'),
        default => route('home'),
    };

    // Définition des éléments de menu
    $menu = [];
    if ($isAdmin) {
        $menu = [
            ['label' => 'Pilotage Global', 'icon' => 'ti ti-layout-dashboard', 'route' => 'care.module1.index', 'active' => 'care.module1.*'],
            ['label' => 'Flux Patients', 'icon' => 'ti ti-arrows-exchange', 'route' => 'care.module2.index', 'active' => 'care.module2.*'],
            ['label' => 'Dossiers Cliniques', 'icon' => 'ti ti-stethoscope', 'route' => 'care.module3.index', 'active' => 'care.module3.*'],
            ['label' => 'Stérilisation & Labo', 'icon' => 'ti ti-flask', 'route' => 'care.module4.index', 'active' => 'care.module4.*'],
            ['label' => 'File d\'attente', 'icon' => 'ti ti-list-numbers', 'route' => 'admin.dashboard', 'active' => 'admin.*'],
            ['label' => 'Paramétrages', 'icon' => 'ti ti-settings-automation', 'submenu' => [
                ['label' => 'Utilisateurs', 'route' => 'users.index', 'active' => 'users.*'],
				['label' => 'Template ris', 'route' => 'users.index', 'active' => 'users.*'],
                ['label' => 'Questionnaires', 'route' => 'clinical.questionnaire-templates.index', 'active' => 'clinical.questionnaire-templates.*'],
                
				['label' => 'Paramètres généraux', 'icon' => 'ti ti-adjustments', 'submenu' => [
                    ['label' => 'Liste des actes', 'route' => 'procedures.index', 'active' => 'procedures.*'],
                    ['label' => 'Liste des médicaments', 'route' => 'admin.medications.index', 'active' => 'admin.medications.*'],
                    ['label' => 'Liste des codes CIM-10', 'route' => 'admin.icd10-codes.index', 'active' => 'admin.icd10-codes.*'],
                ]],
            ]],
        ];
    } elseif ($isPractitioner) {
        $menu = [
            ['label' => 'Mon Dashboard', 'icon' => 'ti ti-smart-home', 'route' => 'appointment.pro.dashboard', 'active' => 'appointment.pro.*'],
            ['label' => 'Flux Patients', 'icon' => 'ti ti-users-group', 'route' => 'care.module2.index', 'active' => 'care.module2.*'],
            ['label' => 'Soins Cliniques', 'icon' => 'ti ti-heart-rate-monitor', 'route' => 'care.module3.index', 'active' => 'care.module3.*'],
            ['label' => 'Base Patients', 'icon' => 'ti ti-user-search', 'route' => 'clinical.patients', 'active' => 'clinical.patients*'],
            ['label' => 'Stérilisation', 'icon' => 'ti ti-flask', 'route' => 'care.module4.index', 'active' => 'care.module4.*'],
        ];
    } elseif ($isSecretary) {
        $menu = [
            ['label' => 'Accueil Secrétariat', 'icon' => 'ti ti-calendar-stats', 'route' => 'appointment.sec.dashboard', 'active' => 'appointment.sec.*'],
            ['label' => 'Flux Patients', 'icon' => 'ti ti-users', 'route' => 'care.module2.index', 'active' => 'care.module2.*'],
            ['label' => 'Base Patients', 'icon' => 'ti ti-user-search', 'route' => 'clinical.patients', 'active' => 'clinical.patients*'],
            ['label' => 'Stérilisation', 'icon' => 'ti ti-flask', 'route' => 'care.module4.index', 'active' => 'care.module4.*'],
            ['label' => 'Billetterie', 'icon' => 'ti ti-ticket', 'route' => 'tickets.create', 'active' => 'tickets.*'],
        ];
    } elseif ($isAssistant) {
        $menu = [
            ['label' => 'Dashboard', 'icon' => 'ti ti-layout-grid', 'route' => 'care.module3.index', 'active' => 'care.module3.*'],
            ['label' => 'Dossier Clinique', 'icon' => 'ti ti-heart-rate-monitor', 'route' => 'care.module3.index', 'active' => 'care.module3.*'],
            ['label' => 'Patients', 'icon' => 'ti ti-user-search', 'route' => 'clinical.patients', 'active' => 'clinical.patients*'],
            ['label' => 'Stérilisation', 'icon' => 'ti ti-flask', 'route' => 'care.module4.index', 'active' => 'care.module4.*'],
        ];
    }

    if (($isAdmin || $isPractitioner || $isSecretary || $isAssistant) && $risEnabled) {
        $menu[] = ['label' => 'Radiologie RIS', 'icon' => 'ti ti-microscope', 'route' => 'ris.exams.index', 'active' => 'ris.*'];
    }

    $roleLabel = str_replace('_', ' ', ucfirst($canonicalRole));
?>

<aside class="sidebar-modern" id="sidebar">
    <div class="sidebar-brand">
        <a href="<?php echo e($homeRoute); ?>" class="brand-link">
            <div class="brand-icon">
                <i class="ti ti-activity-heartbeat"></i>
            </div>
            <span class="brand-text">MediOffice</span>
        </a>
    </div>

    <div class="role-badge">
        <div class="role-avatar"><?php echo e(substr($roleLabel, 0, 1)); ?></div>
        <div class="role-text"><?php echo e($roleLabel); ?></div>
    </div>

    <ul class="nav-menu">
        <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(isset($item['submenu'])): ?>
                <?php 
                    $isSubmenuActive = collect($item['submenu'])->contains(fn($sub) => 
                        request()->routeIs($sub['active'] ?? '') ||
                        (isset($sub['submenu']) && collect($sub['submenu'])->contains(fn($nested) => request()->routeIs($nested['active'] ?? '')))
                    );
                ?>
                <li class="nav-item">
                    <a class="nav-link-modern <?php echo e($isSubmenuActive ? 'active submenu-open' : ''); ?>" href="#" onclick="toggleSubmenu(event, this)">
                        <i class="<?php echo e($item['icon']); ?>"></i>
                        <span style="flex:1"><?php echo e($item['label']); ?></span>
                        <i class="ti ti-chevron-down submenu-icon"></i>
                    </a>
                    <ul class="submenu-list <?php echo e($isSubmenuActive ? 'show' : ''); ?>">
                        <?php $__currentLoopData = $item['submenu']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if(isset($subItem['submenu'])): ?>
                                <?php 
                                    $isNestedActive = collect($subItem['submenu'])->contains(fn($nested) => request()->routeIs($nested['active'] ?? ''));
                                ?>
                                <li>
                                    <a href="#" onclick="toggleSubmenu(event, this)" class="submenu-link" style="display:flex;align-items:center;justify-content:space-between;">
                                        <span><?php echo e($subItem['label']); ?></span>
                                        <i class="ti ti-chevron-down submenu-icon" style="font-size:14px;"></i>
                                    </a>
                                    <ul class="submenu-list nested-submenu <?php echo e($isNestedActive ? 'show' : ''); ?>">
                                        <?php $__currentLoopData = $subItem['submenu']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nestedItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php $isSubActive = request()->routeIs($nestedItem['active'] ?? ''); ?>
                                            <li>
                                                <a href="<?php echo e(Route::has($nestedItem['route'] ?? '') ? route($nestedItem['route'], $nestedItem['params'] ?? []) : '#'); ?>" class="submenu-link <?php echo e($isSubActive ? 'active' : ''); ?>">
                                                    <?php echo e($nestedItem['label']); ?>

                                                </a>
                                            </li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                </li>
                            <?php else: ?>
                                <?php $isSubActive = request()->routeIs($subItem['active'] ?? ''); ?>
                                <li>
                                    <a href="<?php echo e(Route::has($subItem['route'] ?? '') ? route($subItem['route'], $subItem['params'] ?? []) : '#'); ?>" class="submenu-link <?php echo e($isSubActive ? 'active' : ''); ?>">
                                        <?php echo e($subItem['label']); ?>

                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </li>
            <?php else: ?>
                <?php $isActive = request()->routeIs($item['active'] ?? ''); ?>
                <li class="nav-item">
                    <a class="nav-link-modern <?php echo e($isActive ? 'active' : ''); ?>" href="<?php echo e(Route::has($item['route'] ?? '') ? route($item['route'], $item['params'] ?? []) : '#'); ?>">
                        <i class="<?php echo e($item['icon']); ?>"></i>
                        <span style="flex:1"><?php echo e($item['label']); ?></span>
                        <?php if(($item['route'] ?? '') === 'care.module2.index'): ?>
                            <span id="module2-consultations-badge" class="badge-modern d-none">0</span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>

    <div class="sidebar-footer">
        v2.4.0 • Stable
    </div>
</aside>

<div class="main-content">
    <header class="header-modern">
        <div class="header-inner">
            <div style="display: flex; align-items: center;">
                <button class="mobile-toggle" id="mobileToggleBtn">
                    <i class="ti ti-menu-2" style="font-size: 20px;"></i>
                </button>
                <div class="page-title-section">
                    <div class="page-pretitle"><?php echo $__env->yieldContent('page_pretitle', $roleLabel); ?></div>
                    <h1 class="page-title"><?php echo $__env->yieldContent('page_title', 'Aperçu'); ?></h1>
                    <div id="header-patient-context" class="patient-context-chip d-none" role="status" aria-live="polite">
                        <span class="context-item">
                            <i class="ti ti-user-circle"></i>
                            <strong id="header-patient-name">-</strong>
                        </span>
                        <span class="context-sep"></span>
                        <span class="context-item">
                            <i class="ti ti-id-badge"></i>
                            <span id="header-patient-mrn">MRN -</span>
                        </span>
                        <span class="context-sep optional"></span>
                        <span class="context-item optional">
                            <i class="ti ti-calendar"></i>
                            <span id="header-patient-age">Age -</span>
                        </span>
                        <span class="context-sep optional"></span>
                        <span class="context-item optional">
                            <i class="ti ti-phone"></i>
                            <span id="header-patient-phone">Tel -</span>
                        </span>
                        <button type="button" id="header-patient-release" class="context-release" aria-label="Libérer le patient actif">✕</button>
                    </div>
                </div>
            </div>

            <div class="user-section">
                <div class="date-badge">
                    <i class="ti ti-calendar-event"></i>
                    <span><?php echo e(now()->translatedFormat('l d F Y')); ?></span>
                </div>

                <div class="dropdown">
                    <div class="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            <?php echo e(substr($user->name ?? 'U', 0, 1)); ?>

                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo e($user->name ?? 'Utilisateur'); ?></div>
                            <div class="user-status"><i class="ti ti-circle-filled" style="font-size: 8px;"></i> Session active</div>
                        </div>
                        <i class="ti ti-chevron-down" style="font-size: 14px; color: #94a3b8;"></i>
                    </div>
                    <div class="dropdown-menu dropdown-menu-custom dropdown-menu-end">
                        <a href="<?php echo e($homeRoute); ?>" class="dropdown-item-custom">
                            <i class="ti ti-dashboard"></i> Dashboard
                        </a>
                        <hr>
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button class="dropdown-item-custom text-danger" type="submit" style="width:100%; background:transparent; border:none; cursor:pointer;">
                                <i class="ti ti-logout"></i> Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="page-body-modern">
        <div id="content-area" data-spa-content>
            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>
</div>

<script>
    // Fonction pour ouvrir/fermer les sous-menus
    function toggleSubmenu(event, element) {
        event.preventDefault();
        element.classList.toggle('submenu-open');
        const submenu = element.nextElementSibling;
        if (submenu) {
            submenu.classList.toggle('show');
        }
    }

    // Mobile sidebar toggle (sans toucher aux fonctionnalités existantes)
    const mobileToggle = document.getElementById('mobileToggleBtn');
    const sidebar = document.getElementById('sidebar');
    
    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
        });
        
        // Fermer le sidebar si on clique en dehors sur mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 992) {
                const isClickInside = sidebar.contains(event.target) || mobileToggle.contains(event.target);
                if (!isClickInside && sidebar.classList.contains('mobile-open')) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });
    }
    
    // S'assurer que le badge et le contexte patient fonctionnent comme avant
    // (conserve toutes les fonctionnalités existantes)
    if (typeof window.dispatchEvent === 'function') {
        // déclencher un événement pour signaler que le DOM est prêt
        document.dispatchEvent(new Event('DOMContentLoaded'));
    }
</script>

<?php echo $__env->yieldPushContent('scripts'); ?>

<script>
    // Tiny helper pour s'assurer que le context patient s'affiche comme avant
    (function() {
        const oldShowContext = window.showPatientContext;
        window.showPatientContext = window.showPatientContext || function(patient) {
            const chip = document.getElementById('header-patient-context');
            if (chip && patient) {
                chip.classList.remove('d-none');
                const nameSpan = document.getElementById('header-patient-name');
                const mrnSpan = document.getElementById('header-patient-mrn');
                const ageSpan = document.getElementById('header-patient-age');
                const phoneSpan = document.getElementById('header-patient-phone');
                if (nameSpan) nameSpan.innerText = patient.name || patient.full_name || '-';
                if (mrnSpan) mrnSpan.innerText = patient.mrn || patient.id || '-';
                if (ageSpan) ageSpan.innerText = patient.age || patient.birth_date ? '--' : '-';
                if (phoneSpan) phoneSpan.innerText = patient.phone || patient.mobile || '-';
            }
            if (oldShowContext) oldShowContext(patient);
        };
        
        const releaseBtn = document.getElementById('header-patient-release');
        if (releaseBtn) {
            releaseBtn.addEventListener('click', function() {
                const chip = document.getElementById('header-patient-context');
                if (chip) chip.classList.add('d-none');
                // Déclencher un event pour les autres modules
                const event = new CustomEvent('patient-released');
                document.dispatchEvent(event);
            });
        }
    })();
</script>

</body>
</html><?php /**PATH D:\xampp8.2\htdocs\fils_attente\resources\views/layouts/admin.blade.php ENDPATH**/ ?>