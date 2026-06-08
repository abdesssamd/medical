@extends('layouts.app')

@section('title', __('queue.app_name'))

@section('page')
<div class="page-stack">
    <section class="card hero-card">
        <h1 class="page-title">{{ __('queue.app_name') }}</h1>
        <p class="muted" style="margin:0.45rem 0 1rem;max-width:760px;">
            {{ __('queue.touch_hint') }}
        </p>
        <div class="split-actions">
            <a class="btn btn-primary" href="{{ route('tickets.create') }}">{{ __('queue.ticket_kiosk') }}</a>
            <a class="btn btn-soft" href="{{ route('display.open') }}" target="_blank">{{ __('queue.public_display') }}</a>
            @guest
                <a class="btn btn-accent" href="{{ route('login') }}">{{ __('queue.login') }}</a>
            @endguest
        </div>
    </section>

    <section class="grid-two">
        <article class="card">
            <h2 class="section-title">{{ __('queue.ticket_kiosk') }}</h2>
            <p class="muted">{{ __('queue.appointment_hint') }}</p>
            <div class="split-actions" style="margin-top:.8rem;">
                <a class="btn btn-primary" href="{{ route('tickets.create') }}">{{ __('queue.open') ?? 'Ouvrir' }}</a>
            </div>
        </article>

        <article class="card">
            <h2 class="section-title">{{ __('queue.public_display') }}</h2>
            <p class="muted">{{ __('queue.tv_management_hint') }}</p>
            <div class="split-actions" style="margin-top:.8rem;">
                <a class="btn btn-soft" href="{{ route('display.open') }}" target="_blank">{{ __('queue.open_display') }}</a>
            </div>
        </article>
    </section>

    @auth
        <section class="card">
            <h2 class="section-title">{{ __('queue.quick_actions') ?? 'Acces rapide' }}</h2>
            <div class="split-actions">
                @if(auth()->user()->role === 'agent')
                    <a class="btn btn-primary" href="{{ route('agent.dashboard') }}">{{ __('queue.agent_dashboard') }}</a>
                @endif
                @if(auth()->user()->role === 'super_admin')
                    <a class="btn btn-primary" href="{{ route('admin.dashboard') }}">{{ __('queue.admin_dashboard') }}</a>
                    <a class="btn btn-soft" href="{{ route('admin.statistics') }}">{{ __('queue.statistics') }}</a>
                    <a class="btn btn-soft" href="{{ route('admin.settings') }}">{{ __('queue.general_settings') }}</a>
                @endif
            </div>
        </section>
    @endauth
</div>
@endsection



