@extends('queue::layouts.app')

@section('title', 'Facturation')

@section('page')
<div class="page-stack">
    {{-- Header --}}
    <section class="card toolbar">
        <div>
            <h1 class="page-title">💰 Facturation</h1>
            <div class="muted">Gestion des factures, paiements et assurances</div>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('billing.invoices') }}">📄 Factures</a>
            <a class="btn btn-soft" href="{{ route('billing.insurance.claims') }}">🏥 Assurances</a>
            <a class="btn btn-soft" href="{{ route('billing.insurance.companies') }}">🏢 Compagnies</a>
        </div>
    </section>

    <section class="card">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
            <div>
                <h2 class="section-title mb-1">Impayes & Tiers-payant</h2>
                <div class="muted">Relances automatiques et bordereaux de teletransmission.</div>
            </div>
            <form method="POST" action="{{ route('billing.teletransmission.generate') }}">
                @csrf
                <button class="btn btn-primary" type="submit">Generer teletransmission</button>
            </form>
        </div>
        <div class="grid-stats mt-2">
            <div class="card">
                <div class="label">Factures impayees</div>
                <div class="stat-number">{{ $unpaidDashboard['count'] ?? 0 }}</div>
            </div>
            <div class="card">
                <div class="label">Factures en retard</div>
                <div class="stat-number stat-orange">{{ $unpaidDashboard['overdue_count'] ?? 0 }}</div>
            </div>
            <div class="card">
                <div class="label">Montant a relancer</div>
                <div class="stat-number stat-red">{{ number_format((float) ($unpaidDashboard['total_remaining'] ?? 0), 2) }} MAD</div>
            </div>
        </div>
    </section>

    {{-- Filtre dates --}}
    <section class="card">
        <form method="GET" class="form-row">
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

    {{-- Stats --}}
    <section class="grid-stats">
        <div class="card">
            <div class="label">Total facturé</div>
            <div class="stat-number stat-blue">{{ number_format($stats['total_invoiced'], 2) }} MAD</div>
        </div>
        <div class="card">
            <div class="label">Total encaissé</div>
            <div class="stat-number stat-green">{{ number_format($stats['total_paid'], 2) }} MAD</div>
        </div>
        <div class="card">
            <div class="label">Reste à encaisser</div>
            <div class="stat-number stat-orange">{{ number_format($stats['total_outstanding'], 2) }} MAD</div>
        </div>
        <div class="card">
            <div class="label">Paiements</div>
            <div class="stat-number">{{ $stats['payment_count'] }}</div>
        </div>
        <div class="card">
            <div class="label">Factures payées</div>
            <div class="stat-number">{{ $stats['paid_invoice_count'] }}/{{ $stats['invoice_count'] }}</div>
        </div>
    </section>

    {{-- Par méthode de paiement --}}
    @if(count($stats['by_payment_method']) > 0)
    <section class="card">
        <h2 class="section-title">💳 Paiements par méthode</h2>
        <div class="payment-methods-grid">
            @foreach($stats['by_payment_method'] as $method => $data)
            <div class="payment-method-card">
                <div class="method-icon">
                    @if($method === 'cash') 💵
                    @elseif($method === 'card') 💳
                    @elseif($method === 'check') 📝
                    @elseif($method === 'bank_transfer') 🏦
                    @elseif($method === 'insurance') 🏥
                    @else 💰
                    @endif
                </div>
                <div class="method-name">{{ ucfirst($method) }}</div>
                <div class="method-count">{{ $data['count'] }} paiement(s)</div>
                <div class="method-total">{{ number_format($data['total'], 2) }} MAD</div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <div class="grid-2-col">
        {{-- Factures récentes --}}
        <section class="card">
            <h2 class="section-title">📄 Factures Récentes</h2>
            <div class="recent-list">
                @forelse($recentInvoices as $invoice)
                <div class="recent-item">
                    <div class="recent-item-info">
                        <div class="recent-item-title">
                            {{ $invoice->invoice_number }}
                            <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'partially_paid' ? 'warning' : 'danger') }}">
                                {{ $invoice->status }}
                            </span>
                        </div>
                        <div class="recent-item-subtitle">{{ $invoice->patient?->full_name ?? '-' }}</div>
                    </div>
                    <div class="recent-item-amount">
                        <div>{{ number_format($invoice->total, 2) }} MAD</div>
                        <div class="muted small">{{ $invoice->invoice_date->format('d/m/Y') }}</div>
                    </div>
                </div>
                @empty
                <div class="empty-state">Aucune facture.</div>
                @endforelse
            </div>
        </section>

        {{-- Réclamations en attente --}}
        <section class="card">
            <h2 class="section-title">🏥 Réclamations Assurance</h2>
            <div class="recent-list">
                @forelse($pendingClaims as $claim)
                <div class="recent-item">
                    <div class="recent-item-info">
                        <div class="recent-item-title">{{ $claim->claim_number }}</div>
                        <div class="recent-item-subtitle">{{ $claim->insuranceCompany?->name ?? '-' }}</div>
                    </div>
                    <div class="recent-item-amount">
                        <div>{{ number_format($claim->claimed_amount, 2) }} MAD</div>
                        <div class="badge badge-warning">{{ $claim->status }}</div>
                    </div>
                </div>
                @empty
                <div class="empty-state">Aucune réclamation en attente.</div>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Factures impayées --}}
    @if($overdueInvoices->count() > 0)
    <section class="card card-danger">
        <h2 class="section-title">⚠️ Factures Impayées / En Retard</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Patient</th>
                        <th>Montant</th>
                        <th>Reste dû</th>
                        <th>Échéance</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($overdueInvoices as $invoice)
                    <tr>
                        <td><code>{{ $invoice->invoice_number }}</code></td>
                        <td>{{ $invoice->patient?->full_name ?? '-' }}</td>
                        <td>{{ number_format($invoice->total, 2) }} MAD</td>
                        <td class="text-danger"><strong>{{ number_format($invoice->remaining_amount, 2) }} MAD</strong></td>
                        <td>{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            @if($invoice->isOverdue())
                                <span class="badge badge-danger">🔴 En retard</span>
                            @else
                                <span class="badge badge-warning">⏳ En attente</span>
                            @endif
                            <div class="mt-1 d-flex gap-1">
                                <form method="POST" action="{{ route('billing.invoices.remind', ['invoiceId' => $invoice->id]) }}">
                                    @csrf
                                    <input type="hidden" name="channel" value="sms">
                                    <button class="btn btn-soft btn-xs" type="submit">Relancer SMS</button>
                                </form>
                                <form method="POST" action="{{ route('billing.invoices.remind', ['invoiceId' => $invoice->id]) }}">
                                    @csrf
                                    <input type="hidden" name="channel" value="email">
                                    <button class="btn btn-soft btn-xs" type="submit">Relancer Email</button>
                                </form>
                            </div>
                        </td>
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
    .grid-2-col { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1rem; }
    
    .stat-blue { color: #1e40af; }
    .stat-green { color: #065f46; }
    .stat-orange { color: #c2410c; }
    .stat-red { color: #b91c1c; }
    
    .payment-methods-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
    .payment-method-card { padding: 1rem; text-align: center; background: #f9fafb; border-radius: 0.5rem; }
    .method-icon { font-size: 2rem; margin-bottom: 0.5rem; }
    .method-name { font-weight: 600; margin-bottom: 0.25rem; }
    .method-count { font-size: 0.85rem; color: #6b7280; }
    .method-total { font-weight: 700; color: #10b981; margin-top: 0.25rem; }
    
    .recent-list { display: flex; flex-direction: column; gap: 0.5rem; }
    .recent-item { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 0.35rem; }
    .recent-item-title { font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
    .recent-item-subtitle { font-size: 0.85rem; color: #6b7280; }
    .recent-item-amount { text-align: right; }
    .recent-item-amount .muted.small { font-size: 0.75rem; color: #9ca3af; }
    
    .card-danger { border: 1px solid #fee2e2; background: #fff5f5; }
    
    code { background: #f3f4f6; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.85rem; }
    .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .text-danger { color: #dc2626; }
    .btn-xs { padding: 0.2rem 0.45rem; font-size: 0.72rem; }
    .empty-state { padding: 2rem; text-align: center; color: #6b7280; background: #f9fafb; border-radius: 0.5rem; }
    .muted { color: #6b7280; }
    
    @media (max-width: 768px) {
        .grid-2-col { grid-template-columns: 1fr; }
    }
</style>
@endpush
@endsection
