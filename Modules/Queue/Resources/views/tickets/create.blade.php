@extends('layouts.admin')

@section('title', __('queue.ticket_kiosk'))

@section('content')
<div class="page-stack">
    <section class="card">
        <h1 class="page-title">{{ __('queue.ticket_kiosk') }}</h1>
        <p class="muted" style="margin-bottom:.8rem;">{{ __('queue.touch_hint') }}</p>

        <form method="GET" class="kiosk-org-form">
            <label class="label">{{ __('queue.select_organization') }}</label>
            <select class="select kiosk-select" name="organization_id" onchange="this.form.submit()">
                @foreach($organizations as $org)
                    <option value="{{ $org->id }}" @selected($organization?->id === $org->id)>{{ $org->name }}</option>
                @endforeach
            </select>
        </form>

        <form id="ticketForm" method="POST" action="{{ route('tickets.store') }}" style="display:none;">
            @csrf
            <input type="hidden" name="organization_id" value="{{ $organization?->id }}">
            <input type="hidden" name="service_id" id="serviceIdInput">
            <input type="hidden" name="direct_print" value="1">
        </form>

        <div class="kiosk-service-grid">
            @foreach($services as $service)
                <button type="button" class="btn btn-primary kiosk-btn kiosk-service-btn" onclick="createTicket('{{ $service->id }}', null)">
                    <span class="kiosk-service-name">{{ $service->name }}</span>
                    <span class="kiosk-service-line">{{ __('queue.ticket_code') }}: {{ $service->prefix }}</span>
                    <span class="kiosk-service-line">
                        {{ __('queue.realtime_eta') }}:
                        {{ $realtimeEtaByService[$service->id]['eta_for_new_ticket'] ?? ($service->average_service_minutes) }}
                        {{ __('queue.minutes') }}
                    </span>
                </button>
            @endforeach
        </div>
    </section>

    <section class="card">
        <h2 class="section-title">{{ __('queue.appointment_booking') }}</h2>
        <p class="muted" style="margin-bottom:.8rem;">{{ __('queue.appointment_hint') }}</p>
        <form id="appointmentForm" onsubmit="return submitAppointment(event)" class="kiosk-service-grid">
            <div>
                <label class="label">{{ __('queue.select_service') }}</label>
                <select class="select" id="appointmentService" required>
                    <option value="">{{ __('queue.select_service') }}</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('queue.appointment_time') }}</label>
                <input class="input" type="datetime-local" id="appointmentAt" required>
            </div>
            <div>
                <button class="btn btn-accent touch-btn" type="submit">{{ __('queue.create_appointment_ticket') }}</button>
            </div>
        </form>
    </section>

    <section class="card toolbar">
        <div class="display-open-box">
            <h2 class="section-title">{{ __('queue.public_display') }}</h2>
            <p class="muted">{{ __('queue.open_display') }}</p>
            <div class="split-actions">
                <input id="tvCodeInput" class="input tv-input" placeholder="{{ __('queue.tv_code') }} : TV-HOSP-01">
                <button type="button" class="btn btn-soft" onclick="openTvByCode()">{{ __('queue.open_by_code') }}</button>
            </div>
            @if(isset($screens) && $screens->count())
                <div class="split-actions">
                    <select id="tvCodeSelect" class="select tv-select">
                        <option value="">{{ __('queue.select_tv') }}</option>
                        @foreach($screens as $screen)
                            <option value="{{ $screen->code }}">{{ $screen->name }} ({{ $screen->code }})</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-soft" onclick="openTvFromSelect()">{{ __('queue.open_selected_tv') }}</button>
                </div>
            @endif
        </div>
        <a class="btn btn-accent touch-btn" href="{{ route('display.open') }}" target="_blank">{{ __('queue.public_display') }}</a>
    </section>
</div>

<script>
async function createTicket(serviceId, appointmentAt) {
    const form = document.getElementById('ticketForm');
    document.getElementById('serviceIdInput').value = serviceId;
    const data = new FormData(form);
    if (appointmentAt) {
        data.set('appointment_at', appointmentAt);
    } else {
        data.delete('appointment_at');
    }

    const res = await fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: data
    });

    if (!res.ok) {
        alert('Erreur lors de la creation du ticket');
        return;
    }

    const data = await res.json();
    if (!data.print_url) return;

    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = data.print_url;
    document.body.appendChild(iframe);
    setTimeout(() => iframe.remove(), 15000);
    setTimeout(() => window.location.reload(), 900);
}

function submitAppointment(event) {
    event.preventDefault();
    const serviceId = document.getElementById('appointmentService').value;
    const appointmentAt = document.getElementById('appointmentAt').value;
    if (!serviceId || !appointmentAt) return false;
    createTicket(serviceId, appointmentAt);
    return false;
}

function openTvByCode() {
    const code = (document.getElementById('tvCodeInput').value || '').trim().toUpperCase();
    if (!code) return;
    window.open(`/display/code/${encodeURIComponent(code)}`, '_blank');
}

function openTvFromSelect() {
    const code = document.getElementById('tvCodeSelect')?.value;
    if (!code) return;
    window.open(`/display/code/${encodeURIComponent(code)}`, '_blank');
}
</script>
@endsection


