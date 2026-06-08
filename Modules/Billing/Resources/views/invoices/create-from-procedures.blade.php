@extends('layouts.app')

@section('title', 'Créer Facture')
@section('page-title', 'Nouvelle Facture')

@section('content')
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h2>💰 Créer une facture depuis les actes</h2>
            <p class="muted">Patient: {{ $patient->full_name }} ({{ $patient->medical_record_number }})</p>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('clinical.patient.show', ['patientId' => $patient->id]) }}">← Retour</a>
        </div>
    </section>

    <form method="POST" action="{{ route('billing.invoices.store-from-procedures', ['patientId' => $patient->id]) }}">
        @csrf
        
        {{-- Sélection des actes --}}
        <section class="card">
            <h3 class="card-title">📋 Actes non facturés ({{ $unbilledProcedures->count() }})</h3>
            @if($unbilledProcedures->isEmpty())
                <div class="empty-state">
                    <p>✅ Tous les actes sont déjà facturés.</p>
                </div>
            @else
                <div class="procedures-select">
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Date</th>
                                <th>Acte</th>
                                <th>Code</th>
                                <th>Dent</th>
                                <th>Praticien</th>
                                <th>Prix</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unbilledProcedures as $proc)
                            <tr>
                                <td>
                                    <input type="checkbox" name="procedure_ids[]" value="{{ $proc->id }}" 
                                           class="procedure-checkbox" data-price="{{ $proc->price }}" checked>
                                </td>
                                <td>{{ $proc->performed_at?->format('d/m/Y') ?? '-' }}</td>
                                <td><strong>{{ $proc->name }}</strong></td>
                                <td><code>{{ $proc->procedure_code }}</code></td>
                                <td>{{ $proc->tooth_number ? 'Dent '.$proc->tooth_number : '-' }}</td>
                                <td>{{ $proc->practitioner?->name ?? '-' }}</td>
                                <td class="procedure-price">{{ number_format($proc->price, 2) }} MAD</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Options de facturation --}}
        <section class="card">
            <h3 class="card-title">⚙️ Options de facturation</h3>
            <div class="form-row">
                <div>
                    <label class="label">Praticien responsable</label>
                    <select class="select" name="practitioner_id">
                        <option value="">-- Aucun --</option>
                        @foreach($practitioners as $p)
                            <option value="{{ $p->id }}">{{ $p->display_name ?? $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Plan de traitement lié</label>
                    <select class="select" name="treatment_plan_id">
                        <option value="">-- Aucun --</option>
                        @foreach($treatmentPlans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Remise (MAD)</label>
                    <input type="number" step="0.01" class="input" name="discount_amount" value="0" min="0" id="discountInput">
                </div>
                <div>
                    <label class="label">TVA (%)</label>
                    <input type="number" step="0.01" class="input" name="tax_rate" value="0" min="0" max="100" id="taxRateInput">
                </div>
            </div>
            <div class="form-row mt-2">
                <div style="flex: 2;">
                    <label class="label">Notes</label>
                    <textarea class="textarea" name="notes" rows="3" placeholder="Notes ou observations..."></textarea>
                </div>
            </div>
        </section>

        {{-- Résumé --}}
        <section class="card summary-card">
            <h3 class="card-title">📊 Résumé de la facture</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-label">Sous-total</span>
                    <span class="summary-value" id="subtotal">0.00 MAD</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">TVA (<span id="taxRateDisplay">0</span>%)</span>
                    <span class="summary-value" id="taxAmount">0.00 MAD</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Remise</span>
                    <span class="summary-value text-danger" id="discountDisplay">-0.00 MAD</span>
                </div>
                <div class="summary-item summary-total">
                    <span class="summary-label">Total</span>
                    <span class="summary-value" id="totalAmount">0.00 MAD</span>
                </div>
            </div>
            <div class="summary-actions">
                <a class="btn btn-soft" href="{{ route('clinical.patient.show', ['patientId' => $patient->id]) }}">Annuler</a>
                <button type="submit" class="btn btn-primary" id="submitBtn" {{ $unbilledProcedures->isEmpty() ? 'disabled' : '' }}>
                    ✅ Créer la facture
                </button>
            </div>
        </section>
    </form>
</div>

@push('head')
<style>
    .procedures-select table { width: 100%; }
    .procedure-price { font-weight: 600; color: var(--color-success); }
    
    .summary-card {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: white;
    }
    .summary-card .card-title { color: white; }
    
    .summary-grid {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-xl);
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: var(--spacing-sm) 0;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .summary-label { font-size: 0.9rem; color: #94a3b8; }
    .summary-value { font-weight: 600; }
    
    .summary-total .summary-label {
        font-size: 1.1rem;
        font-weight: 600;
        color: white;
    }
    .summary-total .summary-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #10b981;
    }
    
    .summary-actions {
        display: flex;
        gap: var(--spacing-md);
        justify-content: flex-end;
    }
    
    .text-danger { color: #fca5a5; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.procedure-checkbox');
    const discountInput = document.getElementById('discountInput');
    const taxRateInput = document.getElementById('taxRateInput');
    const selectAll = document.getElementById('selectAll');
    
    function updateSummary() {
        let subtotal = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                subtotal += parseFloat(cb.dataset.price);
            }
        });
        
        const discount = parseFloat(discountInput?.value || 0);
        const taxRate = parseFloat(taxRateInput?.value || 0);
        const taxAmount = (subtotal - discount) * (taxRate / 100);
        const total = subtotal + taxAmount - discount;
        
        document.getElementById('subtotal').textContent = subtotal.toFixed(2) + ' MAD';
        document.getElementById('taxRateDisplay').textContent = taxRate;
        document.getElementById('taxAmount').textContent = taxAmount.toFixed(2) + ' MAD';
        document.getElementById('discountDisplay').textContent = '-' + discount.toFixed(2) + ' MAD';
        document.getElementById('totalAmount').textContent = total.toFixed(2) + ' MAD';
        
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = subtotal <= 0;
        }
    }
    
    checkboxes.forEach(cb => cb.addEventListener('change', updateSummary));
    if (discountInput) discountInput.addEventListener('input', updateSummary);
    if (taxRateInput) taxRateInput.addEventListener('input', updateSummary);
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateSummary();
        });
    }
    
    updateSummary();
});
</script>
@endpush
@endsection
