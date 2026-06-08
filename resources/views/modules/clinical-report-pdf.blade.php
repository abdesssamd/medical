<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport dossier - {{ $patient->full_name }}</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;margin:24px;color:#111827}
        h1,h2{margin:0 0 10px}
        .muted{color:#6b7280}
        .section{margin-top:18px}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #d1d5db;padding:6px 8px;text-align:left;font-size:12px}
        th{background:#f3f4f6}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .badge{display:inline-block;padding:2px 6px;border-radius:999px;background:#e5e7eb;font-size:11px}
        .chips{display:flex;flex-wrap:wrap;gap:6px}
        .chip{display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;border:1px solid #d1d5db;background:#f9fafb;font-size:11px}
        .thumb-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
        .thumb-card{border:1px solid #d1d5db;border-radius:8px;padding:6px}
        .thumb-card img{width:100%;height:120px;object-fit:cover;border-radius:6px;background:#f3f4f6}
        .small{font-size:11px}
        @media print {.no-print{display:none}}
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Imprimer / Sauvegarder PDF</button>
    <h1>Rapport Dossier Patient</h1>
    <div class="muted">Genere le {{ now()->format('d/m/Y H:i') }}</div>

    <div class="section grid">
        <div>
            <strong>Patient:</strong> {{ $patient->full_name }}<br>
            <strong>MRN:</strong> {{ $patient->medical_record_number }}<br>
            <strong>Date naissance:</strong> {{ $patient->date_of_birth?->format('d/m/Y') ?: '-' }}<br>
            <strong>Telephone:</strong> {{ $patient->phone ?: '-' }}
        </div>
        <div>
            <strong>Allergies:</strong> {{ implode(', ', $patient->allergies ?? []) ?: 'Aucune' }}<br>
            <strong>Antécédents:</strong> {{ implode(', ', $patient->medical_history ?? []) ?: 'Aucun' }}<br>
            <strong>Médicaments:</strong> {{ implode(', ', $patient->current_medications ?? []) ?: 'Aucun' }}
        </div>
    </div>

    <div class="section">
        <h2>Schéma Dentaire Actuel</h2>
        @if(($teethStatusSummary ?? collect())->isNotEmpty())
            <div class="chips">
                @foreach(($teethStatusSummary ?? collect()) as $status => $count)
                    <span class="chip">{{ strtoupper((string) $status) }}: {{ $count }}</span>
                @endforeach
            </div>
        @else
            <div class="muted">Aucune donnée d'odontogramme disponible.</div>
        @endif
    </div>

    <div class="section">
        <h2>Consultations</h2>
        <table>
            <thead><tr><th>Date</th><th>Praticien</th><th>Motif</th><th>Diagnostic</th></tr></thead>
            <tbody>
            @foreach($consultations as $c)
                <tr>
                    <td>{{ $c->consultation_date?->format('d/m/Y') }}</td>
                    <td>{{ $c->practitioner?->name ?: '-' }}</td>
                    <td>{{ $c->chief_complaint ?: '-' }}</td>
                    <td>{{ $c->diagnosis ?: '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Actes Cliniques</h2>
        <table>
            <thead><tr><th>Date</th><th>Acte</th><th>Dent</th><th>Statut</th><th>Prix</th></tr></thead>
            <tbody>
            @foreach($procedures as $p)
                <tr>
                    <td>{{ optional($p->performed_at)->format('d/m/Y') ?: optional($p->planned_date)->format('d/m/Y') ?: '-' }}</td>
                    <td>{{ $p->procedure_code }} - {{ $p->name }}</td>
                    <td>{{ $p->tooth_number ?: '-' }}</td>
                    <td><span class="badge">{{ $p->status }}</span></td>
                    <td>{{ number_format((float) $p->price, 2, ',', ' ') }} MAD</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Plans de Traitement</h2>
        <table>
            <thead><tr><th>Plan</th><th>Statut</th><th>Total</th><th>Paye</th><th>Reste</th></tr></thead>
            <tbody>
            @foreach($plans as $plan)
                <tr>
                    <td>{{ $plan->name }}</td>
                    <td>{{ $plan->status }}</td>
                    <td>{{ number_format((float) $plan->total_estimated_cost, 2, ',', ' ') }}</td>
                    <td>{{ number_format((float) $plan->paid_amount, 2, ',', ' ') }}</td>
                    <td>{{ number_format(max(0, (float)$plan->total_estimated_cost - (float)$plan->paid_amount), 2, ',', ' ') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Imagerie</h2>
        <table>
            <thead><tr><th>ID</th><th>Modalite</th><th>Study UID</th><th>Fichier</th></tr></thead>
            <tbody>
            @foreach(($manifest['items'] ?? []) as $m)
                <tr>
                    <td>{{ $m['id'] }}</td>
                    <td>{{ strtoupper($m['modality']) }}</td>
                    <td>{{ $m['study_uid'] ?: '-' }}</td>
                    <td>{{ $m['file_path'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="section">
            <h2>Dernières Radios</h2>
            @if(($latestImagingItems ?? collect())->isNotEmpty())
                <div class="thumb-grid">
                    @foreach(($latestImagingItems ?? collect()) as $image)
                        @php
                            $isPreviewable = in_array(strtolower((string) ($image['mime_type'] ?? '')), ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/tiff'], true)
                                || preg_match('/\.(png|jpe?g|webp|gif|bmp|tiff?)$/i', (string) ($image['file_path'] ?? ''));
                        @endphp
                        <article class="thumb-card">
                            @if($isPreviewable)
                                <img src="{{ asset($image['file_path']) }}" alt="Imagerie {{ $image['id'] ?? '-' }}">
                            @else
                                <div class="small muted" style="height:120px;display:grid;place-items:center;border:1px dashed #d1d5db;border-radius:6px;">Aperçu indisponible</div>
                            @endif
                            <div class="small" style="margin-top:6px;"><strong>{{ strtoupper((string) ($image['modality'] ?? '-')) }}</strong> #{{ $image['id'] ?? '-' }}</div>
                            <div class="small muted">{{ $image['captured_at'] ?? $image['created_at'] ?? '-' }}</div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="muted">Aucune radio récente.</div>
            @endif
        </div>
    </div>

    <div class="section">
        <h2>Traçabilité Stérilisation</h2>
        <table>
            <thead><tr><th>Date scan</th><th>Sachet</th><th>Lot</th><th>Acte lié</th><th>Conformité</th><th>Notes</th></tr></thead>
            <tbody>
            @forelse(($sterilizationTraces ?? collect()) as $trace)
                <tr>
                    <td>{{ optional($trace->scanned_at)->format('d/m/Y H:i') ?: '-' }}</td>
                    <td>{{ $trace->pouch?->pouch_code ?: '-' }}</td>
                    <td>{{ $trace->pouch?->batch?->batch_code ?: '-' }}</td>
                    <td>
                        @if($trace->clinicalProcedure)
                            {{ $trace->clinicalProcedure->procedure_code }} - {{ $trace->clinicalProcedure->name }}
                        @elseif($trace->appointment)
                            RDV {{ optional($trace->appointment->appointment_date)->format('d/m/Y') }} {{ 
                                \Illuminate\Support\Str::of((string) $trace->appointment->start_time)->substr(0, 5)
                            }}
                        @else
                            Consultation
                        @endif
                    </td>
                    <td>{{ $trace->is_conformity_ok ? 'OK' : 'Non conforme' }}</td>
                    <td>{{ $trace->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Aucune preuve de stérilisation enregistrée.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Timeline</h2>
        @foreach(($timeline['events'] ?? []) as $event)
            <div>{{ $event['date'] ?? '-' }} - <strong>{{ $event['label'] }}</strong> ({{ $event['type'] }})</div>
        @endforeach
    </div>
</body>
</html>
