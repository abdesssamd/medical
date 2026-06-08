<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Compte rendu RIS {{ $order->accession_number }}</title>
    <style>
        @page { margin: 26px 28px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #122033; font-size: 12px; line-height: 1.55; }
        .sheet { width: 100%; }
        .header { border-bottom: 1px solid #cfdceb; padding-bottom: 16px; margin-bottom: 22px; }
        .brand { font-size: 11px; text-transform: uppercase; letter-spacing: 0.12em; color: #6b7c93; font-weight: 700; }
        .title-row { width: 100%; border-collapse: collapse; }
        .title-row td { vertical-align: top; }
        .title { font-size: 24px; font-weight: 700; color: #102c57; margin: 6px 0 4px; }
        .subtitle { color: #5a6b83; font-size: 12px; }
        .badge { display: inline-block; padding: 4px 9px; border-radius: 999px; background: #e8f1fb; color: #24508f; font-size: 10px; font-weight: 700; }
        .qr-box { width: 130px; text-align: center; }
        .qr-shell { border: 1px solid #d9e6f2; border-radius: 14px; padding: 8px; background: #f8fbfe; }
        .qr-caption { margin-top: 6px; color: #6b7c93; font-size: 9px; line-height: 1.35; word-break: break-all; }
        .section { margin-bottom: 18px; }
        .section-title { color: #6b7c93; font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; margin-bottom: 8px; }
        .grid { width: 100%; border-collapse: separate; border-spacing: 8px; margin: 0 -8px 14px; }
        .grid td { width: 50%; vertical-align: top; border: 1px solid #d9e6f2; border-radius: 12px; padding: 11px 12px; background: #fbfdff; }
        .label { color: #6b7c93; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 3px; }
        .value { color: #122033; font-size: 13px; font-weight: 700; }
        .muted { color: #5f7087; }
        .report-box { border: 1px solid #d9e6f2; border-radius: 14px; padding: 16px; min-height: 240px; background: #ffffff; white-space: normal; }
        .clinical-box { border: 1px solid #d9e6f2; border-radius: 14px; padding: 14px 16px; background: #fbfdff; }
        .sign-row { width: 100%; border-collapse: collapse; margin-top: 26px; }
        .sign-row td { vertical-align: top; }
        .sign-card { border-top: 1px solid #d9e6f2; padding-top: 14px; }
        .signature-name { font-size: 14px; font-weight: 700; color: #102c57; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #e3edf7; font-size: 10px; color: #71839b; }
    </style>
</head>
<body>
    @php
        $reportContent = $order->report?->content ?: null;
        $renderedReportContent = $reportContent
            ? (preg_match('/<\s*[a-z][\s\S]*>/i', $reportContent) ? $reportContent : nl2br(e($reportContent)))
            : null;
    @endphp
    <div class="sheet">
        <div class="header">
            <table class="title-row">
                <tr>
                    <td>
                        <div class="brand">MediOffice Radiologie</div>
                        <div class="title">Compte rendu radiologique</div>
                        <div class="subtitle">
                            Accession {{ $order->accession_number ?: 'RIS-'.$order->id }}
                            @if($order->status)
                                <span class="badge">{{ strtoupper((string) $order->status_label) }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="qr-box">
                        @if(!empty($qrCodeSvg))
                            <div class="qr-shell">{!! $qrCodeSvg !!}</div>
                            <div class="qr-caption">Acces public securise</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Informations patient et examen</div>
            <table class="grid">
                <tr>
                    <td>
                        <div class="label">Patient</div>
                        <div class="value">{{ $order->patient?->full_name ?? 'Patient inconnu' }}</div>
                        <div class="muted">MRN {{ $order->patient?->medical_record_number ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="label">Acte</div>
                        <div class="value">{{ $order->procedure?->label ?? 'Examen RIS' }}</div>
                        <div class="muted">{{ $order->modality?->name ?? 'Modalite non definie' }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="label">Demande</div>
                        <div class="value">{{ optional($order->requested_at)->format('d/m/Y H:i') ?: '-' }}</div>
                        <div class="muted">{{ $order->requestedBy?->display_name ?? 'Demandeur non renseigne' }}</div>
                    </td>
                    <td>
                        <div class="label">Acquisition / reception</div>
                        <div class="value">{{ optional($order->received_at)->format('d/m/Y H:i') ?: '-' }}</div>
                        <div class="muted">Validation {{ optional($order->report?->validated_at)->format('d/m/Y H:i') ?: '-' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Indication clinique</div>
            <div class="clinical-box">{{ $order->clinical_indication ?: 'Aucune indication clinique saisie.' }}</div>
        </div>

        <div class="section">
            <div class="section-title">Compte rendu</div>
            <div class="report-box">{!! $renderedReportContent ?: 'Compte rendu non renseigne.' !!}</div>
        </div>

        <table class="sign-row">
            <tr>
                <td>
                    @if($publicExamUrl)
                        <div class="section-title">Lien public securise</div>
                        <div class="muted">{{ $publicExamUrl }}</div>
                    @endif
                </td>
                <td style="width: 220px; text-align: right;">
                    <div class="sign-card">
                        <div class="muted">Signe le {{ optional($order->report?->validated_at)->format('d/m/Y H:i') ?: '-' }}</div>
                        <div class="signature-name">{{ $order->report?->signing_physician_name ?: $order->report?->signingPhysician?->display_name ?: 'Praticien non renseigne' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer">
            Document genere par le module RIS MediOffice.
        </div>
    </div>
</body>
</html>
