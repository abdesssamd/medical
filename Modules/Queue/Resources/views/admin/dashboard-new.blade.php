@extends('layouts.admin')

@section('title', 'Admin Queue')
@section('page-title', 'Administration Queue')

@section('content')
<div class="page-stack">
    {{-- Welcome --}}
    <div class="welcome-card">
        <div class="welcome-text">
            <h2>🏥 Administration Queue</h2>
            <p>{{ $organization->name ?? 'Sélectionnez une organisation' }}</p>
        </div>
        <div class="welcome-date">
            <div class="date-day">{{ now()->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY') }}</div>
            <div class="date-time">{{ now()->format('H:i') }}</div>
        </div>
    </div>

    {{-- Organisation selector --}}
    <section class="card">
        <form method="GET" class="form-row">
            <div>
                <label class="label">Organisation</label>
                <select class="select" name="organization_id" onchange="this.form.submit()">
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" @selected(($organization->id ?? null) === $org->id)>{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit">OK</button>
        </form>
    </section>

    @if($organization)
    {{-- Stats --}}
    <div class="grid-stats">
        <div class="card stat-card stat-blue">
            <div class="stat-icon">🎫</div>
            <div class="stat-info">
                <div class="stat-label">Total aujourd'hui</div>
                <div class="stat-value">{{ $stats['today_total'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card stat-card stat-orange">
            <div class="stat-icon">⏳</div>
            <div class="stat-info">
                <div class="stat-label">En attente</div>
                <div class="stat-value">{{ $stats['today_waiting'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card stat-card stat-green">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <div class="stat-label">Servis</div>
                <div class="stat-value">{{ $stats['today_served'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card stat-card stat-red">
            <div class="stat-icon">❌</div>
            <div class="stat-info">
                <div class="stat-label">Absents</div>
                <div class="stat-value">{{ $stats['today_absent'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card stat-card stat-purple">
            <div class="stat-icon">⏱️</div>
            <div class="stat-info">
                <div class="stat-label">Attente moyenne</div>
                <div class="stat-value">{{ $stats['avg_wait_minutes'] ?? 0 }} min</div>
            </div>
        </div>
    </div>

    {{-- Stats par service --}}
    @if(isset($serviceStats) && $serviceStats->count() > 0)
    <section class="card">
        <h3 class="card-title">📊 Statistiques par service</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Total</th>
                        <th>Servis</th>
                        <th>Taux absent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($serviceStats as $row)
                    <tr>
                        <td><strong>{{ $row['service_name'] }}</strong></td>
                        <td>{{ $row['total'] }}</td>
                        <td>{{ $row['served'] }}</td>
                        <td>{{ $row['absent_rate'] }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif

    {{-- Tickets récents --}}
    @if(isset($recentTickets) && $recentTickets->count() > 0)
    <section class="card">
        <h3 class="card-title">🎫 Tickets récents</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Service</th>
                        <th>Statut</th>
                        <th>Guichet</th>
                        <th>Agent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentTickets as $ticket)
                    <tr>
                        <td><code>{{ $ticket->ticket_number }}</code></td>
                        <td>{{ $ticket->service?->name ?? '-' }}</td>
                        <td>
                            <span class="badge badge-{{ $ticket->status === 'served' ? 'success' : ($ticket->status === 'waiting' ? 'warning' : ($ticket->status === 'absent' ? 'danger' : 'info')) }}">
                                {{ $ticket->status }}
                            </span>
                        </td>
                        <td>{{ $ticket->counter?->name ?? '-' }}</td>
                        <td>{{ $ticket->agent?->name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif

    {{-- Quick Actions --}}
    <section class="card">
        <h3 class="card-title">⚡ Actions rapides</h3>
        <div class="quick-cards">
            <a href="{{ route('admin.statistics', ['organization_id' => $organization->id]) }}" class="quick-card quick-blue">
                <span class="quick-icon">📊</span>
                <span class="quick-label">Statistiques</span>
                <span class="quick-desc">Rapports détaillés</span>
            </a>
            <a href="{{ route('admin.history', ['organization_id' => $organization->id]) }}" class="quick-card quick-green">
                <span class="quick-icon">📜</span>
                <span class="quick-label">Historique</span>
                <span class="quick-desc">Tous les tickets</span>
            </a>
            <a href="{{ route('admin.users') }}" class="quick-card quick-orange">
                <span class="quick-icon">👥</span>
                <span class="quick-label">Utilisateurs</span>
                <span class="quick-desc">Gestion des comptes</span>
            </a>
            <a href="{{ route('admin.counters') }}" class="quick-card quick-purple">
                <span class="quick-icon">🏢</span>
                <span class="quick-label">Guichets</span>
                <span class="quick-desc">Configuration</span>
            </a>
            <a href="{{ route('admin.kiosks') }}" class="quick-card quick-blue">
                <span class="quick-icon">🖥️</span>
                <span class="quick-label">Bornes</span>
                <span class="quick-desc">Gestion des kiosques</span>
            </a>
            <a href="{{ route('admin.screens') }}" class="quick-card quick-green">
                <span class="quick-icon">📺</span>
                <span class="quick-label">Écrans TV</span>
                <span class="quick-desc">Affichage public</span>
            </a>
            <a href="{{ route('admin.settings.questionnaires') }}" class="quick-card quick-orange">
                <span class="quick-icon">📝</span>
                <span class="quick-label">Questionnaires</span>
                <span class="quick-desc">Administration des paramètres</span>
            </a>
        </div>
    </section>
    @endif
</div>
@endsection
