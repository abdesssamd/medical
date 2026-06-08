@extends('layouts.admin')

@section('title', 'Superviseur')
@section('page-title', 'Supervision en temps réel')

@section('content')
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h2>📊 Supervision Live</h2>
            <p class="muted">Tableau de bord superviseur - {{ $organization->name }}</p>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('admin.dashboard') }}">← Admin</a>
            <a class="btn btn-soft" href="{{ route('admin.statistics', ['organization_id' => $organization->id]) }}">📈 Statistiques</a>
        </div>
    </section>

    @if($organization)
    <section class="card">
        <form method="GET" class="form-row">
            <div>
                <label class="label">Organisation</label>
                <select class="select" name="organization_id" onchange="this.form.submit()">
                    @foreach(\Modules\Queue\Models\Organization::orderBy('name')->get() as $org)
                        <option value="{{ $org->id }}" @selected($organization->id === $org->id)>{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit">OK</button>
        </form>
    </section>

    {{-- SLA & Stats --}}
    <div class="grid-stats">
        <div class="card stat-card stat-blue">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
                <div class="stat-label">Taux SLA</div>
                <div class="stat-value">{{ $initial['sla_rate'] ?? 0 }}%</div>
            </div>
        </div>
        <div class="card stat-card stat-green">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <div class="stat-label">Servis aujourd'hui</div>
                <div class="stat-value">{{ $initial['served_today'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card stat-card stat-orange">
            <div class="stat-icon">⏳</div>
            <div class="stat-info">
                <div class="stat-label">En attente</div>
                <div class="stat-value">{{ $initial['waiting_count'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card stat-card stat-red">
            <div class="stat-icon">⚠️</div>
            <div class="stat-info">
                <div class="stat-label">Alertes actives</div>
                <div class="stat-value">{{ $initial['active_alerts'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    {{-- Charge des guichets --}}
    @if(isset($initial['counter_load']) && count($initial['counter_load']) > 0)
    <section class="card">
        <h3 class="card-title">🏢 Charge des guichets</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Guichet</th>
                        <th>Agent</th>
                        <th>Tickets servis</th>
                        <th>Temps moyen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($initial['counter_load'] as $counter)
                    <tr>
                        <td><strong>{{ $counter['counter_name'] ?? '-' }}</strong></td>
                        <td>{{ $counter['agent_name'] ?? '-' }}</td>
                        <td>{{ $counter['served_count'] ?? 0 }}</td>
                        <td>{{ $counter['avg_minutes'] ?? 0 }} min</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif

    {{-- Goulots --}}
    @if(isset($initial['bottlenecks']) && count($initial['bottlenecks']) > 0)
    <section class="card">
        <h3 class="card-title">⚠️ Goulots d'étranglement</h3>
        @foreach($initial['bottlenecks'] as $bottleneck)
        <div class="alert-item alert-item-warning">
            <span class="alert-icon">🔴</span>
            <div class="alert-text">
                <strong>{{ $bottleneck['service_name'] ?? 'Service' }}</strong>
                <div>{{ $bottleneck['waiting_count'] ?? 0 }} tickets en attente - temps moyen: {{ $bottleneck['avg_wait'] ?? 0 }} min</div>
            </div>
        </div>
        @endforeach
    </section>
    @endif
    @endif
</div>
@endsection
