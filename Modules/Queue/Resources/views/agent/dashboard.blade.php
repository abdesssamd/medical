@extends('layouts.admin')

@section('title', __('queue.agent_dashboard'))

@section('content')
<div x-data="agentQueue()" x-init="init()" class="page-stack">
    <section class="card toolbar">
        <div>
            <h1 class="page-title">{{ __('queue.agent_dashboard') }}</h1>
            <div class="muted">{{ $agent->name }} - {{ $organization->name }}</div>
        </div>
        <div class="muted">{{ __('queue.last_update') }}: <span x-text="serverTime"></span></div>
    </section>

    <section class="card">
        <form method="POST" action="{{ route('agent.call.next') }}" class="grid-two">
            @csrf
            <div>
                <label class="label">{{ __('queue.select_counter') }}</label>
                <select class="select" x-model="selectedCounter" name="counter_id" required>
                    @foreach($counters as $counter)
                        <option value="{{ $counter->id }}">{{ $counter->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('queue.select_service') }}</label>
                <select class="select" name="service_id" required>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:1/-1;">
                <button class="btn btn-primary touch-btn" type="submit">{{ __('queue.call_next') }}</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2 class="section-title">{{ __('queue.queue_live') }}</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('queue.ticket') }}</th>
                    <th>{{ __('queue.service') }}</th>
                    <th>{{ __('queue.status') }}</th>
                    <th>{{ __('queue.counter') }}</th>
                    <th>{{ __('queue.actions') }}</th>
                </tr>
                </thead>
                <tbody>
                <template x-for="ticket in tickets" :key="ticket.id">
                    <tr>
                        <td class="ticket-pill" x-text="ticket.ticket_number"></td>
                        <td x-text="ticket.service?.name"></td>
                        <td x-text="ticket.status"></td>
                        <td x-text="ticket.counter?.name ?? '-' "></td>
                        <td>
                            <div style="display:flex;flex-wrap:wrap;gap:.35rem;">
                                <template x-if="ticket.status === 'called'">
                                    <form :action="`/agent/tickets/${ticket.id}/recall`" method="POST">@csrf <button class="btn btn-soft">{{ __('queue.recall') }}</button></form>
                                </template>
                                <template x-if="ticket.status === 'called'">
                                    <form :action="`/agent/tickets/${ticket.id}/served`" method="POST">@csrf <button class="btn btn-success">{{ __('queue.mark_served') }}</button></form>
                                </template>
                                <template x-if="ticket.status === 'called'">
                                    <form :action="`/agent/tickets/${ticket.id}/absent`" method="POST">@csrf <button class="btn btn-accent">{{ __('queue.mark_absent') }}</button></form>
                                </template>
                                <template x-if="ticket.status === 'called'">
                                    <form :action="`/agent/tickets/${ticket.id}/transfer`" method="POST" style="display:flex;gap:.3rem;">
                                        @csrf
                                        <select class="select" name="service_id" style="padding:.45rem .5rem;min-width:150px;">
                                            @foreach($services as $service)
                                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-primary">{{ __('queue.transfer') }}</button>
                                    </form>
                                </template>
                            </div>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
function agentQueue() {
    return {
        tickets: @json($tickets),
        serverTime: '',
        selectedCounter: '{{ $counters->first()?->id }}',
        init() {
            this.refresh();
            setInterval(() => this.refresh(), 4000);
        },
        async refresh() {
            const res = await fetch(`{{ route('agent.queue-status') }}`);
            const data = await res.json();
            this.tickets = data.tickets;
            this.serverTime = data.server_time;
        }
    }
}
</script>
@endsection



