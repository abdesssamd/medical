@extends('layouts.admin')

@section('title', $questionnaire->name)
@section('page_title', $questionnaire->name)
@section('page_pretitle', 'Modèle de Questionnaire')

@push('styles')
<style>
    .container-max {
        max-width: 1200px;
        margin: 0 auto;
    }

    .card-modern {
        background: white;
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
        transition: all 0.2s;
    }

    .card-modern:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .header-card {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 32px;
    }

    .header-content h1 {
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 8px;
    }

    .header-meta {
        display: flex;
        gap: 16px;
        margin-top: 16px;
        flex-wrap: wrap;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #64748b;
    }

    .meta-item i {
        color: #3b82f6;
        font-size: 16px;
    }

    .badge-modern {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-active {
        background: #dcfce7;
        color: #166534;
    }

    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-specialty {
        background: #dbeafe;
        color: #1e40af;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-modern {
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.4);
    }

    .btn-secondary {
        background: white;
        color: #0f172a;
        border: 1px solid #e2e8f0;
    }

    .btn-secondary:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(16, 185, 129, 0.4);
    }

    .btn-danger {
        background: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        border: 1px solid #e2e8f0;
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .stat-label {
        font-size: 13px;
        color: #64748b;
        margin-top: 6px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .info-block {
        border-left: 3px solid #3b82f6;
        padding-left: 16px;
    }

    .info-label {
        font-size: 12px;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .info-value {
        font-size: 14px;
        color: #0f172a;
        font-weight: 500;
    }

    .section-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .section-title i {
        color: #3b82f6;
    }

    .fields-preview {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .field-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        transition: all 0.2s;
    }

    .field-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .field-label {
        font-weight: 600;
        color: #0f172a;
        font-size: 14px;
        margin-bottom: 6px;
    }

    .field-type {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        background: #dbeafe;
        color: #1e40af;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }

    .field-required {
        display: inline-block;
        padding: 2px 8px;
        background: #fee2e2;
        color: #991b1b;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
        margin-left: 8px;
    }

    .field-options {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e2e8f0;
    }

    .field-options-list {
        font-size: 13px;
        color: #64748b;
        margin: 0;
        padding-left: 20px;
    }

    .field-options-list li {
        margin: 4px 0;
    }

    .table-responsive {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .table {
        margin: 0;
    }

    .table thead {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    }

    .table thead th {
        border: none;
        font-weight: 600;
        color: #475569;
        padding: 16px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table tbody td {
        border: 1px solid #e2e8f0;
        padding: 16px;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: #94a3b8;
    }

    .empty-state i {
        font-size: 48px;
        display: block;
        margin-bottom: 12px;
    }

    .description-text {
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 0;
    }

    @media (max-width: 768px) {
        .header-card {
            flex-direction: column;
        }

        .action-buttons {
            width: 100%;
        }

        .action-buttons .btn-modern {
            flex: 1;
            justify-content: center;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .fields-preview {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="page-body-modern">
    <div class="container-max">
        <!-- Header -->
        <div class="card-modern header-card">
            <div class="header-content">
                <h1>{{ $questionnaire->name }}</h1>
                @if ($questionnaire->description)
                    <p class="description-text">{{ $questionnaire->description }}</p>
                @endif
                <div class="header-meta">
                    <span class="badge-modern {{ $questionnaire->is_active ? 'badge-active' : 'badge-inactive' }}">
                        {{ $questionnaire->is_active ? '✓ Actif' : '✕ Inactif' }}
                    </span>
                    @if ($questionnaire->specialty)
                        <span class="badge-modern badge-specialty">{{ $questionnaire->specialty->name }}</span>
                    @endif
                    <span class="meta-item">
                        <i class="ti ti-user"></i>
                        Créé par {{ $questionnaire->creator?->name ?? 'Système' }}
                    </span>
                    <span class="meta-item">
                        <i class="ti ti-calendar"></i>
                        {{ $questionnaire->created_at->translatedFormat('d F Y à H:i') }}
                    </span>
                </div>
            </div>

            <div class="action-buttons">
                <a href="{{ route('clinical.questionnaire-templates.edit', $questionnaire->id) }}" class="btn-modern btn-primary">
                    <i class="ti ti-edit"></i>
                    Éditer
                </a>
                <form method="POST" action="{{ route('clinical.questionnaire-templates.duplicate', $questionnaire->id) }}"
                      style="display: inline;" onsubmit="return confirm('Dupliquer ce modèle ?');">
                    @csrf
                    <button type="submit" class="btn-modern btn-success">
                        <i class="ti ti-copy"></i>
                        Dupliquer
                    </button>
                </form>
                <a href="{{ route('clinical.questionnaire-templates.export', $questionnaire->id) }}" class="btn-modern btn-secondary">
                    <i class="ti ti-download"></i>
                    Exporter
                </a>
                <form method="POST" action="{{ route('clinical.questionnaire-templates.destroy', $questionnaire->id) }}" 
                      style="display: inline;" onsubmit="return confirm('Êtes-vous sûr ? Cette action ne peut pas être annulée.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-modern btn-danger">
                        <i class="ti ti-trash"></i>
                        Supprimer
                    </button>
                </form>
                <a href="{{ route('clinical.questionnaire-templates.index') }}" class="btn-modern btn-secondary">
                    <i class="ti ti-arrow-left"></i>
                    Retour
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <p class="stat-value">{{ count($questionnaire->field_schema ?? []) }}</p>
                <div class="stat-label">Champs</div>
            </div>
            <div class="stat-card">
                <p class="stat-value">{{ $responseCount }}</p>
                <div class="stat-label">Réponses</div>
            </div>
            @if ($questionnaire->practitioner)
                <div class="stat-card">
                    <p class="stat-label" style="font-size: 13px; font-weight: 600; margin-top: 0;">Responsable</p>
                    <p style="font-size: 14px; color: #0f172a; margin: 4px 0 0;">{{ $questionnaire->practitioner->name }}</p>
                </div>
            @endif
        </div>

        <!-- Information -->
        <div class="info-grid">
            @if ($questionnaire->group_name)
                <div class="info-block">
                    <div class="info-label">Catégorie</div>
                    <div class="info-value">{{ $questionnaire->group_name }}</div>
                </div>
            @endif
            <div class="info-block">
                <div class="info-label">Crée le</div>
                <div class="info-value">{{ $questionnaire->created_at->translatedFormat('d F Y \à H:i') }}</div>
            </div>
            <div class="info-block">
                <div class="info-label">Mis à jour le</div>
                <div class="info-value">{{ $questionnaire->updated_at->translatedFormat('d F Y \à H:i') }}</div>
            </div>
        </div>

        <!-- Fields Preview -->
        <div class="card-modern">
            <div class="section-title">
                <i class="ti ti-list-check"></i>
                Champs du Questionnaire
            </div>

            @if (count($questionnaire->field_schema ?? []) > 0)
                <div class="fields-preview">
                    @foreach ($questionnaire->field_schema as $field)
                        <div class="field-card">
                            <div class="field-label">
                                {{ $field['label'] }}
                                @if ($field['required'] ?? false)
                                    <span class="field-required">Obligatoire</span>
                                @endif
                            </div>
                            <span class="field-type">
                                <i class="ti ti-list-check"></i>
                                {{ $field['type'] }}
                            </span>
                            @if ($field['helpText'] ?? null)
                                <div style="font-size: 12px; color: #94a3b8; margin-top: 8px;">
                                    {{ $field['helpText'] }}
                                </div>
                            @endif
                            @if (! empty($field['options'] ?? []))
                                <div class="field-options">
                                    <strong style="font-size: 12px; color: #475569;">Options:</strong>
                                    <ul class="field-options-list">
                                        @foreach ($field['options'] as $option)
                                            <li>{{ $option }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <i class="ti ti-inbox"></i>
                    <p>Aucun champ configuré pour ce questionnaire.</p>
                </div>
            @endif
        </div>

        <!-- Recent Responses -->
        <div class="card-modern">
            <div class="section-title">
                <i class="ti ti-clipboard-list"></i>
                Réponses Récentes
            </div>

            @if ($recentResponses->count() > 0)
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Date de Remplissage</th>
                                <th>Validée par</th>
                                <th>Risques</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentResponses as $response)
                                <tr>
                                    <td>
                                        <strong style="color: #0f172a;">
                                            {{ $response->patient?->full_name ?? 'Patients supprimé' }}
                                        </strong>
                                    </td>
                                    <td style="font-size: 13px; color: #64748b;">
                                        {{ $response->filled_on?->translatedFormat('d F Y') ?? '-' }}
                                    </td>
                                    <td style="font-size: 13px; color: #64748b;">
                                        {{ $response->validator?->name ?? '-' }}
                                    </td>
                                    <td>
                                        @if ($response->has_critical_risk)
                                            <span style="display: inline-block; padding: 4px 10px; background: #fee2e2; color: #991b1b; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                                ⚠ Risques
                                            </span>
                                        @else
                                            <span style="display: inline-block; padding: 4px 10px; background: #dcfce7; color: #166534; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                                ✓ OK
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($responseCount > 10)
                    <div style="margin-top: 16px; text-align: center; font-size: 13px; color: #94a3b8;">
                        ... et {{ $responseCount - 10 }} autre{{ $responseCount - 10 > 1 ? 's' : '' }} réponse{{ $responseCount - 10 > 1 ? 's' : '' }}
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <i class="ti ti-inbox"></i>
                    <p>Aucune réponse pour ce questionnaire.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
