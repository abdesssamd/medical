@extends('layouts.app')

@section('title', 'Disponibilités')
@section('page-title', 'Gestion des disponibilités')

@section('content')
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h2>🕐 Blocs de disponibilité</h2>
            <p class="muted">Configurer les plages horaires des praticiens</p>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('scheduling.dashboard') }}">← Planning</a>
        </div>
    </section>

    {{-- Filtres --}}
    <section class="card">
        <form method="GET" class="form-row">
            <div>
                <label class="label">Praticien</label>
                <select class="select" name="practitioner_id">
                    <option value="">-- Tous --</option>
                    @foreach($practitioners as $p)
                        <option value="{{ $p->id }}" @selected($selectedPractitionerId === $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Du</label>
                <input type="date" class="input" name="from_date" value="{{ $fromDate }}">
            </div>
            <div>
                <label class="label">Au</label>
                <input type="date" class="input" name="to_date" value="{{ $toDate }}">
            </div>
            <button class="btn btn-primary" type="submit" style="align-self: flex-end;">🔍 Filtrer</button>
        </form>
    </section>

    {{-- Formulaire création récurrente --}}
    <section class="card">
        <h3 class="card-title">➕ Créer des blocs récurrents</h3>
        <form method="POST" action="{{ route('scheduling.availability-blocks.store-recurring') }}">
            @csrf
            <div class="form-row">
                <div>
                    <label class="label">Praticien *</label>
                    <select class="select" name="practitioner_id" required>
                        @foreach($practitioners as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Salle</label>
                    <select class="select" name="room_id">
                        <option value="">-- Aucune --</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Type d'acte</label>
                    <select class="select" name="appointment_type_id">
                        <option value="">-- Aucun --</option>
                        @foreach($appointmentTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->duration_minutes }}min)</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div>
                    <label class="label">Heure début *</label>
                    <input type="time" class="input" name="start_time" value="09:00" required>
                </div>
                <div>
                    <label class="label">Heure fin *</label>
                    <input type="time" class="input" name="end_time" value="17:00" required>
                </div>
                <div>
                    <label class="label">Type</label>
                    <select class="select" name="type">
                        <option value="available">Disponible</option>
                        <option value="break">Pause</option>
                        <option value="formation">Formation</option>
                        <option value="absence">Absence</option>
                    </select>
                </div>
                <div>
                    <label class="label">Max patients/jour</label>
                    <input type="number" class="input" name="max_patients" placeholder="Illimité" min="1">
                </div>
            </div>
            <div class="form-row">
                <div>
                    <label class="label">Date début *</label>
                    <input type="date" class="input" name="from_date" value="{{ now()->format('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="label">Date fin *</label>
                    <input type="date" class="input" name="to_date" value="{{ now()->addMonths(3)->format('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="label">Jours exclus</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="exclude_days[]" value="0"> Dim</label>
                        <label><input type="checkbox" name="exclude_days[]" value="6"> Sam</label>
                    </div>
                </div>
                <button class="btn btn-success" type="submit" style="align-self: flex-end;">✅ Créer les blocs</button>
            </div>
        </form>
    </section>

    {{-- Liste des blocs --}}
    <section class="card">
        <h3 class="card-title">📅 Blocs configurés ({{ $blocks->count() }})</h3>
        @if($blocks->isEmpty())
            <div class="empty-state">
                <p>Aucun bloc de disponibilité configuré pour cette période.</p>
            </div>
        @else
            <div class="blocks-timeline">
                @foreach($blocks->groupBy(fn($b) => $b->date->format('Y-m-d')) as $date => $dayBlocks)
                <div class="day-group">
                    <div class="day-header">
                        <strong>{{ \Carbon\Carbon::parse($date)->locale(app()->getLocale())->isoFormat('dddd D MMMM') }}</strong>
                        <span class="badge badge-primary">{{ $dayBlocks->count() }} bloc(s)</span>
                    </div>
                    <div class="blocks-grid">
                        @foreach($dayBlocks as $block)
                        <div class="block-card block-type-{{ $block->type }}">
                            <div class="block-time">
                                {{ \Carbon\Carbon::parse($block->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($block->end_time)->format('H:i') }}
                            </div>
                            <div class="block-info">
                                @if($block->room)
                                    <span class="block-room">🏥 {{ $block->room->name }}</span>
                                @endif
                                @if($block->appointmentType)
                                    <span class="block-type-badge">{{ $block->appointmentType->name }}</span>
                                @endif
                                @if($block->max_patients)
                                    <span class="block-max">Max: {{ $block->max_patients }}</span>
                                @endif
                            </div>
                            <div class="block-status">
                                @if($block->type === 'available')
                                    <span class="badge badge-success">✅ Disponible</span>
                                @elseif($block->type === 'break')
                                    <span class="badge badge-warning">☕ Pause</span>
                                @elseif($block->type === 'formation')
                                    <span class="badge badge-info">📚 Formation</span>
                                @else
                                    <span class="badge badge-danger">❌ Absence</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </section>
</div>

@push('head')
<style>
    .checkbox-group {
        display: flex;
        gap: 1rem;
        padding: var(--spacing-sm) 0;
    }

    .blocks-timeline {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-lg);
    }

    .day-header {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        margin-bottom: var(--spacing-sm);
        padding-bottom: var(--spacing-xs);
        border-bottom: 2px solid var(--color-gray-200);
    }

    .blocks-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: var(--spacing-sm);
    }

    .block-card {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        padding: var(--spacing-sm) var(--spacing-md);
        border-radius: var(--radius-md);
        border-left: 4px solid var(--color-success);
        background: var(--color-gray-50);
    }

    .block-type-break { border-left-color: var(--color-warning); }
    .block-type-formation { border-left-color: var(--color-info); }
    .block-type-absence { border-left-color: var(--color-danger); }

    .block-time {
        font-weight: 700;
        font-family: var(--font-mono);
        min-width: 120px;
    }

    .block-info {
        flex: 1;
        display: flex;
        gap: var(--spacing-sm);
        flex-wrap: wrap;
    }

    .block-room, .block-type-badge, .block-max {
        font-size: 0.8rem;
        color: var(--color-gray-500);
    }

    @media (max-width: 768px) {
        .blocks-grid { grid-template-columns: 1fr; }
        .form-row { flex-direction: column; }
    }
</style>
@endpush
@endsection
