@extends('layouts.admin')

@section('title', __('queue.track_ticket'))

@section('content')
<div class="card ticket-track-shell" x-data="ticketTrack({ code: '{{ $ticket->public_code }}' })" x-init="init()">
    <h1 class="page-title">{{ __('queue.track_ticket') }} - {{ $ticket->ticket_number }}</h1>

    <div class="grid-stats">
        <div class="card card-tight">
            <div class="label">{{ __('queue.status') }}</div>
            <div class="ticket-track-value" style="text-transform:capitalize;" x-text="status"></div>
        </div>
        <div class="card card-tight">
            <div class="label">{{ __('queue.service') }}</div>
            <div class="ticket-track-value" x-text="service"></div>
        </div>
        <div class="card card-tight">
            <div class="label">{{ __('queue.counter') }}</div>
            <div class="ticket-track-value" x-text="counter || '-' "></div>
        </div>
    </div>

    <div class="grid-stats">
        <div class="card card-tight">
            <div class="label">{{ __('queue.queue_position') }}</div>
            <div class="stat-number" x-text="position"></div>
        </div>
        <div class="card card-tight">
            <div class="label">{{ __('queue.realtime_eta') }}</div>
            <div class="stat-number"><span x-text="eta"></span> {{ __('queue.minutes') }}</div>
        </div>
    </div>

    <div class="card card-tight">
        <div class="muted">{{ __('queue.last_update') }}: <span x-text="updatedAt"></span></div>
    </div>
</div>

<script>
function ticketTrack(config) {
    return {
        code: config.code,
        status: @json($ticket->status),
        service: @json($ticket->service?->name),
        counter: @json($ticket->counter?->name),
        position: 0,
        eta: 0,
        updatedAt: '',
        init() {
            this.refresh();
            setInterval(() => this.refresh(), 4000);
        },
        async refresh() {
            const res = await fetch(`/api/tickets/track/${this.code}/status`);
            if (!res.ok) return;
            const data = await res.json();
            this.status = data.status || this.status;
            this.service = data.service || this.service;
            this.counter = data.counter || this.counter;
            this.position = data.position ?? this.position;
            this.eta = data.eta_minutes ?? this.eta;
            this.updatedAt = data.updated_at || '';
        }
    }
}
</script>
@endsection



