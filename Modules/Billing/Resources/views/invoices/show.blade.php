@extends('queue::layouts.app')

@section('title', 'Facture ' . $invoice->invoice_number)

@section('page')
<div class="page-stack">
    {{-- Header --}}
    <section class="card toolbar">
        <div>
            <h1 class="page-title">📄 Facture {{ $invoice->invoice_number }}</h1>
            <div class="muted">
                {{ $invoice->patient?->full_name ?? '-' }} • {{ $invoice->invoice_date->format('d/m/Y') }}
                <span class="badge badge-{{ $invoice->isPaid() ? 'success' : ($invoice->status === 'partially_paid' ? 'warning' : 'danger') }}">
                    {{ $invoice->status }}
                </span>
            </div>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('billing.invoices') }}">← Retour</a>
            @if(!$invoice->isPaid())
                <button class="btn btn-primary" onclick="document.getElementById('paymentForm').style.display='block'">💰 Enregistrer paiement</button>
            @endif
        </div>
    </section>

    {{-- Info facture --}}
    <section class="card">
        <div class="invoice-info-grid">
            <div><strong>Patient:</strong> {{ $invoice->patient?->full_name ?? '-' }}</div>
            <div><strong>Praticien:</strong> {{ $invoice->practitioner?->display_name ?? '-' }}</div>
            <div><strong>Date:</strong> {{ $invoice->invoice_date->format('d/m/Y') }}</div>
            <div><strong>Échéance:</strong> {{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</div>
            @if($invoice->treatmentPlan)
                <div><strong>Plan de traitement:</strong> {{ $invoice->treatmentPlan->name }}</div>
            @endif
        </div>
    </section>

    {{-- Résumé financier --}}
    <section class="grid-stats">
        <div class="card">
            <div class="label">Sous-total</div>
            <div class="stat-number">{{ number_format($invoice->subtotal, 2) }} MAD</div>
        </div>
        <div class="card">
            <div class="label">TVA ({{ $invoice->tax_rate }}%)</div>
            <div class="stat-number">{{ number_format($invoice->tax_amount, 2) }} MAD</div>
        </div>
        @if($invoice->discount_amount > 0)
        <div class="card">
            <div class="label">Remise</div>
            <div class="stat-number stat-red">-{{ number_format($invoice->discount_amount, 2) }} MAD</div>
        </div>
        @endif
        <div class="card">
            <div class="label">Total</div>
            <div class="stat-number stat-blue">{{ number_format($invoice->total, 2) }} MAD</div>
        </div>
        <div class="card">
            <div class="label">Payé</div>
            <div class="stat-number stat-green">{{ number_format($invoice->paid_amount, 2) }} MAD</div>
        </div>
        <div class="card">
            <div class="label">Reste dû</div>
            <div class="stat-number {{ $invoice->remaining_amount > 0 ? 'stat-red' : 'stat-green' }}">{{ number_format($invoice->remaining_amount, 2) }} MAD</div>
        </div>
    </section>

    {{-- Lignes de facture --}}
    <section class="card">
        <h2 class="section-title">📋 Détail des actes</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Acte</th>
                        <th>Code</th>
                        <th>Qté</th>
                        <th>Prix unit.</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->lineItems as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td><code>{{ $item->procedure_code ?? '-' }}</code></td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->unit_price, 2) }} MAD</td>
                        <td><strong>{{ number_format($item->total_price, 2) }} MAD</strong></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right"><strong>Total:</strong></td>
                        <td><strong>{{ number_format($invoice->total, 2) }} MAD</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    {{-- Formulaire paiement --}}
    <section class="card" id="paymentForm" style="display: none;">
        <h2 class="section-title">💰 Enregistrer un paiement</h2>
        <form method="POST" action="{{ route('billing.invoices.record-payment', ['invoiceId' => $invoice->id]) }}">
            @csrf
            <div class="form-row">
                <div>
                    <label class="label">Montant</label>
                    <input type="number" step="0.01" class="input" name="amount" value="{{ $invoice->remaining_amount }}" max="{{ $invoice->remaining_amount }}" required>
                </div>
                <div>
                    <label class="label">Méthode</label>
                    <select class="select" name="method" required>
                        <option value="cash">💵 Espèces</option>
                        <option value="card">💳 Carte</option>
                        <option value="check">📝 Chèque</option>
                        <option value="bank_transfer">🏦 Virement</option>
                    </select>
                </div>
                <div>
                    <label class="label">Référence</label>
                    <input type="text" class="input" name="reference" placeholder="N° chèque, transaction...">
                </div>
                <button class="btn btn-success" type="submit" style="align-self: flex-end;">✅ Valider</button>
            </div>
        </form>
    </section>

    {{-- Historique paiements --}}
    @if($invoice->payments->count() > 0)
    <section class="card">
        <h2 class="section-title">📜 Historique des paiements</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Date</th>
                        <th>Méthode</th>
                        <th>Montant</th>
                        <th>Reçu par</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->payments as $payment)
                    <tr>
                        <td><code>{{ $payment->payment_number }}</code></td>
                        <td>{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                        <td>{{ ucfirst($payment->method) }}</td>
                        <td><strong>{{ number_format($payment->amount, 2) }} MAD</strong></td>
                        <td>{{ $payment->receivedBy?->name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif
</div>

@push('head')
<style>
    .invoice-info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; }
    .stat-blue { color: #1e40af; }
    .stat-green { color: #065f46; }
    .stat-red { color: #dc2626; }
    .text-right { text-align: right; }
    code { background: #f3f4f6; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.85rem; }
    .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .form-row { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
    .form-row > div { flex: 1; min-width: 200px; }
</style>
@endpush
@endsection
