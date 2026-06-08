<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accès à vos résultats MediOffice</title>
</head>
<body style="margin:0; padding:0; background:#f4f8fc; font-family:Inter,Segoe UI,Arial,sans-serif; color:#0f172a;">
    <div style="max-width:720px; margin:0 auto; padding:24px;">
        <div style="background:#fff; border:1px solid #dbe8f3; border-radius:24px; overflow:hidden; box-shadow:0 18px 40px rgba(15,23,42,0.08);">
            <div style="padding:24px; background:linear-gradient(135deg,#0ea5e9,#38bdf8); color:#fff;">
                <div style="font-size:12px; text-transform:uppercase; letter-spacing:.14em; font-weight:900; opacity:.9;">MediOffice Patient Portal</div>
                <h1 style="margin:8px 0 0; font-size:28px; line-height:1.1;">Vos résultats sont disponibles</h1>
            </div>

            <div style="padding:24px;">
                <p style="margin:0 0 16px; font-size:16px; line-height:1.7;">Bonjour {{ $access->patient?->full_name ?? 'Patient' }},</p>
                <p style="margin:0 0 16px; font-size:15px; line-height:1.7; color:#475569;">Votre examen <strong>{{ $access->order?->procedure?->label ?? 'd’imagerie' }}</strong> a été validé. Vous pouvez consulter vos résultats via le lien sécurisé ci-dessous.</p>

                <div style="background:#f8fbff; border:1px solid #dbe8f3; border-radius:18px; padding:18px; margin:20px 0;">
                    <div style="font-size:12px; text-transform:uppercase; letter-spacing:.08em; color:#64748b; font-weight:900; margin-bottom:8px;">Code d’accès unique</div>
                    <div style="font-size:26px; font-weight:900; letter-spacing:.14em; margin-bottom:10px;">{{ $plainCode }}</div>
                    <div style="font-size:14px; color:#64748b; line-height:1.6;">MRN: {{ $access->patient?->medical_record_number ?? '-' }}<br>Date de naissance: {{ optional($access->patient?->date_of_birth)->format('d/m/Y') ?: '-' }}</div>
                </div>

                <div style="text-align:center; margin:24px 0;">
                    @if($qrCodeSvg)
                        {!! $qrCodeSvg !!}
                    @endif
                </div>

                <p style="margin:0 0 14px; font-size:15px; line-height:1.7;">Lien portail: <a href="{{ $portalUrl }}" style="color:#0ea5e9; font-weight:700;">{{ $portalUrl }}</a></p>
                <p style="margin:0; font-size:14px; line-height:1.7; color:#64748b;">Ce code est temporaire et strictement personnel. Pour accéder au portail, saisissez votre numéro de dossier, votre date de naissance et le code transmis.</p>
            </div>
        </div>
    </div>
</body>
</html>
