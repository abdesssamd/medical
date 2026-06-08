@extends('layouts.app')

@section('title', 'Plans de traitement - ' . $patient->full_name)
@section('page-title', 'Plans de traitement : ' . $patient->full_name)

@section('content')
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h2>📋 Plans de traitement</h2>
            <p class="muted">Gestion des plans de traitement multi-phases</p>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('clinical.patient.show', ['patientId' => $patient->id]) }}">← Dossier</a>
        </div>
    </section>

    {{-- Créer un plan --}}
    <section class="card">
        <h3 class="card-title">➕ Nouveau plan de traitement</h3>
        <form method="POST" action="{{ route('clinical.treatment-plans.store', ['patientId' => $patient->id]) }}">
            @csrf
            <div class="form-row">
                <div>
                    <label class="label">Nom du plan *</label>
                    <input type="text" class="input" name="name" placeholder="ex: Traitement complet 2026" required>
                </div>
                <div>
                    <label class="label">Praticien *</label>
                    <select class="select" name="practitioner_id" required>
                        @foreach($practitioners as $p)
                            <option value="{{ $p->id }}">{{ $p->display_name ?? $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex: 2;">
                    <label class="label">Objectif</label>
                    <input type="text" class="input" name="objective" placeholder="Objectif du traitement...">
                </div>
                <button class="btn btn-primary" type="submit" style="align-self: flex-end;">✅ Créer</button>
            </div>
        </form>
    </section>

    {{-- Plans existants --}}
    @foreach($treatmentPlans as $plan)
    <section class="card plan-card">
        <div class="plan-header">
            <div>
                <h3 class="plan-title">{{ $plan->name }}</h3>
                <div class="plan-meta">
                    <span>👨‍⚕️ {{ $plan->practitioner?->display_name ?? '-' }}</span>
                    <span class="badge badge-{{ $plan->status === 'in_progress' ? 'primary' : ($plan->status === 'completed' ? 'success' : 'warning') }}">
                        {{ $plan->status }}
                    </span>
                </div>
            </div>
            <div class="plan-stats">
                <div class="plan-amount">
                    <div class="amount-label">Coût total</div>
                    <div class="amount-value">{{ number_format($plan->total_estimated_cost, 2) }} MAD</div>
                </div>
                <div class="plan-amount">
                    <div class="amount-label">Payé</div>
                    <div class="amount-value text-success">{{ number_format($plan->paid_amount, 2) }} MAD</div>
                </div>
                <div class="plan-amount">
                    <div class="amount-label">Reste</div>
                    <div class="amount-value text-danger">{{ number_format($plan->remaining_amount, 2) }} MAD</div>
                </div>
            </div>
        </div>

        {{-- Progress bars --}}
        <div class="plan-progress">
            <div class="progress-section">
                <div class="progress-label">
                    <span>Paiement</span>
                    <span>{{ $plan->payment_progress }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill progress-blue" style="width: {{ $plan->payment_progress }}%"></div>
                </div>
            </div>
            <div class="progress-section">
                <div class="progress-label">
                    <span>Traitement</span>
                    <span>{{ $plan->completion_progress }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill progress-green" style="width: {{ $plan->completion_progress }}%"></div>
                </div>
            </div>
        </div>

        {{-- Phases --}}
        @if($plan->procedures->count() > 0)
        <div class="plan-phases">
            <h4 class="phases-title">📋 Actes du plan</h4>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Phase</th>
                            <th>Acte</th>
                            <th>Dent</th>
                            <th>Prix</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plan->procedures->sortBy(fn($p) => $p->phase_number * 100 + $p->order_in_phase) as $procEntry)
                        <tr>
                            <td>Phase {{ $procEntry->phase_number }}</td>
                            <td>{{ $procEntry->procedure?->name ?? '-' }}</td>
                            <td>{{ $procEntry->procedure?->tooth_number ? 'Dent '.$procEntry->procedure->tooth_number : '-' }}</td>
                            <td>{{ $procEntry->procedure ? number_format($procEntry->procedure->price, 2).' MAD' : '-' }}</td>
                            <td>
                                <span class="badge badge-{{ $procEntry->status === 'completed' ? 'success' : 'warning' }}">
                                    {{ $procEntry->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Ajouter un acte --}}
        <div class="add-procedure-form">
            <h4 class="phases-title">➕ Ajouter un acte</h4>
            <form method="POST" action="{{ route('clinical.treatment-plans.add-procedure', ['planId' => $plan->id]) }}">
                @csrf
                <div class="form-row">
                    <div>
                        <label class="label">Praticien</label>
                        <select class="select" name="practitioner_id" required>
                            @foreach($practitioners as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Spécialité</label>
                        <select class="select" name="specialty_id" required>
                            @foreach($specialties as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Code acte</label>
                        <input type="text" class="input" name="procedure_code" placeholder="D2750" required>
                    </div>
                    <div>
                        <label class="label">Nom</label>
                        <input type="text" class="input" name="name" placeholder="Couronne..." required>
                    </div>
                    <div>
                        <label class="label">Prix (MAD)</label>
                        <input type="number" step="0.01" class="input" name="price" required>
                    </div>
                    <div>
                        <label class="label">Phase</label>
                        <input type="number" class="input" name="phase_number" value="1" min="1" required>
                    </div>
                    <div>
                        <label class="label">Ordre</label>
                        <input type="number" class="input" name="order_in_phase" value="1" min="1" required>
                    </div>
                    <button class="btn btn-success" type="submit" style="align-self: flex-end;">➕ Ajouter</button>
                </div>
            </form>
        </div>
    </section>
    @endforeach

    @if($treatmentPlans->isEmpty())
    <div class="empty-state">
        <p>Aucun plan de traitement pour ce patient.</p>
    </div>
    @endif
</div>

@push('head')
<style>
    .plan-card {
        margin-bottom: var(--spacing-lg);
    }

    .plan-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: var(--spacing-md);
    }

    .plan-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0 0 var(--spacing-xs);
    }

    .plan-meta {
        display: flex;
        gap: var(--spacing-md);
        align-items: center;
        font-size: 0.85rem;
        color: var(--color-gray-500);
    }

    .plan-stats {
        display: flex;
        gap: var(--spacing-xl);
    }

    .plan-amount .amount-label {
        font-size: 0.75rem;
        color: var(--color-gray-500);
        text-transform: uppercase;
    }

    .plan-amount .amount-value {
        font-size: 1.1rem;
        font-weight: 700;
    }

    .plan-progress {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-lg);
    }

    .progress-section .progress-label {
        display: flex;
        justify-content: space-between;
        font-size: 0.85rem;
        margin-bottom: var(--spacing-xs);
    }

    .progress-bar {
        height: 10px;
        background: var(--color-gray-200);
        border-radius: var(--radius-full);
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        border-radius: var(--radius-full);
        transition: width 0.5s ease;
    }

    .progress-blue { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .progress-green { background: linear-gradient(90deg, #10b981, #34d399); }

    .phases-title {
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: var(--spacing-md);
    }

    .add-procedure-form {
        margin-top: var(--spacing-lg);
        padding-top: var(--spacing-lg);
        border-top: 1px solid var(--color-gray-200);
    }

    @media (max-width: 768px) {
        .plan-header, .plan-stats { flex-direction: column; gap: var(--spacing-md); }
        .form-row { flex-direction: column; }
    }
</style>
@endpush
@endsection
