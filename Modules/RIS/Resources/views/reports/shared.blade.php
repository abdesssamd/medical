<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compte rendu radiologique</title>
    <style>
        :root {
            --ris-sky: #0ea5e9;
            --ris-sky-deep: #0369a1;
            --ris-ink: #0f172a;
            --ris-muted: #64748b;
            --ris-line: #dbe8f3;
            --ris-shell: #f7fbfe;
            --ris-card: rgba(255, 255, 255, 0.92);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            color: var(--ris-ink);
            background:
                radial-gradient(circle at top left, rgba(14, 165, 233, 0.12), transparent 30%),
                linear-gradient(180deg, #f9fcff 0%, #f2f7fb 100%);
        }

        .public-shell {
            width: min(1180px, calc(100% - 32px));
            margin: 28px auto;
            display: grid;
            gap: 20px;
        }

        .public-card {
            background: var(--ris-card);
            border: 1px solid rgba(219, 232, 243, 0.85);
            border-radius: 28px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
            backdrop-filter: blur(18px);
        }

        .public-topbar {
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .public-brand {
            display: grid;
            gap: 4px;
        }

        .public-brand-eyebrow {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: var(--ris-sky-deep);
            font-weight: 800;
        }

        .public-title {
            margin: 0;
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            line-height: 1.05;
        }

        .public-subtitle {
            color: var(--ris-muted);
            font-size: 0.96rem;
        }

        .public-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .public-btn {
            border: 1px solid #cfe1ed;
            border-radius: 999px;
            background: #fff;
            color: var(--ris-ink);
            padding: 10px 16px;
            font-size: 0.92rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .public-btn-primary {
            background: linear-gradient(135deg, var(--ris-sky), #38bdf8);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.25);
        }

        .public-grid {
            display: grid;
            grid-template-columns: 1.7fr 0.9fr;
            gap: 20px;
        }

        .public-main,
        .public-side {
            padding: 24px;
        }

        .public-section + .public-section {
            margin-top: 18px;
        }

        .public-kicker {
            margin: 0 0 10px;
            color: var(--ris-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.74rem;
            font-weight: 800;
        }

        .public-report {
            border: 1px solid var(--ris-line);
            border-radius: 24px;
            background: #fff;
            padding: 22px;
            white-space: normal;
            word-break: break-word;
            font-size: 1rem;
            line-height: 1.75;
            min-height: 320px;
        }

        .public-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .public-meta-card {
            border: 1px solid var(--ris-line);
            border-radius: 22px;
            background: #fbfdff;
            padding: 16px 18px;
        }

        .public-meta-label {
            color: var(--ris-muted);
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .public-meta-value {
            font-size: 1rem;
            font-weight: 700;
        }

        .public-seal {
            position: relative;
            display: grid;
            place-items: center;
            width: 180px;
            aspect-ratio: 1;
            border-radius: 50%;
            margin: 0 auto 18px;
            background:
                radial-gradient(circle, rgba(14, 165, 233, 0.10), rgba(14, 165, 233, 0.02)),
                #fff;
            border: 2px solid rgba(14, 165, 233, 0.28);
            box-shadow: inset 0 0 0 8px rgba(14, 165, 233, 0.07);
            text-align: center;
        }

        .public-seal::before,
        .public-seal::after {
            content: "";
            position: absolute;
            inset: 12px;
            border-radius: 50%;
            border: 1px dashed rgba(3, 105, 161, 0.32);
        }

        .public-seal-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: var(--ris-sky-deep);
            font-weight: 900;
        }

        .public-seal-title {
            margin: 8px 0 6px;
            font-size: 1.1rem;
            font-weight: 900;
            color: var(--ris-ink);
        }

        .public-seal-code {
            font-size: 0.75rem;
            color: var(--ris-muted);
            font-weight: 700;
        }

        .public-readonly body,
        body.is-readonly {
            background: #fff;
        }

        body.is-readonly .public-brand .public-brand-eyebrow,
        body.is-readonly .public-brand .public-subtitle,
        body.is-readonly .public-toolbar .public-btn:not(#readonlyModeToggle) {
            display: none !important;
        }

        body.is-readonly .public-topbar {
            justify-content: flex-end;
        }

        body.is-readonly .public-shell {
            width: min(920px, calc(100% - 32px));
            margin-top: 18px;
        }

        body.is-readonly .public-grid {
            grid-template-columns: 1fr;
        }

        @media (max-width: 960px) {
            .public-grid {
                grid-template-columns: 1fr;
            }

            .public-meta-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    @php
        $reportContent = $report->content ?: null;
        $renderedReportContent = $reportContent
            ? (preg_match('/<\s*[a-z][\s\S]*>/i', $reportContent) ? $reportContent : nl2br(e($reportContent)))
            : null;
    @endphp
    <div class="public-shell">
        <section class="public-card">
            <div class="public-topbar">
                <div class="public-brand">
                    <div class="public-brand-eyebrow">MediOffice RIS</div>
                    <h1 class="public-title">Compte rendu radiologique signe</h1>
                    <div class="public-subtitle">
                        {{ $order->patient?->full_name ?? 'Patient' }} | {{ $order->procedure?->label ?? 'Examen RIS' }} | {{ optional($report->validated_at)->format('d/m/Y H:i') ?: '-' }}
                    </div>
                </div>
                <div class="public-toolbar">
                    <button type="button" class="public-btn" id="readonlyModeToggle">Mode lecture seule</button>
                    <button type="button" class="public-btn" id="printReportButton">Imprimer / PDF</button>
                    @if($viewerUrl)
                        <a href="{{ $viewerUrl }}" target="_blank" rel="noopener" class="public-btn public-btn-primary">Consulter les images en ligne</a>
                    @endif
                </div>
            </div>
        </section>

        <section class="public-grid">
            <article class="public-card public-main">
                <div class="public-section">
                    <p class="public-kicker">Compte rendu</p>
                    <div class="public-report">{!! $renderedReportContent ?: 'Compte rendu non renseigne.' !!}</div>
                </div>

                <div class="public-section">
                    <p class="public-kicker">Contexte clinique</p>
                    <div class="public-report" style="min-height: 0;">{{ $order->clinical_indication ?: 'Aucune indication clinique saisie.' }}</div>
                </div>
            </article>

            <aside class="public-card public-side">
                <div class="public-seal">
                    <div>
                        <div class="public-seal-label">Validation</div>
                        <div class="public-seal-title">Signe numeriquement</div>
                        <div class="public-seal-code">{{ $sealCode }}</div>
                    </div>
                </div>

                <div class="public-meta-grid">
                    <div class="public-meta-card">
                        <div class="public-meta-label">Patient</div>
                        <div class="public-meta-value">{{ $order->patient?->full_name ?? 'Patient inconnu' }}</div>
                    </div>
                    <div class="public-meta-card">
                        <div class="public-meta-label">MRN</div>
                        <div class="public-meta-value">{{ $order->patient?->medical_record_number ?? '-' }}</div>
                    </div>
                    <div class="public-meta-card">
                        <div class="public-meta-label">Examen</div>
                        <div class="public-meta-value">{{ $order->procedure?->label ?? 'Examen RIS' }}</div>
                    </div>
                    <div class="public-meta-card">
                        <div class="public-meta-label">Modalite</div>
                        <div class="public-meta-value">{{ $order->modality?->name ?? 'Non definie' }}</div>
                    </div>
                    <div class="public-meta-card">
                        <div class="public-meta-label">Medecin signataire</div>
                        <div class="public-meta-value">{{ $report->signing_physician_name ?: $report->signingPhysician?->display_name ?: 'Praticien non renseigne' }}</div>
                    </div>
                    <div class="public-meta-card">
                        <div class="public-meta-label">Accession</div>
                        <div class="public-meta-value">{{ $order->accession_number ?: 'RIS-'.$order->id }}</div>
                    </div>
                </div>
            </aside>
        </section>
    </div>

    <script>
        document.getElementById('readonlyModeToggle')?.addEventListener('click', () => {
            document.body.classList.toggle('is-readonly');
        });

        document.getElementById('printReportButton')?.addEventListener('click', () => {
            window.print();
        });
    </script>
</body>
</html>
