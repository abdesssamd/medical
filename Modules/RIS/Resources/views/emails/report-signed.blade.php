<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; line-height: 1.55; }
        .card { max-width: 720px; margin: 0 auto; padding: 24px; border: 1px solid #dbe8f3; border-radius: 18px; }
        .title { font-size: 22px; font-weight: 700; margin: 0 0 10px; }
        .muted { color: #64748b; }
        .box { margin: 16px 0; padding: 14px 16px; background: #f8fbff; border: 1px solid #dbe8f3; border-radius: 14px; }
        .btn { display: inline-block; padding: 11px 16px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 10px; font-weight: 700; }
        .meta { margin-top: 10px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="card">
        <p class="muted">Bonjour {{ $recipientLabel }},</p>
        <h1 class="title">Votre compte rendu RIS est signé</h1>
        <p>Le compte rendu de l'examen <strong>{{ $order->procedure?->label ?? 'Examen RIS' }}</strong> a été validé et signé.</p>

        <div class="box">
            <div><strong>Patient:</strong> {{ $order->patient?->full_name ?? 'Patient inconnu' }}</div>
            <div><strong>Accession:</strong> {{ $order->accession_number ?: 'RIS-'.$order->id }}</div>
            <div><strong>Signé par:</strong> {{ $order->report?->signing_physician_name ?: $order->report?->signingPhysician?->display_name ?: 'Praticien non renseigné' }}</div>
            <div><strong>Date:</strong> {{ optional($order->report?->validated_at)->format('d/m/Y H:i') ?: now()->format('d/m/Y H:i') }}</div>
        </div>

        @if($shareUrl)
            <p><a class="btn" href="{{ $shareUrl }}" target="_blank" rel="noopener">Ouvrir le compte rendu sécurisé</a></p>
            <p class="meta muted">Un PDF est joint à ce message si disponible.</p>
        @else
            <p class="meta muted">Le lien sécurisé n'a pas encore été généré.</p>
        @endif

        <p class="muted">Message généré automatiquement par MediOffice RIS.</p>
    </div>
</body>
</html>