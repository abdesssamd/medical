@extends('queue::layouts.app')

@section('title', 'Planification')

@section('page')
<div class="page-stack">
    {{-- Header --}}
    <section class="card toolbar">
        <div>
            <h1 class="page-title">📅 Planification</h1>
            <div class="muted">Gestion des rendez-vous et disponibilités</div>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('scheduling.appointment-types') }}">🏷️ Types d'actes</a>
            <a class="btn btn-soft" href="{{ route('scheduling.availability-blocks') }}">🕐 Disponibilités</a>
            <a class="btn btn-soft" href="{{ route('scheduling.multi-specialty') }}">🔄 Multi-spécialités</a>
        </div>
    </section>

    {{-- Sélection praticien + date --}}
    <section class="card">
        <form method="GET" class="form-row">
            <div>
                <label class="label">Praticien</label>
                <select class="select" name="practitioner_id" onchange="this.form.submit()">
                    @foreach($practitioners as $practitioner)
                        <option value="{{ $practitioner->id }}" @selected($selectedPractitionerId === $practitioner->id)>
                            {{ $practitioner->display_name ?? $practitioner->name }}
                            @if($practitioner->specialties->isNotEmpty())
                                ({{ $practitioner->specialties->pluck('code')->join(', ') }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Date</label>
                <input type="date" class="input" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()">
            </div>
            <button class="btn btn-primary" type="submit">OK</button>
        </form>
    </section>

    @if($selectedPractitioner)
    {{-- Stats du jour --}}
    <section class="grid-stats">
        <div class="card">
            <div class="label">Rendez-vous aujourd'hui</div>
            <div class="stat-number">{{ $appointments->count() }}</div>
        </div>
        <div class="card">
            <div class="label">Créneaux disponibles</div>
            <div class="stat-number">{{ $availability['available'] ? count($availability['slots']) : 0 }}</div>
        </div>
        <div class="card">
            <div class="label">Consultés</div>
            <div class="stat-number">{{ $appointments->where('status', 'consulted')->count() }}</div>
        </div>
        <div class="card">
            <div class="label">Taux d'occupation</div>
            <div class="stat-number">
                @if($availability['max_patients_per_day'])
                    {{ round(($availability['booked_count'] / $availability['max_patients_per_day']) * 100) }}%
                @else
                    N/A
                @endif
            </div>
        </div>
    </section>

    <div class="grid-2-col">
        {{-- Planning du jour --}}
        <section class="card">
            <h2 class="section-title">📋 Planning du {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</h2>
            
            @if($appointments->isEmpty())
                <div class="empty-state">
                    <p>Aucun rendez-vous pour cette date.</p>
                </div>
            @else
                <div class="timeline">
                    @foreach($appointments as $apt)
                    <div class="timeline-item status-{{ $apt->status }}">
                        <div class="timeline-time">
                            {{ \Carbon\Carbon::parse($apt->start_time)->format('H:i') }}
                            <br>
                            <small>{{ \Carbon\Carbon::parse($apt->end_time)->format('H:i') }}</small>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-patient">
                                {{ $apt->patient?->full_name ?? $apt->patient_name ?? 'Patient inconnu' }}
                            </div>
                            <div class="timeline-details">
                                @if($apt->appointmentType)
                                    <span class="badge badge-primary">{{ $apt->appointmentType->name }}</span>
                                @endif
                                @if($apt->room)
                                    <span class="badge badge-secondary">🏥 {{ $apt->room->name }}</span>
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
        </section>

        {{-- Créneaux disponibles --}}
        <section class="card">
            <h2 class="section-title">🕐 Créneaux disponibles</h2>
            
            @if(!$availability['available'])
                <div class="empty-state">
                    <p>⚠️ {{ $availability['reason'] === 'quota_reached' ? 'Quota journalier atteint.' : 'Aucun créneau disponible.' }}</p>
                </div>
            @else
                <div class="slots-grid">
                    @foreach(array_slice($availability['slots'], 0, 20) as $slot)
                    <div class="slot-item">
                        <div class="slot-time">{{ \Carbon\Carbon::parse($slot['start_time'])->format('H:i') }}</div>
                        <div class="slot-room">{{ $slot['room_id'] ? '🏥 Salle ' . $slot['room_id'] : '-' }}</div>
                    </div>
                    @endforeach
                </div>
                @if(count($availability['slots']) > 20)
                    <div class="text-center muted" style="margin-top: 1rem;">
                        ... et {{ count($availability['slots']) - 20 }} autres créneaux
                    </div>
                @endif
            @endif
        </section>
    </div>

    {{-- Vue semaine --}}
    <section class="card">
        <h2 class="section-title">📆 Vue de la semaine</h2>
        <div class="week-grid">
            @php
                $weekDays = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
            @endphp
            @for($i = 0; $i < 7; $i++)
                @php
                    $dayDate = \Carbon\Carbon::parse($selectedDate)->startOfWeek()->addDays($i);
                    $dayStr = $dayDate->format('Y-m-d');
                    $dayAppointments = $weekAppointments[$dayStr] ?? collect();
                @endphp
                <div class="week-day {{ $dayStr === $selectedDate ? 'active' : '' }}">
                    <div class="week-day-header">
                        <strong>{{ $weekDays[$i] }}</strong>
                        <span class="muted">{{ $dayDate->format('d/m') }}</span>
                        <span class="badge badge-{{ $dayAppointments->count() > 0 ? 'primary' : 'muted' }}">
                            {{ $dayAppointments->count() }}
                        </span>
                    </div>
                    @if($dayAppointments->count() > 0)
                        <div class="week-day-appointments">
                            @foreach($dayAppointments as $apt)
                                <div class="mini-appointment">
                                    <span class="mini-time">{{ \Carbon\Carbon::parse($apt->start_time)->format('H:i') }}</span>
                                    <span class="mini-name">{{ Str::limit($apt->patient?->full_name ?? $apt->patient_name, 15) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endfor
        </div>
    </section>
    @endif
</div>

@push('head')
<style>
    .grid-2-col {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 1rem;
    }

    .timeline {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .timeline-item {
        display: flex;
        gap: 1rem;
        padding: 0.75rem;
        border-radius: 0.5rem;
        background: var(--card-bg, #fff);
        border-left: 4px solid #3b82f6;
    }

    .timeline-item.status-consulted { border-left-color: #10b981; }
    .timeline-item.status-cancelled { border-left-color: #ef4444; }
    .timeline-item.status-no_show { border-left-color: #f59e0b; }

    .timeline-time {
        min-width: 60px;
        font-weight: 600;
        color: #374151;
    }

    .timeline-time small {
        color: #6b7280;
        font-size: 0.8rem;
    }

    .timeline-content {
        flex: 1;
    }

    .timeline-patient {
        font-weight: 500;
        margin-bottom: 0.25rem;
    }

    .timeline-details {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .badge {
        display: inline-block;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .badge-primary { background: #dbeafe; color: #1e40af; }
    .badge-secondary { background: #e5e7eb; color: #374151; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-muted { background: #f3f4f6; color: #6b7280; }

    .slots-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 0.5rem;
    }

    .slot-item {
        padding: 0.75rem;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 0.5rem;
        text-align: center;
    }

    .slot-time {
        font-weight: 600;
        color: #166534;
    }

    .slot-room {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }

    .week-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0.5rem;
    }

    .week-day {
        padding: 0.75rem;
        border-radius: 0.5rem;
        background: #f9fafb;
        min-height: 150px;
    }

    .week-day.active {
        background: #dbeafe;
        border: 2px solid #3b82f6;
    }

    .week-day-header {
        text-align: center;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
    }

    .week-day-header strong {
        display: block;
    }

    .week-day-appointments {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .mini-appointment {
        display: flex;
        justify-content: space-between;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        background: white;
        border-radius: 0.25rem;
    }

    .mini-time {
        color: #3b82f6;
        font-weight: 500;
    }

    .mini-name {
        color: #374151;
    }

    .empty-state {
        padding: 2rem;
        text-align: center;
        color: #6b7280;
        background: #f9fafb;
        border-radius: 0.5rem;
    }

    @media (max-width: 768px) {
        .week-grid {
            grid-template-columns: 1fr;
        }
        .grid-2-col {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush
@endsection
