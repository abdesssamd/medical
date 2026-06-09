@extends('patient_portal::layouts.app')

@section('title', 'Tableau de bord patient')

@section('content')
@php
    $patientName = $patient?->full_name ?? 'Patient';
    $examLabel = $order?->procedure?->label ?? 'Examen';
    $statusLabel = $order?->status_label ?? 'En attente';
    $viewerAvailable = filled($viewerUrl);
@endphp

<div class="portal-grid">
    <section class="portal-content">
        <div class="portal-section">
            <div class="portal-eyebrow">Bonjour {{ $patientName }}</div>
            <h1 class="portal-h1">Vos résultats sont disponibles dans votre espace sécurisé.</h1>
            <p class="portal-subtitle">Consultez le résumé de votre examen, téléchargez le compte-rendu PDF et ouvrez le viewer si vos images sont déjà publiées par le serveur d’imagerie.</p>
        </div>

        <div class="portal-section portal-grid-2">
            <div class="portal-stat">
                <div class="portal-stat-label">Examen</div>
                <div class="portal-stat-value">{{ $examLabel }}</div>
            </div>
            <div class="portal-stat">
                <div class="portal-stat-label">Statut</div>
                <div class="portal-stat-value">{{ $statusLabel }}</div>
            </div>
            <div class="portal-stat">
                <div class="portal-stat-label">Date de l’examen</div>
                <div class="portal-stat-value">{{ optional($order?->requested_at)->format('d/m/Y H:i') ?: 'Non renseignée' }}</div>
            </div>
            <div class="portal-stat">
                <div class="portal-stat-label">Accès sécurisé</div>
                <div class="portal-stat-value">Actif</div>
            </div>
        </div>

        <div class="portal-section portal-panel">
            <div style="display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; align-items:flex-start;">
                <div>
                    <div class="portal-eyebrow">Compte-rendu</div>
                    <div style="font-weight:900; font-size:1.08rem; margin-bottom:8px;">Résumé du résultat</div>
                    <div class="portal-footer-note">Le PDF contient le compte-rendu validé par le radiologue. Vous pouvez le télécharger ou l’ouvrir en lecture.</div>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="{{ route('patient-portal.results.pdf') }}" class="portal-btn">Télécharger PDF</a>
                    <a href="{{ route('patient-portal.results.show') }}" class="portal-btn portal-btn-secondary">Voir le détail</a>
                    <form method="POST" action="{{ route('patient-portal.logout') }}">
                        @csrf
                        <button type="submit" class="portal-btn portal-btn-danger">Quitter</button>
                    </form>
                </div>
            </div>
        </div>

        @if($viewerAvailable)
            <div class="portal-section portal-panel" style="margin-top:16px;">
                <div class="portal-eyebrow">Images DICOM</div>
                <div style="font-weight:900; font-size:1.08rem; margin-bottom:12px;">Visualisation des images de votre examen</div>
                <div style="border:1px solid #dbe8f3; border-radius:18px; overflow:hidden; background:#f8fafc;">
                    <iframe src="{{ $viewerUrl }}" style="width:100%; height:560px; border:0;" allowfullscreen title="Viewer DICOM"></iframe>
                </div>
                <div class="portal-footer-note" style="margin-top:10px;">
                    <a href="{{ route('patient-portal.results.viewer') }}" target="_blank">Ouvrir en plein écran &rarr;</a>
                </div>
            </div>
        @endif

        <div class="portal-section portal-panel">
            <div class="portal-eyebrow">Conseils</div>
            <ul class="portal-list">
                <li>Conservez votre code d'accès jusqu'à la récupération de votre compte-rendu.</li>
                <li>Le PDF et les images proviennent du module RIS MediOffice.</li>
                <li>En cas de difficulté, contactez le secrétariat ou le service radiologie.</li>
            </ul>
        </div>
    </section>

    <aside class="portal-side">
        <div class="portal-section portal-panel">
            <div class="portal-eyebrow">Détail patient</div>
            <div class="portal-stat" style="margin-bottom:12px;">
                <div class="portal-stat-label">Nom</div>
                <div class="portal-stat-value">{{ $patientName }}</div>
            </div>
            <div class="portal-stat" style="margin-bottom:12px;">
                <div class="portal-stat-label">MRN</div>
                <div class="portal-stat-value">{{ $patient?->medical_record_number ?? '-' }}</div>
            </div>
            <div class="portal-stat" style="margin-bottom:12px;">
                <div class="portal-stat-label">Date de naissance</div>
                <div class="portal-stat-value">{{ optional($patient?->date_of_birth)->format('d/m/Y') ?: '-' }}</div>
            </div>
            <div class="portal-stat">
                <div class="portal-stat-label">Téléphone</div>
                <div class="portal-stat-value">{{ $patient?->phone ?? '-' }}</div>
            </div>
        </div>

        @if($viewerAvailable)
            <div class="portal-section portal-panel">
                <div class="portal-eyebrow">Images DICOM</div>
                <div class="portal-footer-note">Les images sont visibles directement dans le tableau de bord ci-contre.</div>
                <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="{{ route('patient-portal.results.viewer') }}" class="portal-btn">Plein écran</a>
                </div>
            </div>
        @else
            <div class="portal-section portal-panel">
                <div class="portal-eyebrow">Images</div>
                <div class="portal-footer-note">Le viewer n'est pas encore disponible pour cet examen.</div>
                <div style="margin-top:14px;">
                    <a href="{{ route('patient-portal.results.show') }}" class="portal-btn portal-btn-secondary">Voir le compte-rendu</a>
                </div>
            </div>
        @endif
    </aside>
</div>
@endsection
