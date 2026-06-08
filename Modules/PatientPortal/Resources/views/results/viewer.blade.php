@extends('patient_portal::layouts.app')

@section('title', 'Viewer images')

@section('content')
<div class="portal-content" style="width:100%;">
    <div class="portal-section">
        <div class="portal-eyebrow">Viewer imagerie</div>
        <h1 class="portal-h1" style="font-size:1.9rem;">Visualisation légère des images</h1>
        <p class="portal-subtitle">Cette page ouvre le viewer allégé lié à votre examen. Utilisez le mode plein écran du navigateur pour un meilleur confort.</p>
    </div>

    @if($viewerUrl)
        <div class="portal-panel" style="padding:12px; margin-top:16px;">
            <iframe class="portal-viewer" src="{{ $viewerUrl }}" title="Viewer DICOM"></iframe>
        </div>
    @else
        <div class="portal-panel" style="margin-top:16px;">
            <div class="portal-footer-note">Aucun viewer disponible pour cet examen.</div>
        </div>
    @endif
</div>
@endsection
