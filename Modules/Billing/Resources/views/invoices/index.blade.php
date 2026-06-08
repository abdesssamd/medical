@extends('queue::layouts.app')

@section('title', 'Factures')

@section('page')
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h1 class="page-title">📄 Factures</h1>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('billing.dashboard') }}">← Dashboard</a>
        </div>
    </section>

    <section class="card">
        <form method="GET" class="form-row">
            <div>
                <label class="label">Recherche</label>
                <input type="text" class="input" name="search" value="{{ $search ?? '' }}" placeholder="Nom patient...">
            </div>
            <div>
                <label class="label">Statut</label>
                <select class="select" name="status">
                    <option value="">Tous</option>
                    <option value="draft" @selected(($status ?? '') === 'draft')">Brouillon</option>
                    <option value="sent" @selected(($status ?? '') === 'sent')">Envoyée</option>
                    <option value="partially_paid" @selected(($status ?? '') === 'partially_paid'>Partiellement payée</option>
                    <option value="paid" @selected(($status ?? '') === 'paid')">Payée</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit" style="align-self: flex-end;">OK</button>
        </form>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Payé</th>
                        <th>Reste</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td><code>{{ $invoice->invoice_number }}</code></td>
                        <td>{{ $invoice->patient?->full_name ?? '-' }}</td>
                        <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                        <td>{{ number_format($invoice->total, 2) }} MAD</td>
                        <td class="text-green">{{ number_format($invoice->paid_amount, 2) }}</td>
                        <td class="text-red">{{ number_format($invoice->remaining_amount, 2) }}</td>
                        <td><span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : 'warning' }}">{{ $invoice->status }}</span></td>
                        <td>
                            <a class="btn btn-sm btn-primary" href="{{ route('billing.invoices.show', ['invoiceId' => $invoice->id]) }}">👁️ Voir</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center muted">Aucune facture.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $invoices->links() }}</div>
    </section>
</div>

@push('head')
<style>
    .form-row { display: flex; gap: 1rem; flex-wrap: wrap; }
    .form-row > div { flex: 1; min-width: 200px; }
    code { background: #f3f4f6; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.85rem; }
    .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .text-green { color: #10b981; }
    .text-red { color: #dc2626; }
    .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.85rem; border-radius: 0.35rem; text-decoration: none; display: inline-block; background: #3b82f6; color: white; }
    .mt-2 { margin-top: 1rem; }
</style>
@endpush
@endsection
