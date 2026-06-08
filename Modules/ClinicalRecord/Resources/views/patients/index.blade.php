
@extends('layouts.admin')
@section('page_pretitle', 'Role Secretaire')
@section('title', 'Dossiers Clinques')

@section('page')
<div class="page-stack">
    {{-- Header --}}
    <section class="card toolbar">
        <div>
            <h1 class="page-title">🦷 Dossiers Clinques</h1>
            <div class="muted">Gestion des dossiers patients et odontogrammes</div>
        </div>
    </section>

    {{-- Recherche --}}
    <section class="card">
        <form method="GET" class="form-row">
            <div style="flex: 1;">
                <label class="label">Rechercher un patient</label>
                <input type="text" class="input" name="search" value="{{ $search ?? '' }}" placeholder="Nom, prénom, CIN, N° dossier...">
            </div>
            <button class="btn btn-primary" type="submit" style="align-self: flex-end;">🔍 Rechercher</button>
        </form>
    </section>

    {{-- Liste des patients --}}
    <section class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>N° Dossier</th>
                        <th>Nom complet</th>
                        <th>Âge</th>
                        <th>Téléphone</th>
                        <th>Rendez-vous</th>
                        <th>Actes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                    <tr>
                        <td><code>{{ $patient->medical_record_number }}</code></td>
                        <td>
                            <strong>{{ $patient->full_name }}</strong>
                            @if($patient->hasAllergies())
                                <span class="badge badge-danger" title="Allergies: {{ implode(', ', $patient->allergies) }}">⚠️</span>
                            @endif
                        </td>
                        <td>{{ $patient->age }} ans</td>
                        <td>{{ $patient->phone ?? '-' }}</td>
                        <td>{{ $patient->appointments_count ?? 0 }}</td>
                        <td>{{ $patient->clinical_procedures_count ?? 0 }}</td>
                        <td class="actions">
                            <a class="btn btn-sm btn-primary" href="{{ route('clinical.patient.show', ['patientId' => $patient->id]) }}">
                                📋 Dossier
                            </a>
                            <a class="btn btn-sm btn-secondary" href="{{ route('clinical.patient.chart', ['patientId' => $patient->id]) }}">
                                🦷 Odontogramme
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center muted">Aucun patient trouvé.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $patients->links() }}
        </div>
    </section>
</div>

@push('head')
<style>
    .actions {
        display: flex;
        gap: 0.5rem;
    }
    .btn-sm {
        padding: 0.35rem 0.75rem;
        font-size: 0.85rem;
        border-radius: 0.35rem;
        text-decoration: none;
        display: inline-block;
    }
    .btn-primary { background: #3b82f6; color: white; border: none; }
    .btn-secondary { background: #6b7280; color: white; border: none; }
    code {
        background: #f3f4f6;
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.85rem;
    }
    .badge {
        display: inline-block;
        padding: 0.15rem 0.4rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        margin-left: 0.5rem;
    }
    .badge-danger { background: #fee2e2; color: #991b1b; }
</style>
@endpush
@endsection
