@extends('layouts.admin')

@section('title', __('queue.admin_dashboard'))

@section('content')
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h1 class="page-title">{{ __('queue.admin_dashboard') }}</h1>
            <div class="muted">{{ $organization->name }}</div>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('admin.statistics', ['organization_id' => $organization->id]) }}">{{ __('queue.statistics') }}</a>
            <a class="btn btn-soft" href="{{ route('admin.history', ['organization_id' => $organization->id]) }}">{{ __('queue.history') }}</a>
            <a class="btn btn-soft" href="{{ route('admin.users') }}">{{ __('queue.user_management') }}</a>
            <a class="btn btn-soft" href="{{ route('admin.counters') }}">{{ __('queue.counter_management') }}</a>
            <a class="btn btn-soft" href="{{ route('admin.kiosks') }}">{{ __('queue.kiosk_management') }}</a>
            <a class="btn btn-soft" href="{{ route('admin.screens') }}">{{ __('queue.tv_management') }}</a>
        </div>
    </section>

    <section class="card">
        <form method="GET" class="form-row">
            <div>
                <label class="label">{{ __('queue.select_organization') }}</label>
                <select class="select" name="organization_id">
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" @selected($organization->id === $org->id)>{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit">OK</button>
        </form>
    </section>

    <section class="grid-stats">
        <div class="card"><div class="label">{{ __('queue.today_total') }}</div><div class="stat-number">{{ $stats['today_total'] }}</div></div>
        <div class="card"><div class="label">{{ __('queue.today_waiting') }}</div><div class="stat-number">{{ $stats['today_waiting'] }}</div></div>
        <div class="card"><div class="label">{{ __('queue.today_served') }}</div><div class="stat-number">{{ $stats['today_served'] }}</div></div>
        <div class="card"><div class="label">{{ __('queue.today_absent') }}</div><div class="stat-number">{{ $stats['today_absent'] }}</div></div>
        <div class="card"><div class="label">{{ __('queue.avg_wait') }}</div><div class="stat-number">{{ $stats['avg_wait_minutes'] }} {{ __('queue.minutes') }}</div></div>
    </section>

    <section class="card">
        <h2 class="section-title">{{ __('queue.service_stats') }}</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>{{ __('queue.service') }}</th><th>Total</th><th>{{ __('queue.today_served') }}</th><th>Absent %</th></tr></thead>
                <tbody>
                @foreach($serviceStats as $row)
                    <tr>
                        <td>{{ $row['service_name'] }}</td>
                        <td>{{ $row['total'] }}</td>
                        <td>{{ $row['served'] }}</td>
                        <td>{{ $row['absent_rate'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2 class="section-title">{{ __('queue.recent_tickets') }}</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>{{ __('queue.ticket') }}</th><th>{{ __('queue.service') }}</th><th>{{ __('queue.status') }}</th><th>{{ __('queue.counter') }}</th><th>{{ __('queue.agent') }}</th></tr></thead>
                <tbody>
                @foreach($recentTickets as $ticket)
                    <tr>
                        <td>{{ $ticket->ticket_number }}</td>
                        <td>{{ $ticket->service?->name }}</td>
                        <td>{{ $ticket->status }}</td>
                        <td>{{ $ticket->counter?->name ?? '-' }}</td>
                        <td>{{ $ticket->agent?->name ?? '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection



