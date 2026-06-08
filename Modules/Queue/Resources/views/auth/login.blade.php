<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123d36">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>{{ __('queue.login') }}</title>
    @vite(['resources/scss/app.scss', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex flex-column bg-body-tertiary">
<div class="page page-center">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <a href="{{ route('home') }}" class="navbar-brand navbar-brand-autodark fs-2 fw-bold text-decoration-none">
                {{ __('queue.app_name') }}
            </a>
        </div>

        <div class="card card-md">
            <div class="card-body">
                <h2 class="h2 text-center mb-3">{{ __('queue.login') }}</h2>
                <p class="text-secondary text-center">{{ __('queue.login_hint') }}</p>

                <form method="POST" action="{{ route('login.attempt') }}" autocomplete="off" novalidate>
                    @csrf
                    <x-tabler-input name="email" type="email" label="Email" icon="mail" :value="old('email')" required />
                    <x-tabler-input name="password" type="password" :label="__('queue.password')" icon="lock" required />
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember">
                        <span class="form-check-label">{{ __('queue.remember_me') }}</span>
                    </label>
                    <div class="form-footer mt-3">
                        <x-tabler-button type="submit" class="w-100">{{ __('queue.login') }}</x-tabler-button>
                    </div>
                </form>

                @if(isset($errors) && $errors->any())
                    <div class="alert alert-danger mt-3 mb-0">{{ $errors->first() }}</div>
                @endif
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
