@extends('layouts.admin')

@section('title', 'Tableau de bord')
@section('page_pretitle', 'Accueil')
@section('page_title', 'Tableau de bord')

@section('content')
<div style="display:grid; gap:24px;">
    {{-- Welcome Section --}}
    <div class="content-card" style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color:white; border:none; display:flex; justify-content:space-between; align-items:center; padding:28px 32px;">
        <div>
            <h2 style="font-size:22px; font-weight:700; margin-bottom:4px;">Bonjour, {{ auth()->user()->name }}</h2>
            <p style="color:#94a3b8; font-size:14px;">Voici un résumé de votre activité aujourd'hui</p>
        </div>
        <div style="text-align:right;">
            <div style="color:#cbd5e1; font-size:13px;">{{ now()->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY') }}</div>
            <div style="font-size:36px; font-weight:700; font-family:'Inter', monospace; line-height:1.2;">{{ now()->format('H:i') }}</div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="stats-grid">
        <div class="stat-card-modern">
            <div class="stat-icon stat-icon-blue"><i class="ti ti-users"></i></div>
            <div class="stat-label">Patients</div>
            <div class="stat-value">{{ \App\Models\Patient::count() }}</div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-icon stat-icon-green"><i class="ti ti-calendar-stats"></i></div>
            <div class="stat-label">RDV aujourd'hui</div>
            <div class="stat-value">{{ class_exists(\Modules\Appointment\Models\Appointment::class) ? \Modules\Appointment\Models\Appointment::whereDate('appointment_date', today())->count() : 0 }}</div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-icon stat-icon-yellow"><i class="ti ti-ticket"></i></div>
            <div class="stat-label">Tickets en attente</div>
            <div class="stat-value">{{ class_exists(\Modules\Queue\Models\Ticket::class) ? \Modules\Queue\Models\Ticket::whereDate('ticket_date', today())->where('status', 'waiting')->count() : 0 }}</div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-icon stat-icon-purple"><i class="ti ti-report-money"></i></div>
            <div class="stat-label">Revenu du mois</div>
            <div class="stat-value">{{ class_exists(\Modules\Billing\Models\Payment::class) ? number_format(\Modules\Billing\Models\Payment::whereMonth('payment_date', now()->month)->sum('amount'), 0) : 0 }} MAD</div>
        </div>
    </div>

    {{-- Quick Access --}}
    <div class="content-card">
        <div class="card-header-custom">
            <h3><i class="ti ti-rocket" style="margin-right:8px;"></i>Accès rapide</h3>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
            @php
                $quickLinks = [];
                if (auth()->user()?->hasAnyRole(['super_admin', 'admin', 'professional', 'doctor', 'medecin', 'secretary', 'secretaire', 'assistant'])) {
                    $quickLinks = [
                        ['label' => 'Planification', 'icon' => 'ti ti-calendar-time', 'desc' => 'Planning & disponibilités', 'route' => 'scheduling.dashboard', 'color' => '#dbeafe', 'iconColor' => '#1d4ed8'],
                        ['label' => 'Patients', 'icon' => 'ti ti-users-group', 'desc' => 'Dossiers cliniques', 'route' => 'clinical.patients', 'color' => '#d1fae5', 'iconColor' => '#065f46'],
                        ['label' => 'Facturation', 'icon' => 'ti ti-cash', 'desc' => 'Paiements & assurances', 'route' => 'billing.dashboard', 'color' => '#fef3c7', 'iconColor' => '#92400e'],
                        ['label' => 'File d\'attente', 'icon' => 'ti ti-list-numbers', 'desc' => 'Gestion des tickets', 'route' => 'admin.dashboard', 'color' => '#ede9fe', 'iconColor' => '#6d28d9'],
                    ];
                }
            @endphp
            @foreach($quickLinks as $link)
                @if(Route::has($link['route']))
                    <a href="{{ route($link['route']) }}" style="display:flex; flex-direction:column; align-items:center; gap:8px; padding:20px 16px; border-radius:16px; text-decoration:none; background:{{ $link['color'] }}; transition:all 0.2s; border:2px solid transparent;" onmouseover="this.style.borderColor='{{ $link['iconColor'] }}'; this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='transparent'; this.style.transform='none'">
                        <i class="{{ $link['icon'] }}" style="font-size:28px; color:{{ $link['iconColor'] }};"></i>
                        <span style="font-weight:700; font-size:14px; color:#0f172a;">{{ $link['label'] }}</span>
                        <span style="font-size:12px; color:#475569;">{{ $link['desc'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Content Grid --}}
    <div class="content-grid">
        {{-- Today's Appointments --}}
        <div class="content-card">
            <div class="card-header-custom">
                <h3><i class="ti ti-calendar-event" style="margin-right:8px;"></i>Rendez-vous du jour</h3>
            </div>
            @php
                $todayAppointments = class_exists(\Modules\Appointment\Models\Appointment::class)
                    ? \Modules\Appointment\Models\Appointment::whereDate('appointment_date', today())
                        ->with(['patient', 'professional', 'appointmentType'])
                        ->orderBy('start_time')
                        ->limit(10)
                        ->get()
                    : collect();
            @endphp
            @if($todayAppointments->isEmpty())
                <div class="empty-state-modern">
                    <i class="ti ti-calendar-off"></i>
                    <p>Aucun rendez-vous aujourd'hui</p>
                </div>
            @else
                <div style="display:flex; flex-direction:column; gap:6px;">
                    @foreach($todayAppointments as $apt)
                    <div style="display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:10px; background:#f8fafc; border-left:3px solid {{ $apt->status === 'consulted' ? '#10b981' : ($apt->status === 'cancelled' ? '#ef4444' : '#f59e0b') }};">
                        <div style="font-weight:700; font-size:14px; font-family:'Inter', monospace; min-width:48px; color:#334155;">{{ \Carbon\Carbon::parse($apt->start_time)->format('H:i') }}</div>
                        <div style="flex:1;">
                            <div style="font-weight:600; font-size:13px; margin-bottom:2px;">{{ $apt->patient?->full_name ?? $apt->patient_name ?? '-' }}</div>
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                <span class="badge-modern badge-blue">{{ $apt->professional?->name ?? '-' }}</span>
                                @if($apt->appointmentType)
                                    <span class="badge-modern badge-gray">{{ $apt->appointmentType->name }}</span>
                                @endif
                                <span class="badge-modern {{ $apt->status === 'consulted' ? 'badge-green' : ($apt->status === 'cancelled' ? 'badge-red' : 'badge-yellow') }}">
                                    {{ $apt->status }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Queue in Real Time --}}
        <div class="content-card">
            <div class="card-header-custom">
                <h3><i class="ti ti-ticket" style="margin-right:8px;"></i>Queue en temps réel</h3>
            </div>
            @php
                $todayTickets = class_exists(\Modules\Queue\Models\Ticket::class)
                    ? \Modules\Queue\Models\Ticket::whereDate('ticket_date', today())
                        ->with(['service', 'counter', 'agent'])
                        ->orderByDesc('created_at')
                        ->limit(10)
                        ->get()
                    : collect();
            @endphp
            @if($todayTickets->isEmpty())
                <div class="empty-state-modern">
                    <i class="ti ti-ticket-off"></i>
                    <p>Aucun ticket aujourd'hui</p>
                </div>
            @else
                <div style="display:flex; flex-direction:column; gap:6px;">
                    @foreach($todayTickets as $ticket)
                    <div style="display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:10px; background:#f8fafc;">
                        <div style="font-weight:800; font-size:16px; font-family:'Inter', monospace; min-width:72px; color:#1d4ed8;">{{ $ticket->ticket_number }}</div>
                        <div style="flex:1;">
                            <div style="font-weight:600; font-size:13px;">{{ $ticket->service?->name ?? '-' }}</div>
                            <div style="display:flex; gap:6px; margin-top:4px;">
                                <span class="badge-modern {{ $ticket->status === 'served' ? 'badge-green' : ($ticket->status === 'waiting' ? 'badge-yellow' : 'badge-blue') }}">
                                    {{ $ticket->status }}
                                </span>
                                @if($ticket->counter)
                                    <span class="badge-modern badge-gray">{{ $ticket->counter->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Recent Payments --}}
        <div class="content-card">
            <div class="card-header-custom">
                <h3><i class="ti ti-cash" style="margin-right:8px;"></i>Derniers paiements</h3>
            </div>
            @php
                $recentPayments = class_exists(\Modules\Billing\Models\Payment::class)
                    ? \Modules\Billing\Models\Payment::with(['invoice', 'patient'])
                        ->orderByDesc('created_at')
                        ->limit(8)
                        ->get()
                    : collect();
            @endphp
            @if($recentPayments->isEmpty())
                <div class="empty-state-modern">
                    <i class="ti ti-credit-card-off"></i>
                    <p>Aucun paiement enregistré</p>
                </div>
            @else
                <div style="display:flex; flex-direction:column; gap:6px;">
                    @foreach($recentPayments as $payment)
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:10px; background:#f8fafc;">
                        <div>
                            <div style="font-weight:600; font-size:13px;">{{ $payment->patient?->full_name ?? '-' }}</div>
                            <div style="font-size:11px; color:#64748b; display:flex; gap:8px; margin-top:2px;">
                                <span>{{ $payment->payment_number }}</span>
                                <span>{{ ucfirst($payment->method) }}</span>
                            </div>
                        </div>
                        <div style="font-weight:700; font-size:16px; color:#065f46;">{{ number_format($payment->amount, 2) }} MAD</div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Alerts --}}
        <div class="content-card">
            <div class="card-header-custom">
                <h3><i class="ti ti-bell" style="margin-right:8px;"></i>Alertes</h3>
            </div>
            @php
                $alerts = class_exists(\Modules\Queue\Models\SupervisorAlert::class)
                    ? \Modules\Queue\Models\SupervisorAlert::where('is_resolved', false)
                        ->with(['ticket'])
                        ->orderByDesc('created_at')
                        ->limit(5)
                        ->get()
                    : collect();
            @endphp
            @if($alerts->isEmpty())
                <div class="empty-state-modern">
                    <i class="ti ti-shield-check" style="color:#10b981; opacity:1;"></i>
                    <p style="color:#065f46;">Aucune alerte active</p>
                </div>
            @else
                <div style="display:flex; flex-direction:column; gap:6px;">
                    @foreach($alerts as $alert)
                    <div style="display:flex; align-items:flex-start; gap:10px; padding:10px 12px; border-radius:10px; background:#fef3c7;">
                        <i class="ti ti-alert-triangle" style="color:#d97706; font-size:18px; flex-shrink:0; margin-top:1px;"></i>
                        <div>
                            <div style="font-size:13px; color:#92400e;">{{ $alert->message }}</div>
                            <div style="font-size:11px; color:#a16207; margin-top:2px;">{{ $alert->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
