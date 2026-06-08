@extends('layouts.admin')

@section('title', __('queue.statistics'))

@section('content')
<div class="page-stack">
    <section class="card">
        <h1 class="page-title">{{ __('queue.statistics') }}</h1>
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
                <label class="label">{{ __('queue.from') }}</label>
                <input class="input" type="date" name="from" value="{{ $from->toDateString() }}">
            </div>
            <div>
                <label class="label">{{ __('queue.to') }}</label>
                <input class="input" type="date" name="to" value="{{ $to->toDateString() }}">
            </div>
            <button class="btn btn-primary" type="submit">{{ __('queue.filter') }}</button>
        </form>
    </section>

    <section class="grid-stats">
        <div class="card"><div class="label">Total</div><div class="stat-number">{{ $global['total'] }}</div></div>
        <div class="card"><div class="label">{{ __('queue.today_served') }}</div><div class="stat-number">{{ $global['served'] }}</div></div>
        <div class="card"><div class="label">{{ __('queue.today_absent') }}</div><div class="stat-number">{{ $global['absent'] }}</div></div>
        <div class="card"><div class="label">Served %</div><div class="stat-number">{{ $global['served_rate'] }}%</div></div>
        <div class="card"><div class="label">Absent %</div><div class="stat-number">{{ $global['absent_rate'] }}%</div></div>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>{{ __('queue.service') }}</th><th>Total</th><th>{{ __('queue.today_served') }}</th><th>{{ __('queue.today_absent') }}</th><th>{{ __('queue.avg_wait') }}</th></tr></thead>
                <tbody>
                @foreach($byService as $row)
                    <tr>
                        <td>{{ $row['service'] }}</td>
                        <td>{{ $row['total'] }}</td>
                        <td>{{ $row['served'] }}</td>
                        <td>{{ $row['absent'] }}</td>
                        <td>{{ $row['avg_wait'] }} {{ __('queue.minutes') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection



