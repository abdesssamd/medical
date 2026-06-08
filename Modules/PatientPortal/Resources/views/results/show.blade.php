@extends('patient_portal::layouts.app')

@section('title', 'Résultats patient')

@section('content')
@php
    $patientName = $patient?->full_name ?? 'Patient';
    $examLabel = $order?->procedure?->label ?? 'Examen';
    $viewerAvailable = filled($viewerUrl);
@endphp

<div class="portal-grid">
    <section class="portal-content">
        <div class="portal-section">
            <div class="portal-eyebrow">Résultats d’imagerie</div>
            <h1 class="portal-h1">{{ $patientName }}</h1>
            <p class="portal-subtitle">Visualisez votre compte-rendu et, si disponible, vos images DICOM dans un viewer allégé.</p>
        </div>

        <div class="portal-section portal-grid-2">
            <div class="portal-stat">
                <div class="portal-stat-label">Examen</div>
                <div class="portal-stat-value">{{ $examLabel }}</div>
            </div>
            <div class="portal-stat">
                <div class="portal-stat-label">Date</div>
                <div class="portal-stat-value">{{ optional($order?->requested_at)->format('d/m/Y H:i') ?: '-' }}</div>
            </div>
        </div>

        <div class="portal-section portal-panel">
            <div style="display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; align-items:center; margin-bottom:16px;">
                <div>
                    <div class="portal-eyebrow">Compte-rendu PDF</div>
                    <div style="font-weight:900; font-size:1.05rem;">Lecture et téléchargement</div>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="{{ $pdfUrl }}" class="portal-btn">Télécharger PDF</a>
                    <a href="{{ route('patient-portal.dashboard') }}" class="portal-btn portal-btn-secondary">Retour</a>
                </div>
            </div>
            <iframe class="portal-viewer" src="{{ $pdfUrl }}" title="Compte-rendu PDF"></iframe>
        </div>
    </section>

    <aside class="portal-side">
        <div class="portal-section portal-panel">
            <div class="portal-eyebrow">Images</div>
            @if($viewerAvailable)
                <iframe class="portal-viewer" style="min-height: 480px;" src="{{ $viewerUrl }}" title="Viewer DICOM"></iframe>
            @else
                <div class="portal-footer-note">Les images ne sont pas encore publiées ou le viewer Orthanc n’est pas accessible.</div>
            @endif
        </div>

        <div class="portal-section portal-panel">
            <div class="portal-eyebrow">Téléchargement sécurisé</div>
            <div class="portal-footer-note">Le PDF est servi depuis votre session portail afin d’éviter une exposition directe de la base documentaire.</div>
        </div>
    </aside>
</div>
@endsection
