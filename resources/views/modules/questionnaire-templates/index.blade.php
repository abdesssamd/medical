@extends('layouts.admin')

@section('title', 'Gestion des Questionnaires')
@section('page_title', 'Gestion des Modèles de Questionnaires')
@section('page_pretitle', 'Configuration Clinique')

@push('styles')
<style>
    .questionnaire-page {
        width: 100%;
        max-width: 1320px;
        margin: 0 auto;
    }

    .questionnaire-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        border-radius: 14px;
    }

    .table {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    .table thead {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    }

    .table thead th {
        border: none;
        font-weight: 600;
        color: #475569;
        padding: 14px 16px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0;
        white-space: nowrap;
    }

    .table tbody td {
        border-top: 1px solid #e2e8f0;
        border-left: 0;
        border-right: 0;
        border-bottom: 0;
        padding: 14px 16px;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    .questionnaire-name {
        font-weight: 600;
        color: #0f172a;
        font-size: 14px;
    }

    .questionnaire-desc {
        font-size: 13px;
        color: #64748b;
        margin-top: 4px;
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
        gap: 6px;
        flex-wrap: nowrap;
    }

    .action-buttons form {
        display: inline-flex;
        margin: 0;
    }

    .btn-icon {
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        color: #64748b;
    }

    .btn-icon:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    .btn-icon.danger:hover {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #dc2626;
    }

    .header-section {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 22px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .btn-primary-modern {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .btn-primary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.4);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .empty-icon {
        font-size: 64px;
        color: #cbd5e1;
        margin-bottom: 16px;
    }

    .empty-title {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 8px;
    }

    .empty-text {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 24px;
    }

    .fields-count {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        background: #f1f5f9;
        border-radius: 20px;
        font-size: 12px;
        color: #475569;
        font-weight: 500;
    }

    .fields-count i {
        font-size: 12px;
    }

    .search-box {
        display: flex;
        gap: 8px;
    }

    .search-box input {
        padding: 10px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        width: 250px;
    }

    .search-box input::placeholder {
        color: #94a3b8;
    }

    .search-box input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .filter-pills {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .filter-pill {
        padding: 8px 16px;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 20px;
        cursor: pointer;
        font-size: 13px;
        color: #64748b;
        transition: all 0.2s;
    }

    .filter-pill:hover,
    .filter-pill.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border-color: transparent;
    }

    .responses-count {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 13px;
        color: #64748b;
    }

    .responses-count i {
        color: #3b82f6;
    }

    @media (max-width: 768px) {
        .questionnaire-page {
            max-width: none;
        }

        .header-section {
            align-items: stretch;
        }

        .header-section > div:last-child,
        .btn-primary-modern {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

@section('content')
<div class="questionnaire-page">
    <!-- Messages -->
    @if ($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 12px; padding: 16px; margin-bottom: 20px; color: #991b1b;">
            <strong>Erreurs:</strong>
            <ul style="margin: 8px 0 0 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div style="background: #dcfce7; border: 1px solid #86efac; border-radius: 12px; padding: 16px; margin-bottom: 20px; color: #166534;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 12px; padding: 16px; margin-bottom: 20px; color: #991b1b;">
            {{ session('error') }}
        </div>
    @endif

    <!-- Header Section -->
    <div class="header-section">
        <div>
            <h2 style="font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">
                Modèles de Questionnaires
            </h2>
            <p style="font-size: 13px; color: #64748b;">
                {{ $questionnaires->total() }} modèle{{ $questionnaires->total() > 1 ? 's' : '' }} configuré{{ $questionnaires->total() > 1 ? 's' : '' }}
            </p>
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="{{ route('clinical.questionnaire-templates.create') }}" class="btn-primary-modern">
                <i class="ti ti-plus"></i>
                Créer un Modèle
            </a>
            <button type="button" class="btn-icon" onclick="document.getElementById('importFile').click()" title="Importer">
                <i class="ti ti-upload"></i>
            </button>
            <form id="importForm" method="POST" action="{{ route('clinical.questionnaire-templates.import') }}" style="display: none;" enctype="multipart/form-data">
                @csrf
                <input type="file" id="importFile" name="file" accept=".json" onchange="document.getElementById('importForm').submit()">
            </form>
        </div>
    </div>

    <!-- Table -->
    @if ($questionnaires->count() > 0)
        <div class="questionnaire-panel table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom du Modèle</th>
                        <th>Spécialité</th>
                        <th>Responsable</th>
                        <th>Champs</th>
                        <th>Réponses</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($questionnaires as $questionnaire)
                        <tr>
                            <td>
                                <div class="questionnaire-name">{{ $questionnaire->name }}</div>
                                @if ($questionnaire->description)
                                    <div class="questionnaire-desc">{{ Str::limit($questionnaire->description, 60) }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($questionnaire->specialty)
                                    <span class="badge-modern badge-specialty">{{ $questionnaire->specialty->name }}</span>
                                @else
                                    <span style="font-size: 13px; color: #94a3b8;">Tous</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 13px; color: #475569;">
                                    {{ $questionnaire->creator?->name ?? 'Système' }}
                                </div>
                            </td>
                            <td>
                                <span class="fields-count">
                                    <i class="ti ti-list-check"></i>
                                    {{ count($questionnaire->field_schema ?? []) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $responseCount = $questionnaire->responses()->count();
                                @endphp
                                <span class="responses-count">
                                    <i class="ti ti-clipboard-list"></i>
                                    {{ $responseCount }}
                                </span>
                            </td>
                            <td>
                                <span class="badge-modern {{ $questionnaire->is_active ? 'badge-active' : 'badge-inactive' }}">
                                    {{ $questionnaire->is_active ? '✓ Actif' : '✕ Inactif' }}
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('clinical.questionnaire-templates.show', $questionnaire->id) }}" 
                                       class="btn-icon" title="Voir">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <a href="{{ route('clinical.questionnaire-templates.edit', $questionnaire->id) }}" 
                                       class="btn-icon" title="Éditer">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('clinical.questionnaire-templates.duplicate', $questionnaire->id) }}"
                                          onsubmit="return confirm('Dupliquer ce modèle ?');">
                                        @csrf
                                        <button type="submit" class="btn-icon" title="Dupliquer">
                                            <i class="ti ti-copy"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('clinical.questionnaire-templates.export', $questionnaire->id) }}" 
                                       class="btn-icon" title="Exporter">
                                        <i class="ti ti-download"></i>
                                    </a>
                                    @if ($responseCount === 0)
                                        <form method="POST" action="{{ route('clinical.questionnaire-templates.destroy', $questionnaire->id) }}" 
                                              onsubmit="return confirm('Supprimer ce modèle ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon danger" title="Supprimer">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn-icon" title="Suppression impossible (réponses existantes)" disabled style="opacity: 0.5; cursor: not-allowed;">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div style="margin-top: 24px;">
            {{ $questionnaires->links('pagination::bootstrap-4') }}
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">
                <i class="ti ti-clipboard-list"></i>
            </div>
            <div class="empty-title">Aucun modèle de questionnaire</div>
            <div class="empty-text">
                Créez votre premier modèle de questionnaire pour l'utiliser dans les dossiers patients.
            </div>
            <a href="{{ route('clinical.questionnaire-templates.create') }}" class="btn-primary-modern">
                <i class="ti ti-plus"></i>
                Créer un Modèle
            </a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[onclick*="toggleActive"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            // Implementation de l'activation/désactivation en AJAX
        });
    });
</script>
@endpush
