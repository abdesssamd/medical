<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ?? false ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123d36">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>{{ config('app.name') }} - @yield('title')</title>
    @vite(['resources/scss/app.scss', 'resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="@yield('body_class')">
    <header class="navbar navbar-expand-md d-print-none">
        <div class="container-xl">
            <a class="navbar-brand navbar-brand-autodark fs-3 fw-bold text-decoration-none" href="{{ route('home') }}">
                {{ __('queue.app_name') }}
            </a>
            <nav class="navbar-nav flex-row align-items-center gap-2">
                @auth
                    @if(auth()->user()->role === 'super_admin')
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.dashboard') }}">🏥 Admin</a>
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('scheduling.dashboard') }}">📅 Planification</a>
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('clinical.patients') }}">🦷 Dossiers</a>
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('billing.dashboard') }}">💰 Facturation</a>
                    @endif
                    @if(auth()->user()->role === 'agent')
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('agent.dashboard') }}">{{ __('queue.agent_dashboard') }}</a>
                    @endif
                @endauth
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('tickets.create') }}">{{ __('queue.ticket_kiosk') }}</a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('display.open') }}" target="_blank">{{ __('queue.public_display') }}</a>
            </nav>
            <div class="navbar-nav ms-auto flex-row align-items-center gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('lang.switch', 'fr') }}">FR</a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('lang.switch', 'ar') }}">AR</a>

                @auth
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm">{{ __('queue.logout') }}</button>
                    </form>
                @else
                    <a class="btn btn-primary btn-sm" href="{{ route('login') }}">{{ __('queue.login') }}</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="container-xl py-3">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('page')
    </main>

    @stack('scripts')
</body>
</html>
