@extends('layouts.app')

@section('title', 'Réclamations Assurance')
@section('page-title', 'Réclamations d\'assurance')

@section('content')
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h2>🏥 Réclamations</h2>
            <p class="muted">Suivi des demandes de remboursement</p>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('billing.dashboard') }}">← Dashboard</a>
            <a class="btn btn-soft" href="{{ route('billing.insurance.companies') }}">🏢 Compagnies</a>
        </div>
    </section>

    {{-- Filtres --}}
    <section class="card">
        <form method="GET" class="form-row">
            <div>
                <label class="label">Statut</label>
                <select class="select" name="status">
                    <option value="">Tous</option>
                    <option value="pending" @selected(($status ?? '') === 'pending'>En attente</option>
                    <option value="submitted" @selected(($status ?? '') === 'submitted'>Soumis</option>
                    <option value="approved" @selected(($status ?? '') === 'approved'>Approuvé</option>
                    <option value="paid" @selected(($status ?? '') === 'paid'>Payé</option>
                    <option value="rejected" @selected(($status ?? '') === 'rejected'>Rejeté</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit" style="align-self: flex-end;">OK</button>
        </form>
    </section>

    {{-- Tableau --}}
    <section class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>N° Réclamation</th>
                        <th>Patient</th>
                        <th>Assurance</th>
                        <th>Montant réclamé</th>
                        <th>Montant approuvé</th>
                        <th>Reste patient</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($claims as $claim)
                    <tr>
                        <td><code>{{ $claim->claim_number }}</code></td>
                        <td>{{ $claim->invoice?->patient?->full_name ?? '-' }}</td>
                        <td>{{ $claim->insuranceCompany?->name ?? '-' }}</td>
                        <td>{{ number_format($claim->claimed_amount, 2) }} MAD</td>
                        <td>{{ $claim->approved_amount ? number_format($claim->approved_amount, 2).' MAD' : '-' }}</td>
                        <td class="{{ $claim->patient_remaining > 0 ? 'text-danger' : 'text-success' }}">
                            {{ $claim->patient_remaining ? number_format($claim->patient_remaining, 2).' MAD' : '-' }}
                        </td>
                        <td>
                            <span class="badge badge-{{
                                $claim->status === 'paid' ? 'success' :
                                ($claim->status === 'approved' ? 'info' :
                                ($claim->status === 'rejected' ? 'danger' : 'warning'))
                            }}">
                                {{ $claim->status }}
                            </span>
                        </td>
                        <td>
                            <div class="actions-group">
                                @if(in_array($claim->status, ['pending', 'submitted']))
                                    <button class="btn btn-sm btn-success" onclick="showApproveModal({{ $claim->id }}, {{ $claim->claimed_amount }})">
                                        ✅ Approuver
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center muted">Aucune réclamation.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $claims->links() }}</div>
    </section>
</div>

{{-- Approve Modal --}}
<div class="modal-overlay" id="approveModal" style="display: none;">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3>Approuver la réclamation</h3>
            <button class="modal-close" onclick="closeApproveModal()">×</button>
        </div>
        <form id="approveForm" method="POST" action="">
            @csrf
            <div class="form-group">
                <label class="label">Montant approuvé (MAD)</label>
                <input type="number" step="0.01" class="input" name="approved_amount" id="approvedAmount" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-soft" onclick="closeApproveModal()">Annuler</button>
                <button type="submit" class="btn btn-success">✅ Confirmer</button>
            </div>
        </form>
    </div>
</div>

@push('head')
<style>
    .actions-group { display: flex; gap: var(--spacing-xs); }
    .modal-sm { max-width: 400px; }
    .text-danger { color: var(--color-danger); font-weight: 600; }
    .text-success { color: var(--color-success); font-weight: 600; }
</style>
@endpush

@push('scripts')
<script>
function showApproveModal(claimId, maxAmount) {
    document.getElementById('approveForm').action = `/billing/insurance/claims/${claimId}/approve`;
    document.getElementById('approvedAmount').max = maxAmount;
    document.getElementById('approvedAmount').value = maxAmount;
    document.getElementById('approveModal').style.display = 'flex';
}

function closeApproveModal() {
    document.getElementById('approveModal').style.display = 'none';
}
</script>
@endpush
@endsection
