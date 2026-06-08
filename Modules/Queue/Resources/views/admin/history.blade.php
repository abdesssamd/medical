@extends('layouts.admin')

@section('title', __('queue.history'))

@section('content')
<div class="page-stack">
    <section class="card">
        <h1 class="page-title">{{ __('queue.history') }}</h1>
        <form method="GET" class="form-row">
            <div>
                <label class="label">{{ __('queue.select_organization') }}</label>
                <select class="select" name="organization_id">
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" @selected($organization->id === $org->id)>{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('queue.service') }}</label>
                <select class="select" name="service_id">
                    <option value="">--</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" @selected(request('service_id') == $service->id)>{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('queue.status') }}</label>
                <select class="select" name="status">
                    <option value="">--</option>
                    @foreach(['waiting','called','served','absent','transferred'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('queue.date') }}</label>
                <input class="input" type="date" name="date" value="{{ request('date') }}">
            </div>
            <button class="btn btn-primary" type="submit">{{ __('queue.filter') }}</button>
        </form>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>{{ __('queue.ticket') }}</th><th>{{ __('queue.service') }}</th><th>{{ __('queue.status') }}</th><th>{{ __('queue.counter') }}</th><th>{{ __('queue.agent') }}</th><th>{{ __('queue.arrival_time') }}</th></tr></thead>
                <tbody>
                @foreach($tickets as $ticket)
                    <tr>
                        <td>{{ $ticket->ticket_number }}</td>
                        <td>{{ $ticket->service?->name }}</td>
                        <td>{{ $ticket->status }}</td>
                        <td>{{ $ticket->counter?->name ?? '-' }}</td>
                        <td>{{ $ticket->agent?->name ?? '-' }}</td>
                        <td>{{ $ticket->arrived_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $tickets->links() }}</div>
    </section>
</div>
@endsection



