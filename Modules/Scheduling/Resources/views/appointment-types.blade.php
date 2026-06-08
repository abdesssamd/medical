@extends('queue::layouts.app')

@section('title', 'Types d\'actes')

@section('page')
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h1 class="page-title">🏷️ Types d'actes</h1>
            <div class="muted">Configuration des types de rendez-vous par spécialité</div>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('scheduling.dashboard') }}">← Planification</a>
        </div>
    </section>

    <div class="grid-2-col">
        {{-- Formulaire --}}
        <section class="card">
            <h2 class="section-title">➕ Nouveau type d'acte</h2>
            <form method="POST" action="{{ route('scheduling.appointment-types.store') }}">
                @csrf
                <div class="form-group">
                    <label class="label">Spécialité *</label>
                    <select class="select" name="specialty_id" required>
                        <option value="">-- Sélectionner --</option>
                        @foreach($specialties as $spec)
                            <option value="{{ $spec->id }}" @selected($selectedSpecialtyId === $spec->id)>{{ $spec->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="label">Code *</label>
                    <input type="text" class="input" name="code" placeholder="ex: IMPLANT, COURONNE" required>
                </div>
                <div class="form-group">
                    <label class="label">Nom *</label>
                    <input type="text" class="input" name="name" required>
                </div>
                <div class="form-group">
                    <label class="label">Durée (minutes) *</label>
                    <input type="number" class="input" name="duration_minutes" value="30" min="5" required>
                </div>
                <div class="form-group">
                    <label class="label">Prix de base (MAD)</label>
                    <input type="number" step="0.01" class="input" name="base_price" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="label">
                        <input type="checkbox" name="requires_follow_up" value="1"> Nécessite un suivi
                    </label>
                </div>
                <div class="form-group">
                    <label class="label">Jours avant suivi</label>
                    <input type="number" class="input" name="follow_up_days" value="7" min="1">
                </div>
                <button class="btn btn-primary" type="submit">✅ Créer</button>
            </form>
        </section>

        {{-- Liste --}}
        <section class="card">
            <h2 class="section-title">📋 Types existants</h2>
            @if($appointmentTypes->isEmpty())
                <div class="empty-state">Sélectionnez une spécialité pour voir ses types d'actes.</div>
            @else
                @foreach($appointmentTypes as $type)
                <div class="type-card">
                    <div class="type-header">
                        <strong>{{ $type->name }}</strong>
                        <code>{{ $type->code }}</code>
                    </div>
                    <div class="type-details">
                        <span>⏱️ {{ $type->duration_minutes }} min</span>
                        @if($type->base_price)
                            <span>💰 {{ number_format($type->base_price, 2) }} MAD</span>
                        @endif
                        @if($type->requires_follow_up)
                            <span>🔄 Suivi J+{{ $type->follow_up_days }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            @endif
        </section>
    </div>
</div>

@push('head')
<style>
    .grid-2-col { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1rem; }
    .form-group { margin-bottom: 1rem; }
    .type-card { padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.75rem; }
    .type-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
    .type-details { display: flex; gap: 1rem; color: #6b7280; font-size: 0.9rem; }
    code { background: #f3f4f6; padding: 0.15rem 0.4rem; border-radius: 0.25rem; }
    .empty-state { padding: 2rem; text-align: center; color: #6b7280; background: #f9fafb; border-radius: 0.5rem; }
</style>
@endpush
@endsection
