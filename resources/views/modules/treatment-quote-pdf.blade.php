<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Devis {{ $quote->quote_number }}</title>
    <style>
        body{font-family:DejaVu Sans, Arial, sans-serif;font-size:12px;color:#0f172a}
        .head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #cbd5e1;padding-bottom:10px;margin-bottom:10px}
        .title{font-size:20px;font-weight:bold}
        table{width:100%;border-collapse:collapse;margin-top:10px}
        th,td{border:1px solid #cbd5e1;padding:6px;text-align:left}
        th{background:#f1f5f9}
        .right{text-align:right}
        .totals{margin-top:12px;width:320px;margin-left:auto}
        .totals td{border:none;padding:4px 0}
        .sig{margin-top:24px;border-top:1px dashed #94a3b8;padding-top:10px}
    </style>
</head>
<body>
<div class="head">
    <div>
        <div class="title">Devis Conventionne</div>
        <div>Numero: {{ $quote->quote_number }}</div>
        <div>Date: {{ optional($quote->quote_date)->format('d/m/Y') }}</div>
        <div>Validite: {{ optional($quote->valid_until)->format('d/m/Y') }}</div>
    </div>
    <div>
        <div><strong>Patient:</strong> {{ $patient?->full_name }}</div>
        <div><strong>MRN:</strong> {{ $patient?->medical_record_number }}</div>
        <div><strong>Praticien:</strong> {{ $practitioner?->name ?? '-' }}</div>
    </div>
</div>

<table>
    <thead>
    <tr>
        <th>Phase</th>
        <th>Code</th>
        <th>Acte</th>
        <th class="right">Prix</th>
        <th class="right">Part assurance</th>
        <th class="right">Part patient</th>
    </tr>
    </thead>
    <tbody>
    @foreach($items as $item)
        <tr>
            <td>{{ $item->phase_number }}</td>
            <td>{{ $item->code }}</td>
            <td>{{ $item->label }}</td>
            <td class="right">{{ number_format((float) $item->total_price, 2, ',', ' ') }}</td>
            <td class="right">{{ number_format((float) $item->insurance_share, 2, ',', ' ') }}</td>
            <td class="right">{{ number_format((float) $item->patient_share, 2, ',', ' ') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td>Sous-total</td><td class="right">{{ number_format((float) $quote->subtotal, 2, ',', ' ') }} MAD</td></tr>
    <tr><td>Part assurance</td><td class="right">{{ number_format((float) $quote->insurance_amount, 2, ',', ' ') }} MAD</td></tr>
    <tr><td>Part mutuelle</td><td class="right">{{ number_format((float) $quote->mutual_amount, 2, ',', ' ') }} MAD</td></tr>
    <tr><td><strong>Reste patient</strong></td><td class="right"><strong>{{ number_format((float) $quote->patient_amount, 2, ',', ' ') }} MAD</strong></td></tr>
</table>

<div class="sig">
    <div><strong>Consentement eclaire:</strong> {{ $quote->consent_status }}</div>
    @if($quote->signed_at)
        <div>Signe le {{ $quote->signed_at->format('d/m/Y H:i') }} par {{ $quote->signed_by_patient_name }}</div>
    @else
        <div>Signature en attente.</div>
    @endif
</div>
</body>
</html>
