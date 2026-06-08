@extends('layouts.admin')

@section('title', 'Fiche mémo patient')
@section('page-title', 'Fiche mémo patient imprimable')

@section('content')
<style>
    .memo-shell { width:min(980px, 100%); margin:0 auto; }
    .memo-card { background:#fff; border:1px solid #dbe8f3; border-radius:28px; box-shadow:0 24px 60px rgba(15,23,42,0.1); overflow:hidden; }
    .memo-top { padding:20px 24px; display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap; background:linear-gradient(135deg,#0ea5e9,#38bdf8); color:#fff; }
    .memo-brand { display:grid; gap:4px; }
    .memo-brand strong { font-size:1.15rem; }
    .memo-brand span { opacity:.9; }
    .memo-body { padding:24px; display:grid; grid-template-columns: minmax(0, 1.05fr) minmax(280px, 0.95fr); gap:18px; }
    .memo-panel { border:1px solid #e2e8f0; border-radius:20px; padding:18px; background:#fbfdff; }
    .memo-label { color:#64748b; text-transform:uppercase; letter-spacing:.08em; font-size:.72rem; font-weight:900; margin-bottom:6px; }
    .memo-value { font-weight:800; font-size:1.02rem; }
    .memo-code { font-size:2rem; letter-spacing:.18em; font-weight:900; text-align:center; margin:10px 0; }
    .memo-qr { display:grid; place-items:center; gap:12px; }
    .memo-qr svg { width:220px; height:220px; background:#fff; padding:12px; border-radius:20px; border:1px solid #dbe8f3; }
    .memo-list { margin:0; padding-left:18px; line-height:1.7; color:#334155; }
    .memo-actions { display:flex; justify-content:flex-end; gap:10px; padding:0 24px 24px; }
    .memo-btn { display:inline-flex; align-items:center; justify-content:center; min-height:42px; border:1px solid #cbd5e1; border-radius:12px; padding:8px 14px; background:#fff; color:#0f172a; text-decoration:none; font-weight:800; cursor:pointer; }
    .memo-btn-primary { background:#2563eb; color:#fff; border-color:#2563eb; }
    @media print {
        body { background:#fff; }
        .sidebar-modern, .topbar, .memo-actions { display:none !important; }
        .main-content { margin:0 !important; padding:0 !important; }
        .memo-shell { width:100%; margin:0; }
        .memo-card { box-shadow:none; border:0; border-radius:0; }
    }
    @media (max-width: 900px) { .memo-body { grid-template-columns:1fr; } }
</style>

<div class="memo-shell">
    <div class="memo-card">
        <div class="memo-top">
            <div class="memo-brand">
                <strong>MediOffice Patient Portal</strong>
                <span>Fiche mémo d’accès aux résultats d’imagerie</span>
            </div>
            <div style="font-weight:800;">{{ optional($access->created_at)->format('d/m/Y H:i') }}</div>
        </div>

        <div class="memo-body">
            <div class="memo-panel">
                <div class="memo-label">Patient</div>
                <div class="memo-value">{{ $access->patient?->full_name ?? '-' }}</div>

                <div style="height:14px"></div>

                <div class="memo-label">MRN</div>
                <div class="memo-value">{{ $access->patient?->medical_record_number ?? '-' }}</div>

                <div style="height:14px"></div>

                <div class="memo-label">Date de naissance</div>
                <div class="memo-value">{{ optional($access->patient?->date_of_birth)->format('d/m/Y') ?: '-' }}</div>

                <div style="height:14px"></div>

                <div class="memo-label">Examen</div>
                <div class="memo-value">{{ $access->order?->procedure?->label ?? '-' }}</div>

                <div style="height:14px"></div>

                <div class="memo-label">Code d’accès unique</div>
                <div class="memo-code">{{ $printableCode ?? 'N/A' }}</div>

                <div class="memo-label">Lien portail</div>
                <div class="memo-value" style="word-break:break-all;">{{ $portalUrl }}</div>

                <div style="height:18px"></div>
                <div class="memo-label">Instructions</div>
                <ul class="memo-list">
                    <li>Connectez-vous avec MRN + code d’accès + date de naissance.</li>
                    <li>Le lien et le code sont personnels et temporaires.</li>
                    <li>Conservez cette fiche jusqu’à la récupération du résultat.</li>
                </ul>
            </div>

            <div class="memo-panel memo-qr">
                <div>
                    <div class="memo-label">QR code</div>
                    <div style="text-align:center; color:#64748b; line-height:1.6;">Scannez pour ouvrir directement le portail patient.</div>
                </div>
                @if($qrCodeSvg)
                    {!! $qrCodeSvg !!}
                @endif
                <div style="text-align:center; font-size:.88rem; color:#64748b; word-break:break-all;">{{ $portalUrl }}</div>
            </div>
        </div>

        <div class="memo-actions">
            <button type="button" class="memo-btn" onclick="window.print()">Imprimer</button>
            <a href="{{ route('patient-portal.admin.show', $access) }}" class="memo-btn memo-btn-primary">Retour</a>
        </div>
    </div>
</div>
@endsection
