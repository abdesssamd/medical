<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etiquettes Lot {{ $batch->batch_code }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 16px; color: #0f172a; }
        .header { margin-bottom: 16px; }
        .header h1 { margin: 0 0 6px; font-size: 20px; }
        .header p { margin: 0; color: #475569; }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(220px, 1fr)); gap: 12px; }
        .label { border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px; page-break-inside: avoid; }
        .code { font-size: 14px; font-weight: 700; margin-bottom: 4px; }
        .meta { font-size: 12px; color: #334155; margin-bottom: 8px; }
        .qr { width: 120px; height: 120px; display: block; border: 1px solid #e2e8f0; }
        .status { margin-top: 8px; font-size: 12px; font-weight: 700; }
        .actions { margin-bottom: 14px; }
        @media print {
            .actions { display: none; }
            body { margin: 10px; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Imprimer les étiquettes</button>
    </div>

    <div class="header">
        <h1>Etiquettes de sterilisation - Lot {{ $batch->batch_code }}</h1>
        <p>Cycle: {{ $batch->sterilizer_cycle ?: '-' }} | Statut: {{ $batch->status }} | Expiration: {{ optional($batch->expires_at)->format('d/m/Y H:i') ?: '-' }}</p>
    </div>

    <section class="grid">
        @foreach($batch->pouches as $pouch)
            @php($qrData = urlencode($pouch->pouch_code))
            <article class="label">
                <div class="code">{{ $pouch->pouch_code }}</div>
                <div class="meta">Lot {{ $batch->batch_code }} | {{ $pouch->instrument_set_name ?: 'Set standard' }}</div>
                <img class="qr" src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ $qrData }}" alt="QR {{ $pouch->pouch_code }}">
                <div class="status">{{ strtoupper($pouch->status) }}</div>
            </article>
        @endforeach
    </section>
</body>
</html>
