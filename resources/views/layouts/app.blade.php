<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ?? false ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>{{ config('app.name', 'MediOffice') }} — @yield('title', 'Dashboard')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.14.0/dist/tabler-icons.min.css">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('head')
    @stack('styles')
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon"><i class="ti ti-activity-heartbeat"></i></div>
                <span class="brand-text">MediOffice</span>
            </div>

            @php
                $user = auth()->user();
                $isAdmin = $user?->hasAnyRole(['super_admin', 'admin']) ?? false;
                $isPractitioner = $user?->hasAnyRole(['professional', 'doctor', 'medecin']) ?? false;
                $isSecretary = $user?->hasAnyRole(['secretary', 'secretaire']) ?? false;
                $isAssistant = $user?->hasAnyRole(['assistant']) ?? false;
                $risEnabled = (bool) config('ris.enabled', false);

                if (! $risEnabled && class_exists('Modules\\Queue\\Models\\AppSetting')) {
                    $risEnabled = filter_var((string) \Modules\Queue\Models\AppSetting::getValue('module.ris.enabled', false), FILTER_VALIDATE_BOOL) === true;
                }
            @endphp

            <nav class="sidebar-nav">
                <a href="{{ route('home') }}" class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="ti ti-home"></i></span>
                    <span class="nav-label">Accueil</span>
                </a>

                @auth
                    @if($isAdmin)
                        <div class="nav-section">Queue</div>
                        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.*') && !request()->routeIs('admin.supervisor*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-users-group"></i></span>
                            <span class="nav-label">Admin Queue</span>
                        </a>
                        <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-settings"></i></span>
                            <span class="nav-label">Paramètres cabinet</span>
                        </a>
                        <a href="{{ route('admin.supervisor') }}" class="nav-item {{ request()->routeIs('admin.supervisor*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-chart-bar"></i></span>
                            <span class="nav-label">Superviseur</span>
                        </a>
                        <a href="{{ route('agent.dashboard') }}" class="nav-item {{ request()->routeIs('agent.*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-user"></i></span>
                            <span class="nav-label">Agent</span>
                        </a>
                        <a href="{{ route('tickets.create') }}" class="nav-item {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-ticket"></i></span>
                            <span class="nav-label">Billetterie</span>
                        </a>

                        <div class="nav-section">Planification</div>
                        <a href="{{ route('scheduling.dashboard') }}" class="nav-item {{ request()->routeIs('scheduling.dashboard') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-calendar-time"></i></span>
                            <span class="nav-label">Planning</span>
                        </a>
                        <a href="{{ route('scheduling.appointment-types') }}" class="nav-item {{ request()->routeIs('scheduling.appointment-types*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-tags"></i></span>
                            <span class="nav-label">Types d'actes</span>
                        </a>
                        <a href="{{ route('scheduling.availability-blocks') }}" class="nav-item {{ request()->routeIs('scheduling.availability-blocks*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-clock-hour-3"></i></span>
                            <span class="nav-label">Disponibilités</span>
                        </a>
                        <a href="{{ route('scheduling.multi-specialty') }}" class="nav-item {{ request()->routeIs('scheduling.multi-specialty*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-arrows-shuffle"></i></span>
                            <span class="nav-label">Multi-spécialités</span>
                        </a>
                    @endif

                    @if($isAdmin || $isPractitioner || $isSecretary || $isAssistant)
                        <div class="nav-section">Clinical</div>
                        <a href="{{ route('clinical.patients') }}" class="nav-item {{ request()->routeIs('clinical.patients') || request()->routeIs('clinical.patient.*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-users"></i></span>
                            <span class="nav-label">Patients</span>
                        </a>
                    @endif

                    @if($isPractitioner || $isSecretary || $isAdmin)
                        @php($sidebarPatientId = (int) (request()->route('patientId') ?? 1))
                        <a href="{{ route('clinical.patient.show', ['patientId' => $sidebarPatientId]) }}" class="nav-item {{ request()->routeIs('clinical.patient.show') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-file-medical"></i></span>
                            <span class="nav-label">Dossier Patient</span>
                        </a>
                    @endif

                    @if($isPractitioner || $isSecretary)
                        <div class="nav-section">Cabinet</div>
                        <a href="{{ route('care.module2.index') }}" class="nav-item {{ request()->routeIs('care.module2.*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-activity-heartbeat"></i></span>
                            <span class="nav-label">Flux Patient</span>
                        </a>
                    @endif

                    @if($isPractitioner || $isAssistant || $isAdmin)
                        <a href="{{ route('care.module3.index') }}" class="nav-item {{ request()->routeIs('care.module3.*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-stethoscope"></i></span>
                            <span class="nav-label">Clinique 3D</span>
                        </a>
                    @endif

                    @if(($isPractitioner || $isSecretary || $isAssistant || $isAdmin) && $risEnabled)
                        <a href="{{ route('ris.exams.index') }}" class="nav-item {{ request()->routeIs('ris.*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-camera"></i></span>
                            <span class="nav-label">RIS Radiologie</span>
                        </a>
                    @endif

                    @if($isPractitioner || $isSecretary || $isAssistant || $isAdmin)
                        <a href="{{ route('care.module4.index') }}" class="nav-item {{ request()->routeIs('care.module4.*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-flask"></i></span>
                            <span class="nav-label">Stérile et Lab</span>
                        </a>
                    @endif

                    @if($isAdmin)
                        <div class="nav-section">Finance</div>
                        <a href="{{ route('billing.dashboard') }}" class="nav-item {{ request()->routeIs('billing.dashboard') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-cash"></i></span>
                            <span class="nav-label">Facturation</span>
                        </a>
                        <a href="{{ route('billing.invoices') }}" class="nav-item {{ request()->routeIs('billing.invoices*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-file-invoice"></i></span>
                            <span class="nav-label">Factures</span>
                        </a>
                        <a href="{{ route('billing.insurance.claims') }}" class="nav-item {{ request()->routeIs('billing.insurance.*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-shield-check"></i></span>
                            <span class="nav-label">Assurances</span>
                        </a>
                        <a href="{{ route('billing.insurance.companies') }}" class="nav-item {{ request()->routeIs('billing.insurance.companies') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="ti ti-building"></i></span>
                            <span class="nav-label">Compagnies</span>
                        </a>
                    @endif
                @endauth

                <div class="nav-section">Public</div>
                <a href="{{ route('display.open') }}" class="nav-item" target="_blank">
                    <span class="nav-icon"><i class="ti ti-device-tv"></i></span>
                    <span class="nav-label">Écran TV</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                @auth
                    <div class="user-info">
                        <div class="user-avatar">{{ substr(auth()->user()->name, 0, 1) }}</div>
                        <div class="user-details">
                            <div class="user-name">{{ auth()->user()->name }}</div>
                            <div class="user-role">{{ str_replace('_', ' ', auth()->user()->canonicalRole()) }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-logout">
                            <i class="ti ti-logout"></i> Déconnexion
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-login">
                        <i class="ti ti-lock-open"></i> Connexion
                    </a>
                @endauth
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <button class="btn-menu-toggle" onclick="document.querySelector('.app-layout')?.classList.toggle('sidebar-collapsed')">
                        <i class="ti ti-menu-2"></i>
                    </button>
                    <h1 class="topbar-title">@yield('page-title', '')</h1>
                </div>
                <div class="topbar-right">
                    <div class="lang-switcher">
                        <a href="{{ route('lang.switch', 'fr') }}" class="lang-btn {{ app()->getLocale() === 'fr' ? 'active' : '' }}">FR</a>
                        <a href="{{ route('lang.switch', 'ar') }}" class="lang-btn {{ app()->getLocale() === 'ar' ? 'active' : '' }}">AR</a>
                    </div>
                </div>
            </header>

            <div class="page-content">
                @if(session('success'))
                    <div class="alert alert-success">
                        <span class="alert-icon">OK</span>
                        {{ session('success') }}
                        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">
                        <span class="alert-icon">ERR</span>
                        {{ session('error') }}
                        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <span class="alert-icon">WARN</span>
                        <ul class="error-list" style="margin: 0.5rem 0 0 1rem;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                @endif

                @hasSection('content')
                    @yield('content')
                @else
                    @yield('page')
                @endif
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
