@extends('layouts.app')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('content')
<div class="page-stack">
    {{-- Welcome Section --}}
    <div class="welcome-card">
        <div class="welcome-text">
            <h2>Bonjour, {{ auth()->user()->name }} 👋</h2>
            <p>Voici un résumé de votre activité aujourd'hui</p>
        </div>
        <div class="welcome-date">
            <div class="date-day">{{ now()->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY') }}</div>
            <div class="date-time">{{ now()->format('H:i') }}</div>
        </div>
    </div>

    {{-- Main Stats --}}
    <div class="grid-stats">
        <div class="card stat-card stat-blue">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <div class="stat-label">Patients</div>
                <div class="stat-value">{{ \App\Models\Patient::count() }}</div>
            </div>
        </div>
        <div class="card stat-card stat-green">
            <div class="stat-icon">📅</div>
            <div class="stat-info">
                <div class="stat-label">RDV aujourd'hui</div>
                <div class="stat-value">{{ \Modules\Appointment\Models\Appointment::whereDate('appointment_date', today())->count() }}</div>
            </div>
        </div>
        <div class="card stat-card stat-orange">
            <div class="stat-icon">🎫</div>
            <div class="stat-info">
                <div class="stat-label">Tickets en attente</div>
                <div class="stat-value">{{ \Modules\Queue\Models\Ticket::whereDate('ticket_date', today())->where('status', 'waiting')->count() }}</div>
            </div>
        </div>
        <div class="card stat-card stat-purple">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <div class="stat-label">Revenu du mois</div>
                <div class="stat-value">{{ number_format(\Modules\Billing\Models\Payment::whereMonth('payment_date', now()->month)->sum('amount'), 0) }} MAD</div>
            </div>
        </div>
    </div>

    {{-- Quick Access --}}
    <div class="quick-access-grid">
        <h3 class="section-title">🚀 Accès rapide</h3>
        <div class="quick-cards">
            <a href="{{ route('scheduling.dashboard') }}" class="quick-card quick-blue">
                <span class="quick-icon">📅</span>
                <span class="quick-label">Planification</span>
                <span class="quick-desc">Planning & disponibilités</span>
            </a>
            <a href="{{ route('clinical.patients') }}" class="quick-card quick-green">
                <span class="quick-icon">👥</span>
                <span class="quick-label">Patients</span>
                <span class="quick-desc">Dossiers cliniques</span>
            </a>
            <a href="{{ route('billing.dashboard') }}" class="quick-card quick-orange">
                <span class="quick-icon">💰</span>
                <span class="quick-label">Facturation</span>
                <span class="quick-desc">Paiements & assurances</span>
            </a>
            <a href="{{ route('admin.dashboard') }}" class="quick-card quick-purple">
                <span class="quick-icon">🏥</span>
                <span class="quick-label">Queue</span>
                <span class="quick-desc">File d'attente</span>
            </a>
        </div>
    </div>

    {{-- Today's Appointments --}}
    <div class="dashboard-grid">
        <div class="card">
            <h3 class="card-title">📅 Rendez-vous du jour</h3>
            @php
                $todayAppointments = \Modules\Appointment\Models\Appointment::whereDate('appointment_date', today())
                    ->with(['patient', 'professional', 'appointmentType'])
                    ->orderBy('start_time')
                    ->limit(10)
                    ->get();
            @endphp
            @if($todayAppointments->isEmpty())
                <div class="empty-state">
                    <p>Aucun rendez-vous aujourd'hui</p>
                </div>
            @else
                <div class="appointment-list">
                    @foreach($todayAppointments as $apt)
                    <div class="appointment-item status-{{ $apt->status }}">
                        <div class="apt-time">{{ \Carbon\Carbon::parse($apt->start_time)->format('H:i') }}</div>
                        <div class="apt-details">
                            <div class="apt-patient">{{ $apt->patient?->full_name ?? $apt->patient_name ?? '-' }}</div>
                            <div class="apt-meta">
                                <span class="badge badge-primary">{{ $apt->professional?->name ?? '-' }}</span>
                                @if($apt->appointmentType)
                                    <span class="badge badge-secondary">{{ $apt->appointmentType->name }}</span>
                                @endif
                                <span class="badge badge-{{ $apt->status === 'consulted' ? 'success' : ($apt->status === 'cancelled' ? 'danger' : 'warning') }}">
                                    {{ $apt->status }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card">
            <h3 class="card-title">🎫 Queue en temps réel</h3>
            @php
                $todayTickets = \Modules\Queue\Models\Ticket::whereDate('ticket_date', today())
                    ->with(['service', 'counter', 'agent'])
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();
            @endphp
            @if($todayTickets->isEmpty())
                <div class="empty-state">
                    <p>Aucun ticket aujourd'hui</p>
                </div>
            @else
                <div class="ticket-list">
                    @foreach($todayTickets as $ticket)
                    <div class="ticket-item">
                        <div class="ticket-number">{{ $ticket->ticket_number }}</div>
                        <div class="ticket-info">
                            <div class="ticket-service">{{ $ticket->service?->name ?? '-' }}</div>
                            <div class="ticket-meta">
                                <span class="badge badge-{{ $ticket->status === 'served' ? 'success' : ($ticket->status === 'waiting' ? 'warning' : 'info') }}">
                                    {{ $ticket->status }}
                                </span>
                                @if($ticket->counter)
                                    <span class="badge badge-secondary">{{ $ticket->counter->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card">
            <h3 class="card-title">💰 Derniers paiements</h3>
            @php
                $recentPayments = \Modules\Billing\Models\Payment::with(['invoice', 'patient'])
                    ->orderByDesc('created_at')
                    ->limit(8)
                    ->get();
            @endphp
            @if($recentPayments->isEmpty())
                <div class="empty-state">
                    <p>Aucun paiement enregistré</p>
                </div>
            @else
                <div class="payment-list">
                    @foreach($recentPayments as $payment)
                    <div class="payment-item">
                        <div class="payment-info">
                            <div class="payment-patient">{{ $payment->patient?->full_name ?? '-' }}</div>
                            <div class="payment-meta">
                                <span>{{ $payment->payment_number }}</span>
                                <span>{{ ucfirst($payment->method) }}</span>
                            </div>
                        </div>
                        <div class="payment-amount">{{ number_format($payment->amount, 2) }} MAD</div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card">
            <h3 class="card-title">⚠️ Alertes</h3>
            @php
                $alerts = \Modules\Queue\Models\SupervisorAlert::where('is_resolved', false)
                    ->with(['ticket'])
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get();
            @endphp
            @if($alerts->isEmpty())
                <div class="empty-state">
                    <p>✅ Aucune alerte active</p>
                </div>
            @else
                <div class="alert-list">
                    @foreach($alerts as $alert)
                    <div class="alert-item alert-item-warning">
                        <span class="alert-icon">⚠️</span>
                        <div class="alert-text">
                            <div>{{ $alert->message }}</div>
                            <div class="alert-time">{{ $alert->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@push('head')
<style>
    .welcome-card {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: white;
        border-radius: var(--radius-lg);
        padding: var(--spacing-xl);
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--spacing-xl);
    }

    .welcome-text h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: var(--spacing-xs);
    }

    .welcome-text p {
        color: #94a3b8;
        font-size: 0.9rem;
    }

    .welcome-date {
        text-align: right;
    }

    .date-day {
        font-size: 0.9rem;
        color: #cbd5e1;
        margin-bottom: var(--spacing-xs);
    }

    .date-time {
        font-size: 2rem;
        font-weight: 700;
        font-family: var(--font-mono);
    }

    .stat-card {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
    }

    .stat-icon {
        font-size: 2.5rem;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-lg);
        background: var(--color-gray-100);
    }

    .stat-info .stat-label {
        font-size: 0.8rem;
        color: var(--color-gray-500);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .stat-info .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
    }

    .quick-access-grid {
        margin-bottom: var(--spacing-xl);
    }

    .quick-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-md);
    }

    .quick-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: var(--spacing-lg);
        border-radius: var(--radius-lg);
        text-decoration: none;
        transition: all var(--transition-fast);
        border: 2px solid transparent;
    }

    .quick-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .quick-blue {
        background: var(--color-primary-light);
        color: #1e40af;
        border-color: #bfdbfe;
    }

    .quick-green {
        background: var(--color-success-light);
        color: #065f46;
        border-color: #a7f3d0;
    }

    .quick-orange {
        background: var(--color-warning-light);
        color: #92400e;
        border-color: #fde68a;
    }

    .quick-purple {
        background: #ede9fe;
        color: #6d28d9;
        border-color: #c4b5fd;
    }

    .quick-icon {
        font-size: 2rem;
        margin-bottom: var(--spacing-sm);
    }

    .quick-label {
        font-weight: 600;
        font-size: 1rem;
    }

    .quick-desc {
        font-size: 0.8rem;
        opacity: 0.8;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: var(--spacing-md);
        color: var(--color-gray-900);
    }

    .card-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: var(--spacing-md);
        color: var(--color-gray-800);
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: var(--spacing-lg);
    }

    .appointment-list, .ticket-list, .payment-list, .alert-list {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-sm);
    }

    .appointment-item, .ticket-item, .payment-item {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        padding: var(--spacing-sm);
        border-radius: var(--radius-md);
        background: var(--color-gray-50);
    }

    .appointment-item.status-consulted { border-left: 3px solid var(--color-success); }
    .appointment-item.status-cancelled { border-left: 3px solid var(--color-danger); }

    .apt-time {
        font-weight: 600;
        font-family: var(--font-mono);
        min-width: 50px;
    }

    .apt-details { flex: 1; }
    .apt-patient { font-weight: 500; margin-bottom: var(--spacing-xs); }
    .apt-meta { display: flex; gap: var(--spacing-xs); flex-wrap: wrap; }

    .ticket-number {
        font-weight: 700;
        font-family: var(--font-mono);
        min-width: 80px;
    }

    .ticket-info { flex: 1; }
    .ticket-service { font-weight: 500; }
    .ticket-meta { display: flex; gap: var(--spacing-xs); margin-top: var(--spacing-xs); }

    .payment-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--spacing-sm);
        background: var(--color-gray-50);
        border-radius: var(--radius-md);
    }

    .payment-patient { font-weight: 500; }
    .payment-meta { font-size: 0.8rem; color: var(--color-gray-500); display: flex; gap: var(--spacing-sm); }
    .payment-amount { font-weight: 700; color: var(--color-success); }

    .alert-item {
        display: flex;
        gap: var(--spacing-sm);
        padding: var(--spacing-sm);
        border-radius: var(--radius-md);
    }

    .alert-item-warning { background: var(--color-warning-light); }

    .alert-text { flex: 1; }
    .alert-time { font-size: 0.75rem; color: var(--color-gray-500); margin-top: var(--spacing-xs); }

    @media (max-width: 768px) {
        .welcome-card {
            flex-direction: column;
            text-align: center;
        }
        .welcome-date { text-align: center; }
        .dashboard-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush
@endsection
