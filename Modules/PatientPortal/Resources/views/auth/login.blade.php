@extends('patient_portal::layouts.app')

@section('title', 'Connexion patient')

@section('content')
@php
    $patientName = $access?->patient?->full_name ?? 'Patient';
    $examLabel = $access?->order?->procedure?->label ?? 'Résultat d’imagerie';
    $portalUrl = $entryToken !== '' ? route('patient-portal.entry', ['token' => $entryToken]) : route('patient-portal.login');
@endphp

<div class="portal-grid">
    <section class="portal-content">
        <div class="portal-section">
            <div class="portal-eyebrow">Portail patient sécurisé</div>
            <h1 class="portal-h1">Accédez à vos résultats d’imagerie depuis chez vous.</h1>
            <p class="portal-subtitle">Identifiez-vous avec votre numéro de dossier, votre code d’accès et votre date de naissance pour ouvrir vos comptes-rendus et vos images en toute sécurité.</p>
        </div>

        <div class="portal-section portal-grid-2">
            <div class="portal-stat">
                <div class="portal-stat-label">Patient attendu</div>
                <div class="portal-stat-value">{{ $patientName }}</div>
            </div>
            <div class="portal-stat">
                <div class="portal-stat-label">Examen associé</div>
                <div class="portal-stat-value">{{ $examLabel }}</div>
            </div>
        </div>

        <div class="portal-section portal-panel">
            <h2 style="margin:0 0 14px; font-size:1.12rem;">Connexion sécurisée</h2>
            <form class="portal-form" method="POST" action="{{ route('patient-portal.authenticate') }}">
                @csrf
                <input type="hidden" name="entry_token" value="{{ old('entry_token', $entryToken) }}">

                <div>
                    <label class="portal-label" for="medical_record_number">Numéro de dossier (MRN)</label>
                    <input class="portal-input" id="medical_record_number" name="medical_record_number" value="{{ old('medical_record_number') }}" placeholder="MRN-2026-0001" required>
                </div>

                <div>
                    <label class="portal-label" for="date_of_birth">Date de naissance</label>
                    <input class="portal-input" id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required>
                </div>

                <div>
                    <label class="portal-label" for="access_code">Code d’accès unique</label>
                    <input class="portal-input" id="access_code" name="access_code" value="{{ old('access_code') }}" placeholder="AB12CD34" required autocomplete="one-time-code">
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <button type="submit" class="portal-btn">Accéder à mes résultats</button>
                    <a href="{{ route('patient-portal.login') }}" class="portal-btn portal-btn-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
    </section>

    <aside class="portal-side">
        <div class="portal-section portal-qrcode">
            <div class="portal-eyebrow">Lien de connexion</div>
            <div class="portal-panel" style="width:100%;">
                @if($access)
                    <div style="font-weight:900; font-size:1.05rem; margin-bottom:6px;">{{ $patientName }}</div>
                    <div class="portal-footer-note">Votre lien personnel est prêt. Le QR code peut être scanné sur la fiche remise au patient.</div>
                    @if(class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class))
                        <div style="margin-top:14px;">{!! app(\Modules\PatientPortal\Services\PatientPortalAccessService::class)->buildLoginQrSvg($access) !!}</div>
                    @endif
                    <div class="portal-footer-note" style="margin-top:14px; word-break:break-all;">{{ $portalUrl }}</div>
                @else
                    <div class="portal-footer-note">Saisissez vos informations pour ouvrir vos résultats. Si vous avez reçu un SMS ou un email, utilisez le code transmis par l’établissement.</div>
                @endif
            </div>
        </div>

        <div class="portal-section portal-panel">
            <div class="portal-eyebrow">Rappel de sécurité</div>
            <ul class="portal-list">
                <li>Le code d’accès est unique pour votre examen.</li>
                <li>Le lien est temporaire et peut expirer.</li>
                <li>Ne partagez pas votre code avec un tiers.</li>
            </ul>
        </div>
    </aside>
</div>
@endsection
