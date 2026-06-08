@extends('queue::layouts.app')

@section('title', 'Dossier Patient - ' . $patient->full_name)

@section('page')
<div class="page-stack">
    {{-- Header --}}
    <section class="card toolbar">
        <div>
            <h1 class="page-title">🦷 Dossier: {{ $patient->full_name }}</h1>
            <div class="muted">
                N° {{ $patient->medical_record_number }} • {{ $patient->age }} ans
                @if($patient->hasAllergies())
                    <span class="badge badge-danger ml-2">⚠️ Allergies: {{ implode(', ', $patient->allergies) }}</span>
                @endif
            </div>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('clinical.patients') }}">← Retour</a>
            <a class="btn btn-soft" href="{{ route('clinical.patient.chart', ['patientId' => $patient->id]) }}">🦷 Odontogramme</a>
            <a class="btn btn-soft" href="{{ route('clinical.treatment-plans', ['patientId' => $patient->id]) }}">📋 Plans de traitement</a>
            <a class="btn btn-soft" href="{{ route('billing.invoices.create-from-procedures', ['patientId' => $patient->id]) }}">💰 Facturer</a>
        </div>
    </section>

    {{-- Info Patient --}}
    <section class="card">
        <h2 class="section-title">👤 Informations Patient</h2>
        <div class="patient-info-grid">
            <div><strong>Nom:</strong> {{ $patient->last_name }}</div>
            <div><strong>Prénom:</strong> {{ $patient->first_name }}</div>
            <div><strong>Date de naissance:</strong> {{ $patient->date_of_birth->format('d/m/Y') }}</div>
            <div><strong>Téléphone:</strong> {{ $patient->phone ?? '-' }}</div>
            <div><strong>Email:</strong> {{ $patient->email ?? '-' }}</div>
            <div><strong>CIN:</strong> {{ $patient->cin ?? '-' }}</div>
        </div>
        @if($patient->medical_history)
        <div class="mt-2">
            <strong>Antécédents médicaux:</strong>
            <div class="tags">
                @foreach($patient->medical_history as $condition)
                    <span class="tag tag-warning">{{ $condition }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </section>

    <div class="grid-2-col">
        {{-- Résumé Odontogramme --}}
        <section class="card">
            <h2 class="section-title">🦷 Résumé Odontogramme</h2>
            <div class="teeth-summary">
                @foreach($teethSummary as $status => $data)
                    @if($data['count'] > 0)
                    <div class="tooth-status-item">
                        <span class="status-label">{{ $data['label'] }}</span>
                        <span class="status-count">{{ $data['count'] }}</span>
                        <div class="status-teeth">{{ implode(', ', $data['teeth']) }}</div>
                    </div>
                    @endif
                @endforeach
            </div>
            <div class="mt-2">
                <strong>Coût estimé actes planifiés:</strong> {{ number_format($estimatedCost, 2) }} MAD
            </div>
        </section>

        {{-- Derniers actes --}}
        <section class="card">
            <h2 class="section-title">📝 Derniers Actes Clinques</h2>
            @if($procedures->isEmpty())
                <div class="empty-state">Aucun acte enregistré.</div>
            @else
                <div class="procedures-list">
                    @foreach($procedures->take(10) as $proc)
                    <div class="procedure-item">
                        <div class="proc-header">
                            <span class="proc-name">{{ $proc['name'] }}</span>
                            <span class="proc-price">{{ number_format($proc['price'], 2) }} MAD</span>
                        </div>
                        <div class="proc-details">
                            @if($proc['tooth_number'])
                                <span class="badge">Dent {{ $proc['tooth_number'] }}</span>
                            @endif
                            <span class="badge">{{ $proc['performed_at'] ? \Carbon\Carbon::parse($proc['performed_at'])->format('d/m/Y') : 'Planifié' }}</span>
                            @if($proc['practitioner_name'])
                                <span class="badge">{{ $proc['practitioner_name'] }}</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- Plans de traitement --}}
    <section class="card">
        <h2 class="section-title">📋 Plans de Traitement</h2>
        @if($treatmentPlans->isEmpty())
            <div class="empty-state">Aucun plan de traitement.</div>
        @else
            @foreach($treatmentPlans as $plan)
            <div class="treatment-plan-card">
                <div class="plan-header">
                    <h3>{{ $plan->name }}</h3>
                    <span class="badge badge-{{ $plan->status === 'in_progress' ? 'primary' : ($plan->status === 'completed' ? 'success' : 'warning') }}">
                        {{ $plan->status }}
                    </span>
                </div>
                <div class="plan-details">
                    <div><strong>Praticien:</strong> {{ $plan->practitioner?->display_name ?? '-' }}</div>
                    <div><strong>Coût total:</strong> {{ number_format($plan->total_estimated_cost, 2) }} MAD</div>
                    <div><strong>Payé:</strong> {{ number_format($plan->paid_amount, 2) }} MAD</div>
                    <div><strong>Reste:</strong> {{ number_format($plan->remaining_amount, 2) }} MAD</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $plan->payment_progress }}%"></div>
                </div>
                <div class="progress-text">Paiement: {{ $plan->payment_progress }}% • Traitement: {{ $plan->completion_progress }}%</div>
            </div>
            @endforeach
        @endif
    </section>

    {{-- Images médicales récentes --}}
    <section class="card">
        <h2 class="section-title">📷 Images Médicales Récentes</h2>
        @if($medicalImages->isEmpty())
            <div class="empty-state">Aucune image.</div>
        @else
            <div class="images-grid">
                @foreach($medicalImages as $image)
                <div class="image-card">
                    <div class="image-placeholder">
                        @if($image->is3D())
                            🧊
                        @elseif($image->isDicom())
                            🩻
                        @else
                            📷
                        @endif
                    </div>
                    <div class="image-info">
                        <div class="image-type">{{ $image->type }}</div>
                        @if($image->associated_teeth)
                            <div class="image-teeth">Dents: {{ implode(', ', $image->associated_teeth) }}</div>
                        @endif
                        <div class="image-date">{{ $image->taken_at?->format('d/m/Y') ?? '-' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </section>
</div>

@push('head')
<style>
    .grid-2-col { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1rem; }
    .patient-info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; }
    
    .teeth-summary { display: flex; flex-direction: column; gap: 0.5rem; }
    .tooth-status-item { display: flex; align-items: center; gap: 1rem; padding: 0.5rem; background: #f9fafb; border-radius: 0.35rem; }
    .status-label { min-width: 150px; font-weight: 500; }
    .status-count { font-weight: 700; color: #3b82f6; }
    .status-teeth { color: #6b7280; font-size: 0.85rem; }
    
    .procedures-list { display: flex; flex-direction: column; gap: 0.5rem; }
    .procedure-item { padding: 0.75rem; background: #f9fafb; border-radius: 0.35rem; }
    .proc-header { display: flex; justify-content: space-between; margin-bottom: 0.35rem; }
    .proc-name { font-weight: 600; }
    .proc-price { color: #10b981; font-weight: 500; }
    .proc-details { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    
    .treatment-plan-card { padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 1rem; }
    .plan-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
    .plan-header h3 { margin: 0; }
    .plan-details { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; margin-bottom: 0.75rem; }
    
    .progress-bar { height: 8px; background: #e5e7eb; border-radius: 9999px; overflow: hidden; }
    .progress-fill { height: 100%; background: linear-gradient(90deg, #3b82f6, #10b981); transition: width 0.3s; }
    .progress-text { font-size: 0.85rem; color: #6b7280; margin-top: 0.35rem; }
    
    .images-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
    .image-card { border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden; }
    .image-placeholder { height: 120px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-size: 3rem; }
    .image-info { padding: 0.75rem; }
    .image-type { font-weight: 600; font-size: 0.85rem; }
    .image-teeth { font-size: 0.75rem; color: #6b7280; }
    .image-date { font-size: 0.75rem; color: #9ca3af; }
    
    .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; }
    .badge-primary { background: #dbeafe; color: #1e40af; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    
    .tags { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem; }
    .tag { padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.85rem; }
    .tag-warning { background: #fef3c7; color: #92400e; }
    
    .empty-state { padding: 2rem; text-align: center; color: #6b7280; background: #f9fafb; border-radius: 0.5rem; }
    .mt-2 { margin-top: 0.75rem; }
    .ml-2 { margin-left: 0.5rem; }
    
    @media (max-width: 768px) {
        .grid-2-col, .patient-info-grid, .plan-details { grid-template-columns: 1fr; }
    }
</style>
@endpush
@endsection
