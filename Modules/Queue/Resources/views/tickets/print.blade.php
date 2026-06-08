@extends('layouts.admin')

@section('title', __('queue.print_ticket'))

@section('content')
<div class="card ticket-print-shell">
    <div class="ticket-print-org">{{ $ticket->service->organization->name }}</div>
    <div class="muted">{{ $ticket->service->name }}</div>
    <div class="ticket-print-number">{{ $ticket->ticket_number }}</div>
    <div>{{ __('queue.arrival_time') }}: {{ $ticket->arrived_at->format('H:i:s') }}</div>
    <div>{{ __('queue.estimated_wait') }}: {{ $ticket->estimated_wait_minutes }} {{ __('queue.minutes') }}</div>
    @if($ticket->is_appointment && $ticket->appointment_at)
        <div>{{ __('queue.appointment_time') }}: {{ $ticket->appointment_at->format('Y-m-d H:i') }}</div>
    @endif
    @if($ticket->public_code)
        <div class="ticket-print-track">
            <div class="ticket-print-track-title">{{ __('queue.track_ticket') }}</div>
            <canvas id="ticketQrCanvas" class="ticket-print-qr"></canvas>
            <div class="ticket-print-link">
                <a href="{{ route('tickets.track', $ticket->public_code) }}" target="_blank">{{ route('tickets.track', $ticket->public_code) }}</a>
            </div>
        </div>
    @endif
    <div class="split-actions" style="margin-top:1rem;justify-content:center;">
        <button onclick="window.print()" class="btn btn-primary touch-btn">{{ __('queue.print_ticket') }}</button>
        <a href="{{ route('tickets.create', ['organization_id' => $ticket->organization_id]) }}" class="btn btn-soft touch-btn">{{ __('queue.back_kiosk') }}</a>
    </div>
</div>
@if($ticket->public_code)
<script>
window.addEventListener('load', async () => {
    if (!window.QRCodeLib) return;
    const canvas = document.getElementById('ticketQrCanvas');
    if (!canvas) return;
    await window.QRCodeLib.toCanvas(canvas, @json(route('tickets.track', $ticket->public_code)), {
        width: 164,
        margin: 1,
        color: { dark: '#0F172A', light: '#FFFFFF' }
    });
});
</script>
@endif
@if(request()->boolean('auto'))
<script>
window.addEventListener('load', () => {
    setTimeout(() => window.print(), 120);
    const redirectUrl = @json(request('redirect', route('tickets.create', ['organization_id' => $ticket->organization_id])));
    setTimeout(() => {
        window.location.replace(redirectUrl);
    }, 900);
});
</script>
@endif
@endsection



