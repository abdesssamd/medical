<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ordonnance {{ $prescription->prescription_number }}</title>
    <style>
        @page { size: A5 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        body{margin:0;font-family:DejaVu Sans, Arial, sans-serif;font-size:11px;color:#0f172a;background:#fff}
        .sheet{min-height:100%;border:1px solid #dbeafe;border-radius:14px;padding:14px;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%)}
        .header{display:grid;grid-template-columns:auto 1fr auto;gap:12px;align-items:center;padding-bottom:12px;border-bottom:1px solid #dbeafe}
        .logo{width:46px;height:46px;border-radius:16px;display:grid;place-items:center;background:linear-gradient(135deg,#2563eb,#60a5fa);color:#fff;font-weight:900;letter-spacing:.08em}
        .cabinet{font-size:10px;line-height:1.45}
        .cabinet strong{display:block;font-size:12px;color:#0f172a}
        .title-block{text-align:right}
        .title{font-size:20px;font-weight:900;letter-spacing:.02em;color:#1d4ed8}
        .ref{font-size:11px;font-weight:700;color:#334155}
        .meta{font-size:10px;color:#64748b}
        .patient{margin-top:12px;padding:10px 12px;border:1px solid #dbeafe;border-radius:12px;background:#eff6ff;display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
        .patient .label{display:block;font-size:9px;text-transform:uppercase;letter-spacing:.12em;font-weight:800;color:#3b82f6}
        .patient .name{font-size:13px;font-weight:900;color:#0f172a}
        .patient .meta-line{font-size:10px;color:#334155}
        .paper-list{margin-top:12px;display:grid;gap:8px}
        .item{padding:10px 12px;border:1px solid #dbeafe;border-radius:12px;background:#fff}
        .item-head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;margin-bottom:4px}
        .item-name{font-weight:900;color:#0f172a}
        .badges{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
        .badge{padding:3px 7px;border-radius:999px;background:#dbeafe;color:#1d4ed8;font-size:9px;font-weight:800}
        .line{font-size:10px;color:#334155;line-height:1.45}
        .section-title{margin-top:12px;font-size:9px;font-weight:900;letter-spacing:.16em;text-transform:uppercase;color:#64748b}
        .footer{margin-top:14px;padding-top:12px;border-top:1px solid #dbeafe;display:flex;justify-content:space-between;gap:10px;align-items:flex-end}
        .notes{font-size:9px;color:#64748b;max-width:64%}
        .sig{min-width:150px;text-align:right}
        .sig-box{height:34px;border-bottom:1px dashed #94a3b8}
        .sig-label{font-size:9px;color:#64748b}
        .hidden-print{display:none}
        ul{margin:6px 0 0 14px;padding:0}
        @media print {
            body{background:#fff}
            .sheet{border:none;border-radius:0;box-shadow:none}
        }
    </style>
</head>
<body>
<div class="sheet">
    <div class="header">
        <div class="logo">MO</div>
        <div class="cabinet">
            <strong>Cabinet Dentaire MediOffice</strong>
            <div>Ordonnance medicale optimisee pour lecture rapide et impression A5.</div>
            <div class="hidden-print">Adresse et antécédents masqués par défaut.</div>
        </div>
        <div class="title-block">
            <div class="title">Ordonnance</div>
            <div class="ref">{{ $prescription->prescription_number }}</div>
            <div class="meta">{{ optional($issuedAt)->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="patient">
        <div>
            <span class="label">Patient</span>
            <div class="name">{{ $patient?->full_name }}</div>
            <div class="meta-line">MRN {{ $patient?->medical_record_number }} • {{ $patient?->age }} ans</div>
        </div>
        <div>
            <span class="label">Praticien</span>
            <div class="meta-line"><strong>{{ $practitioner?->name ?: '-' }}</strong></div>
        </div>
    </div>

    <div class="section-title">Prescription</div>
    <div class="paper-list">
        @foreach($items as $item)
            <article class="item">
                <div class="item-head">
                    <div class="item-name">{{ $loop->iteration }}. {{ $item->medication_name }}</div>
                    <div class="badges">
                        <span class="badge">{{ $item->dosage ?: '-' }}</span>
                        <span class="badge">{{ $item->duration_days ? $item->duration_days.' j' : '-' }}</span>
                    </div>
                </div>
                <div class="line"><strong>Forme:</strong> {{ $item->unit ?: '-' }} • <strong>Fréquence:</strong> {{ $item->frequency ?: '-' }}</div>
                <div class="line"><strong>QSP:</strong> {{ $item->instructions ?: '-' }}</div>
            </article>
        @endforeach
    </div>

    @if(!empty(($prescription->safety_alerts['warnings'] ?? [])))
        <div class="section-title">Surveillance</div>
        <div class="item">
            <strong>Alertes de surveillance</strong>
            <ul>
                @foreach(($prescription->safety_alerts['warnings'] ?? []) as $w)
                    <li>{{ $w }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="footer">
        <div class="notes">
            <div><strong>QR traçabilité:</strong> {{ $verifyUrl }}</div>
            <div>Les champs Adresse, Notes et Antécédents ne sont pas imprimés par défaut.</div>
        </div>
        <div class="sig">
            <div class="sig-box">
                @if(!empty($prescription->signature_payload['data_url']))
                    <img src="{{ $prescription->signature_payload['data_url'] }}" alt="Signature" style="max-height:32px;max-width:140px;">
                @endif
            </div>
            <div class="sig-label">Signature praticien</div>
        </div>
    </div>
</div>
</body>
</html>
