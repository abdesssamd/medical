@extends('layouts.app')

@section('title', 'Compagnies d\'assurance')
@section('page-title', 'Compagnies d\'assurance')

@section('content')
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h2>🏢 Compagnies d'assurance</h2>
            <p class="muted">Gestion des assureurs et conventions</p>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('billing.dashboard') }}">← Dashboard</a>
            <a class="btn btn-soft" href="{{ route('billing.insurance.claims') }}">🏥 Réclamations</a>
        </div>
    </section>

    <div class="insurance-grid">
        @forelse($companies as $company)
        <div class="insurance-card">
            <div class="insurance-header">
                <div class="insurance-logo">
                    <span class="insurance-icon">🏥</span>
                    <span class="insurance-name">{{ $company->name }}</span>
                </div>
                <span class="badge badge-{{ $company->is_active ? 'success' : 'danger' }}">
                    {{ $company->is_active ? 'Actif' : 'Inactif' }}
                </span>
            </div>
            <div class="insurance-code">
                <code>{{ $company->code }}</code>
            </div>
            <div class="insurance-details">
                @if($company->contact_phone)
                <div class="detail-item">
                    <span class="detail-label">📞 Téléphone</span>
                    <span class="detail-value">{{ $company->contact_phone }}</span>
                </div>
                @endif
                @if($company->contact_email)
                <div class="detail-item">
                    <span class="detail-label">📧 Email</span>
                    <span class="detail-value">{{ $company->contact_email }}</span>
                </div>
                @endif
            </div>
            <div class="insurance-stats">
                <div class="stat-box">
                    <div class="stat-value">{{ $company->claims_count }}</div>
                    <div class="stat-label">Réclamations</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">{{ $company->patient_subscriptions_count }}</div>
                    <div class="stat-label">Assurés</div>
                </div>
            </div>
            @if($company->coverage_rules)
            <div class="coverage-rules">
                <div class="coverage-title">Taux de couverture:</div>
                <div class="coverage-list">
                    @foreach($company->coverage_rules as $key => $rate)
                    <div class="coverage-item">
                        <span class="coverage-key">{{ $key }}</span>
                        <span class="coverage-rate">{{ ($rate * 100) }}%</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @empty
        <div class="empty-state">
            <p>Aucune compagnie d'assurance configurée.</p>
        </div>
        @endforelse
    </div>
</div>

@push('head')
<style>
    .insurance-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: var(--spacing-lg);
    }

    .insurance-card {
        background: var(--color-white);
        border: 1px solid var(--color-gray-200);
        border-radius: var(--radius-lg);
        padding: var(--spacing-lg);
        box-shadow: var(--shadow-sm);
        transition: all var(--transition-fast);
    }

    .insurance-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .insurance-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--spacing-md);
    }

    .insurance-logo {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
    }

    .insurance-icon {
        font-size: 1.5rem;
    }

    .insurance-name {
        font-size: 1.1rem;
        font-weight: 600;
    }

    .insurance-code {
        margin-bottom: var(--spacing-md);
    }

    .insurance-code code {
        background: var(--color-gray-100);
        padding: var(--spacing-xs) var(--spacing-sm);
        border-radius: var(--radius-sm);
        font-family: var(--font-mono);
        font-size: 0.85rem;
    }

    .insurance-details {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-sm);
        margin-bottom: var(--spacing-md);
        padding-bottom: var(--spacing-md);
        border-bottom: 1px solid var(--color-gray-200);
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
    }

    .detail-label { color: var(--color-gray-500); }
    .detail-value { font-weight: 500; }

    .insurance-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-md);
    }

    .stat-box {
        text-align: center;
        padding: var(--spacing-sm);
        background: var(--color-gray-50);
        border-radius: var(--radius-md);
    }

    .stat-box .stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--color-primary);
    }

    .stat-box .stat-label {
        font-size: 0.75rem;
        color: var(--color-gray-500);
        text-transform: uppercase;
    }

    .coverage-rules {
        padding-top: var(--spacing-md);
        border-top: 1px solid var(--color-gray-200);
    }

    .coverage-title {
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: var(--spacing-sm);
    }

    .coverage-list {
        display: flex;
        flex-wrap: wrap;
        gap: var(--spacing-xs);
    }

    .coverage-item {
        display: flex;
        justify-content: space-between;
        gap: var(--spacing-md);
        padding: var(--spacing-xs) var(--spacing-sm);
        background: var(--color-primary-light);
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
    }

    .coverage-key {
        font-weight: 500;
        color: #1e40af;
    }

    .coverage-rate {
        font-weight: 700;
        color: var(--color-primary);
    }

    @media (max-width: 768px) {
        .insurance-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush
@endsection
