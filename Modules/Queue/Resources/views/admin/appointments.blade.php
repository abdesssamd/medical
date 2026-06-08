@extends('layouts.admin')

@section('title', __('queue.appointment_management'))

@section('content')
<div class="page-stack">
    <section class="card">
        <h1 class="page-title">{{ __('queue.appointment_management') }}</h1>
        <form method="GET" class="grid-two" style="margin-top:.8rem;">
            <div>
                <label class="label">{{ __('queue.select_organization') }}</label>
                <select class="select" name="organization_id" onchange="this.form.submit()">
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" @selected($organization->id === $org->id)>{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('queue.select_service') }}</label>
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
                    @foreach(['waiting','called','served','absent'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ __('queue.'.$st) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('queue.date') }}</label>
                <input class="input" type="date" name="date" value="{{ request('date') }}">
            </div>
            <div style="grid-column:1/-1;display:flex;gap:.5rem;">
                <button class="btn btn-soft" type="submit">{{ __('queue.filter') }}</button>
                <a class="btn btn-soft" href="{{ route('admin.appointments', ['organization_id' => $organization->id]) }}">Reset</a>
            </div>
        </form>
    </section>

    <section class="card">
        <h2 style="font-size:1.1rem;font-weight:800;margin-bottom:.7rem;">{{ __('queue.create_appointment_ticket') }}</h2>
        <form method="POST" action="{{ route('admin.appointments.store') }}" class="grid-two">
            @csrf
            <div>
                <label class="label">{{ __('queue.select_organization') }}</label>
                <select class="select" name="organization_id" required>
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" @selected($organization->id === $org->id)>{{ $org->name }}</option>
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
            <div>
                <label class="label">{{ __('queue.appointment_time') }}</label>
                <input class="input" type="datetime-local" name="appointment_at" required>
            </div>
            <div style="display:flex;align-items:end;">
                <button class="btn btn-primary" type="submit">{{ __('queue.save') }}</button>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('queue.ticket') }}</th>
                    <th>{{ __('queue.service') }}</th>
                    <th>{{ __('queue.appointment_time') }}</th>
                    <th>{{ __('queue.status') }}</th>
                    <th>{{ __('queue.actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($appointments as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->ticket_number }}</strong><br>
                            <small>{{ $item->public_code }}</small>
                        </td>
                        <td>{{ $item->service?->name }}</td>
                        <td>{{ $item->appointment_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ __('queue.'.$item->status) }}</td>
                        <td>
                            <details>
                                <summary>{{ __('queue.edit') }}</summary>
                                <form method="POST" action="{{ route('admin.appointments.update', $item) }}" style="display:grid;gap:.45rem;min-width:300px;margin-top:.4rem;">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="organization_id" value="{{ $organization->id }}">
                                    <select class="select" name="service_id" required>
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}" @selected($item->service_id === $service->id)>{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                    <input class="input" type="datetime-local" name="appointment_at" value="{{ $item->appointment_at?->format('Y-m-d\\TH:i') }}" required>
                                    <select class="select" name="status" required>
                                        @foreach(['waiting','called','served','absent'] as $st)
                                            <option value="{{ $st }}" @selected($item->status === $st)>{{ __('queue.'.$st) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-primary" type="submit">{{ __('queue.save') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.appointments.destroy', $item) }}" onsubmit="return confirm('{{ __('queue.confirm_delete') }}')" style="margin-top:.4rem;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" type="submit">{{ __('queue.delete') }}</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">{{ __('queue.no_ticket_waiting') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">{{ $appointments->links() }}</div>
    </section>
</div>
@endsection



