@extends('layouts.admin')

@section('title', 'RIS Radiologie')
@section('page-title', 'RIS Radiologie')

@section('content')
    <style>
        :root {
            --ris-accent: #0ea5e9;
            --ris-accent-deep: #0369a1;
            --ris-accent-soft: rgba(14, 165, 233, 0.10);
            --ris-shell: #f6fbfe;
            --ris-card: rgba(255, 255, 255, 0.88);
            --ris-ink: #0f172a;
            --ris-muted: #64748b;
            --ris-line: rgba(219, 234, 246, 0.95);
        }

        .ris26-shell {
            display: grid;
            gap: 20px;
            color: var(--ris-ink);
        }

        .ris26-top {
            display: grid;
            grid-template-columns: 1.4fr 0.9fr;
            gap: 12px;
        }

        .ris26-card {
            background: var(--ris-card);
            border: 1px solid var(--ris-line);
            border-radius: 24px;
            box-shadow: 0 16px 50px rgba(15, 23, 42, 0.06);
            backdrop-filter: blur(16px);
        }

        .ris26-panel {
            padding: 22px 24px;
        }

        .ris26-hero {
            background:
                radial-gradient(circle at top right, rgba(14, 165, 233, 0.16), transparent 30%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 252, 255, 0.92));
        }

        .ris26-eyebrow {
            font-size: 11px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--ris-accent-deep);
            font-weight: 800;
        }

        .ris26-title {
            margin: 4px 0 6px;
            font-size: clamp(2rem, 4vw, 3.25rem);
            line-height: 0.95;
            letter-spacing: -0.03em;
        }

        .ris26-copy {
            max-width: 58ch;
            color: var(--ris-muted);
            font-size: 0.98rem;
        }

        .ris26-inline-meta,
        .ris26-actions,
        .ris26-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .ris26-inline-meta {
            margin-top: 16px;
        }

        .ris26-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 9px 12px;
            font-size: 0.78rem;
            font-weight: 700;
            background: #fff;
            border: 1px solid var(--ris-line);
            color: #334155;
        }

        .ris26-pill.is-ok { color: #0369a1; background: rgba(14, 165, 233, 0.10); border-color: rgba(14, 165, 233, 0.20); }
        .ris26-pill.is-bad { color: #b91c1c; background: #fff1f2; border-color: #fecdd3; }

        .ris26-side-stack { display: grid; gap: 14px; }

        .ris26-small-title {
            font-size: 0.76rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--ris-muted);
            font-weight: 800;
        }

        .ris26-status-title {
            margin: 8px 0 2px;
            font-size: 1.15rem;
            font-weight: 800;
        }

        .ris26-status-copy {
            color: var(--ris-muted);
            font-size: 0.9rem;
        }

        .ris26-spotlight-trigger {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid var(--ris-line);
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.9);
            padding: 16px 18px;
            cursor: pointer;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.4);
        }

        .ris26-spotlight-trigger-main {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .ris26-spotlight-icon,
        .ris26-kpi-icon {
            width: 42px;
            height: 42px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: var(--ris-accent-soft);
            color: var(--ris-accent-deep);
            flex: 0 0 auto;
        }

        .ris26-spotlight-copy strong { display: block; font-size: 1rem; }
        .ris26-spotlight-copy span { color: var(--ris-muted); font-size: 0.88rem; }

        .ris26-kbd {
            border: 1px solid #d7e5ef;
            background: #fff;
            border-radius: 999px;
            padding: 8px 10px;
            color: var(--ris-muted);
            font-size: 0.76rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .ris26-selected {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            border: 1px solid rgba(14, 165, 233, 0.22);
            border-radius: 22px;
            background: rgba(14, 165, 233, 0.06);
            padding: 16px 18px;
        }

        .ris26-patient {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .ris26-avatar {
            width: 50px;
            height: 50px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #0ea5e9, #38bdf8);
            color: #fff;
            font-weight: 800;
            overflow: hidden;
            flex: 0 0 auto;
        }

        .ris26-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ris26-patient-name {
            font-size: 1rem;
            font-weight: 800;
        }

        .ris26-patient-meta {
            color: var(--ris-muted);
            font-size: 0.88rem;
        }

        .ris26-kpis {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 14px;
        }

        .ris26-kpi {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(219, 234, 246, 0.86);
            padding: 18px;
        }

        .ris26-kpi::after {
            content: "";
            position: absolute;
            inset: auto -10px -18px auto;
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(14, 165, 233, 0.08);
        }

        .ris26-kpi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .ris26-kpi-label {
            color: var(--ris-muted);
            font-size: 0.82rem;
        }

        .ris26-kpi-value {
            margin-top: 12px;
            font-size: 1.8rem;
            line-height: 1;
            font-weight: 900;
            letter-spacing: -0.04em;
        }

        .ris26-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(320px, 0.42fr);
            gap: 18px;
            align-items: start;
        }

        .ris26-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .ris26-table thead th {
            padding: 0 14px 4px;
            text-align: left;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--ris-muted);
        }

        .ris26-row {
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
        }

        .ris26-row td {
            padding: 16px 14px;
            border-top: 1px solid rgba(219, 234, 246, 0.8);
            border-bottom: 1px solid rgba(219, 234, 246, 0.8);
            vertical-align: middle;
        }

        .ris26-row td:first-child {
            border-left: 1px solid rgba(219, 234, 246, 0.8);
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
        }

        .ris26-row td:last-child {
            border-right: 1px solid rgba(219, 234, 246, 0.8);
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .ris26-entity-title { font-weight: 800; }
        .ris26-entity-meta { color: var(--ris-muted); font-size: 0.84rem; margin-top: 4px; }

        .ris26-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 999px;
            padding: 8px 11px;
            font-size: 0.74rem;
            font-weight: 800;
            border: 1px solid transparent;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .ris26-chip-status-ordonne { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
        .ris26-chip-status-en_attente { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
        .ris26-chip-status-images_recues { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
        .ris26-chip-status-termine { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .ris26-chip-status-annule { background: #fff1f2; color: #be123c; border-color: #fecdd3; }
        .ris26-chip-priority-routine { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
        .ris26-chip-priority-urgent { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
        .ris26-chip-priority-stat { background: #fff1f2; color: #be123c; border-color: #fecdd3; }

        .ris26-chip.is-pulsing {
            animation: ris26-pulse 1.1s ease 2;
        }

        @keyframes ris26-pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(14, 165, 233, 0.28); }
            70% { transform: scale(1.03); box-shadow: 0 0 0 12px rgba(14, 165, 233, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(14, 165, 233, 0); }
        }

        .ris26-row-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .ris26-btn {
            border: 1px solid #d7e5ef;
            border-radius: 999px;
            background: #fff;
            color: var(--ris-ink);
            padding: 9px 14px;
            font-size: 0.82rem;
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }

        .ris26-btn-primary {
            background: linear-gradient(135deg, var(--ris-accent), #38bdf8);
            border-color: transparent;
            color: #fff;
        }

        .ris26-btn-soft {
            background: rgba(14, 165, 233, 0.08);
            color: var(--ris-accent-deep);
            border-color: rgba(14, 165, 233, 0.18);
        }

        .ris26-reports {
            display: grid;
            gap: 12px;
        }

        .ris26-report-item {
            border: 1px solid rgba(219, 234, 246, 0.86);
            border-radius: 22px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.94);
        }

        .ris26-empty {
            padding: 38px 20px;
            text-align: center;
            color: var(--ris-muted);
        }

        .ris26-filters {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        .ris26-field label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.76rem;
            font-weight: 800;
            color: var(--ris-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .ris26-field input,
        .ris26-field select,
        .ris26-field textarea {
            width: 100%;
            border: 1px solid #d7e5ef;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.95);
            padding: 12px 14px;
            color: var(--ris-ink);
            font-size: 0.92rem;
        }

        .ris26-fab {
            position: fixed;
            right: 28px;
            bottom: 28px;
            z-index: 70;
            border: 0;
            width: 64px;
            height: 64px;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--ris-accent), #38bdf8);
            color: #fff;
            box-shadow: 0 26px 44px rgba(14, 165, 233, 0.30);
            display: grid;
            place-items: center;
            cursor: pointer;
        }

        .ris26-overlay {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: none;
        }

        .ris26-overlay.is-open {
            display: block;
        }

        .ris26-spotlight-backdrop,
        .ris26-slide-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.28);
            backdrop-filter: blur(12px);
        }

        .ris26-spotlight-dialog {
            position: relative;
            width: min(860px, calc(100% - 28px));
            margin: 9vh auto 0;
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(219, 234, 246, 0.98);
            box-shadow: 0 40px 90px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .ris26-spotlight-input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            padding: 24px 26px 18px;
            font-size: 1.2rem;
            color: var(--ris-ink);
        }

        .ris26-spotlight-groups {
            max-height: 70vh;
            overflow: auto;
            padding: 0 16px 18px;
        }

        .ris26-group {
            padding: 8px 0 2px;
        }

        .ris26-group-title {
            padding: 8px 10px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--ris-muted);
            font-weight: 800;
        }

        .ris26-result {
            width: 100%;
            border: 0;
            background: transparent;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 12px;
            text-align: left;
            cursor: pointer;
        }

        .ris26-result:hover,
        .ris26-result.is-active {
            background: rgba(14, 165, 233, 0.08);
        }

        .ris26-result-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--ris-ink);
        }

        .ris26-result-subtitle {
            font-size: 0.82rem;
            color: var(--ris-muted);
            margin-top: 2px;
        }

        .ris26-result-badge {
            margin-left: auto;
            color: var(--ris-muted);
            font-size: 0.74rem;
            font-weight: 800;
        }

        .ris26-slide {
            position: absolute;
            top: 0;
            right: 0;
            width: min(520px, 100%);
            height: 100%;
            background: rgba(255, 255, 255, 0.98);
            border-left: 1px solid rgba(219, 234, 246, 0.98);
            box-shadow: -16px 0 40px rgba(15, 23, 42, 0.10);
            display: grid;
            grid-template-rows: auto 1fr;
        }

        .ris26-slide-head {
            padding: 22px 22px 16px;
            border-bottom: 1px solid rgba(219, 234, 246, 0.9);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .ris26-slide-body {
            overflow: auto;
            padding: 18px 22px 24px;
        }

        .ris26-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .ris26-form-grid .full {
            grid-column: 1 / -1;
        }

        @media (max-width: 1200px) {
            .ris26-top,
            .ris26-grid {
                grid-template-columns: 1fr;
            }

            .ris26-kpis {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .ris26-filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .ris26-kpis,
            .ris26-filters,
            .ris26-form-grid,
            .ris26-progress-grid {
                grid-template-columns: 1fr;
            }

            .ris26-row-actions {
                justify-content: flex-start;
            }

            .ris26-fab {
                right: 18px;
                bottom: 18px;
            }
        }
    </style>

    @php
        $selectedInitials = $selectedPatient
            ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) $selectedPatient->first_name, 0, 1).\Illuminate\Support\Str::substr((string) $selectedPatient->last_name, 0, 1))
            : 'P';
        $kpis = [
            ['key' => 'total', 'label' => 'Total examens', 'value' => $stats['total'], 'icon' => 'ti ti-layout-grid'],
            ['key' => 'today', 'label' => 'Demandes du jour', 'value' => $stats['today'], 'icon' => 'ti ti-calendar-event'],
            ['key' => 'waiting', 'label' => 'En attente', 'value' => $stats['waiting'], 'icon' => 'ti ti-loader-2'],
            ['key' => 'received', 'label' => 'Images recues', 'value' => $stats['received'], 'icon' => 'ti ti-photo-check'],
            ['key' => 'done', 'label' => 'Termines', 'value' => $stats['done'], 'icon' => 'ti ti-rosette-discount-check'],
            ['key' => 'urgent', 'label' => 'Urgents / STAT', 'value' => $stats['urgent'], 'icon' => 'ti ti-bell-ringing'],
        ];
    @endphp

    <div class="ris26-shell">
        @if($selectedPatient)
            <div id="module3-patient-context"
                 class="d-none"
                 data-patient-id="{{ $selectedPatient->id }}"
                 data-patient-name="{{ $selectedPatient->full_name }}"
                 data-patient-mrn="{{ $selectedPatient->medical_record_number }}"
                 data-patient-age="{{ $selectedPatient->age ?? optional($selectedPatient->date_of_birth)->age ?? '-' }}"
                 data-patient-phone="{{ $selectedPatient->phone ?: '-' }}"
                 data-patient-release-url="{{ route('ris.patients.clear') }}"></div>
        @endif

        <section class="ris26-top">
            <article class="ris26-card ris26-hero">
                <div class="ris26-panel">
                    <div class="ris26-eyebrow">Radiologie dentaire</div>
                    <h1 class="ris26-title">RIS edge-to-edge, plus calme, plus rapide.</h1>
                    <p class="ris26-copy">Demandes, PACS , réception d’images et comptes rendus dans un flux unique plus lisible, avec recherche globale et suivi live des statuts.</p>

                    <div class="ris26-inline-meta">
                        <span class="ris26-pill {{ ($orthancStatus['ok'] ?? false) ? 'is-ok' : 'is-bad' }}">
                            <i class="ti ti-activity-heartbeat"></i>
                            {{ ($orthancStatus['ok'] ?? false) ? 'PACS connecte' : 'PACS indisponible' }}
                        </span>
                        <span class="ris26-pill">{{ $patients->count() }} patients visibles</span>
                        <span class="ris26-pill">{{ $modalities->count() }} modalites | {{ $procedures->count() }} actes</span>
                    </div>
                </div>
            </article>

            <div class="ris26-side-stack">
                <article class="ris26-card">
                    <div class="ris26-panel">
                        <div class="ris26-small-title">Etat de la connexion</div>
                        <div class="ris26-status-title">{{ ($orthancStatus['ok'] ?? false) ? 'PACS pret' : 'Connexion a verifier' }}</div>
                        <div class="ris26-status-copy">
                            @if($orthancStatus['ok'] ?? false)
                                HTTP {{ $orthancStatus['status'] ?? 200 }} | {{ data_get($orthancStatus, 'data.Name', 'Orthanc') }}
                            @else
                                {{ $orthancStatus['message'] ?? 'Verifier la configuration RIS_ORTHANC_*' }}
                            @endif
                        </div>
                    </div>
                </article>

                <button type="button" class="ris26-spotlight-trigger" id="ris26SpotlightTrigger">
                    <span class="ris26-spotlight-trigger-main">
                        <span class="ris26-spotlight-icon"><i class="ti ti-search"></i></span>
                        <span class="ris26-spotlight-copy">
                            <strong>Recherche centrale RIS</strong>
                            <span>Patients, examens récents et actions rapides dans un spotlight unique.</span>
                        </span>
                    </span>
                    <span class="ris26-kbd">Ctrl K</span>
                </button>
            </div>
        </section>

        @if($selectedPatient)
            <section class="ris26-selected">
                <div class="ris26-patient">
                    <div class="ris26-avatar">
                        @if($selectedPatient->patient_photo_path)
                            <img src="{{ asset($selectedPatient->patient_photo_path) }}" alt="">
                        @else
                            {{ $selectedInitials ?: 'P' }}
                        @endif
                    </div>
                    <div>
                        <div class="ris26-patient-name">{{ $selectedPatient->full_name }}</div>
                        <div class="ris26-patient-meta">{{ $selectedPatient->medical_record_number }} | Ne(e) le {{ optional($selectedPatient->date_of_birth)->format('d/m/Y') ?: '-' }}</div>
                    </div>
                </div>
                <div class="ris26-actions">
                    <form method="POST" action="{{ route('ris.exams.sync-pacs') }}">
                        @csrf
                        <input type="hidden" name="patient_id" value="{{ $selectedPatient->id }}">
                        <button type="submit" class="ris26-btn ris26-btn-soft">Synchroniser avec PACS</button>
                    </form>
                    <form method="POST" action="{{ route('ris.patients.clear') }}">
                        @csrf
                        <button type="submit" class="ris26-btn">Fermer le dossier</button>
                    </form>
                </div>
            </section>
        @endif

        <section class="ris26-kpis">
            @foreach($kpis as $kpi)
                <article class="ris26-kpi">
                    <div class="ris26-kpi-top">
                        <div class="ris26-kpi-label">{{ $kpi['label'] }}</div>
                        <div class="ris26-kpi-icon"><i class="{{ $kpi['icon'] }}"></i></div>
                    </div>
                    <div class="ris26-kpi-value" data-kpi="{{ $kpi['key'] }}">{{ $kpi['value'] }}</div>
                </article>
            @endforeach
        </section>

        <section class="ris26-card">
            <div class="ris26-panel">
                <div class="ris26-toolbar" id="ris26FiltersAnchor" style="margin-bottom: 14px;">
                    <div>
                        <div class="ris26-entity-title">Filtres de lecture</div>
                        <div class="ris26-entity-meta">Gardez la file visible et épurée, puis ouvrez le panneau latéral pour créer une nouvelle demande.</div>
                    </div>
                </div>

                <form method="GET" class="ris26-filters">
                    <div class="ris26-field">
                        <label>Filtre texte</label>
                        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Patient, MRN, accession, acte...">
                    </div>
                    <div class="ris26-field">
                        <label>Statut</label>
                        <select name="status">
                            <option value="">Tous</option>
                            @foreach($statusLabels as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" @selected($filters['status'] === $statusValue)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ris26-field">
                        <label>Priorite</label>
                        <select name="priority">
                            <option value="">Toutes</option>
                            @foreach($priorityLabels as $priorityValue => $priorityLabel)
                                <option value="{{ $priorityValue }}" @selected($filters['priority'] === $priorityValue)>{{ $priorityLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ris26-field">
                        <label>Modalite</label>
                        <select name="modality_id">
                            <option value="">Toutes</option>
                            @foreach($modalities as $modality)
                                <option value="{{ $modality->id }}" @selected((int) $filters['modality_id'] === (int) $modality->id)>{{ $modality->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ris26-field" style="display: flex; align-items: flex-end; padding-bottom: 4px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.82rem; text-transform: none; letter-spacing: normal; color: var(--ris-ink); font-weight: 700;">
                            <input type="checkbox" name="include_orphan" value="1" @checked($filters['include_orphan']) style="width: 18px; height: 18px; border-radius: 6px; accent-color: var(--ris-accent);">
                            <span>Inclure les instances <span style="color: var(--ris-accent-deep);">PACS</span> non liees au RIS</span>
                        </label>
                    </div>
                    <div class="ris26-actions" style="align-self: end;">
                        <button type="submit" class="ris26-btn ris26-btn-primary">Filtrer</button>
                        <a href="{{ route('ris.exams.index') }}" class="ris26-btn">Reset</a>
                    </div>
                </form>
            </div>
        </section>

        <section class="ris26-grid">
            <section class="ris26-card">
                <div class="ris26-panel">
                    <div class="ris26-toolbar" style="margin-bottom: 14px;">
                        <div>
                            <div class="ris26-entity-title">File d’examens RIS</div>
                            <div class="ris26-entity-meta">La liste se met à jour automatiquement en arrière-plan. Les nouveaux examens ayant reçu leurs images pulsent discrètement.</div>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="ris26-table">
                            <thead>
                                <tr>
                                    <th>Accession</th>
                                    <th>Patient</th>
                                    <th>Acte / modalite</th>
                                    <th>Planification</th>
                                    <th>Statut</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    @php
                                        $payload = (array) ($order->orthanc_payload ?? []);
                                        $viewerStudyId = data_get($payload, 'study_uid')
                                            ?? data_get($payload, 'reconciliation.matched_study.study_instance_uid')
                                            ?? data_get($payload, 'orthanc_study_id')
                                            ?? data_get($payload, 'reconciliation.matched_study.study_id');
                                        $viewerUrl = $viewerStudyId
                                            ? rtrim((string) config('ris.orthanc.viewer_base_url', config('ris.orthanc.base_url', config('services.orthanc.base_url', 'http://127.0.0.1:8042'))), '/').'/stone-webviewer/index.html?study='.urlencode((string) $viewerStudyId)
                                            : null;
                                    @endphp
                                    <tr class="ris26-row" data-order-row data-order-id="{{ $order->id }}" data-order-status="{{ $order->status }}">
                                        <td>
                                            <div class="ris26-entity-title">{{ $order->accession_number ?: 'RIS-'.$order->id }}</div>
                                            <div class="ris26-entity-meta">Demande {{ optional($order->requested_at)->format('d/m/Y H:i') }}</div>
                                        </td>
                                        <td>
                                            <div class="ris26-entity-title">{{ $order->patient?->full_name ?? 'Patient inconnu' }}</div>
                                            <div class="ris26-entity-meta">{{ $order->patient?->medical_record_number }} | {{ $order->patient?->phone }}</div>
                                        </td>
                                        <td>
                                            <div class="ris26-entity-title">{{ $order->procedure?->label ?? 'Acte non defini' }}</div>
                                            <div class="ris26-entity-meta">{{ $order->modality?->name ?? 'Modalite non definie' }}</div>
                                        </td>
                                        <td>
                                            <div class="ris26-entity-title">{{ optional($order->scheduled_at)->format('d/m/Y H:i') ?: 'Non planifie' }}</div>
                                            <div class="ris26-entity-meta">{{ $order->requestedBy?->display_name ?? 'Utilisateur non renseigne' }}</div>
                                        </td>
                                        <td>
                                            <div class="ris26-actions" style="gap: 8px;">
                                                <span class="ris26-chip ris26-chip-status-{{ $order->status }}" data-status-chip>{{ $order->status_label }}</span>
                                                <span class="ris26-chip ris26-chip-priority-{{ $order->priority }}" data-priority-chip>{{ $order->priority_label }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="ris26-row-actions">
                                                <a href="{{ route('ris.exams.show', $order) }}" class="ris26-btn ris26-btn-soft">Ouvrir</a>
                                                @if($viewerUrl)
                                                    <a href="{{ $viewerUrl }}" target="_blank" rel="noopener" class="ris26-btn ris26-btn-primary">Viewer</a>
                                                @endif
                                                @if($order->status === \Modules\RIS\Models\RisOrder::STATUS_ORDONNE)
                                                    <form method="POST" action="{{ route('ris.exams.waiting', $order) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="ris26-btn">Attente</button>
                                                    </form>
                                                @endif
                                                @if($order->status !== \Modules\RIS\Models\RisOrder::STATUS_TERMINE && $order->status !== \Modules\RIS\Models\RisOrder::STATUS_ANNULE)
                                                    <form method="POST" action="{{ route('ris.exams.worklist', $order) }}">
                                                        @csrf
                                                        <button type="submit" class="ris26-btn">MWL</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="ris26-empty">Aucun examen RIS ne correspond aux filtres.</td>
                                    </tr>
                                @endforelse

                                @if($orphanStudies->isNotEmpty())
                                    <tr>
                                        <td colspan="6" style="padding: 20px 14px 6px;">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <span style="width: 6px; height: 6px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                                                <span style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.08em; color: #92400e; font-weight: 800;">
                                                    Études PACS orphelines (non liées au RIS) — {{ $orphanStudies->count() }} trouvée(s)
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    @php
                                        $orthancViewerBaseUrl = rtrim((string) config('ris.orthanc.viewer_base_url', config('ris.orthanc.base_url', config('services.orthanc.base_url', 'http://127.0.0.1:8042'))), '/');
                                    @endphp
                                    @foreach($orphanStudies as $study)
                                        @php
                                            $studyViewerUrl = $study->study_uid
                                                ? $orthancViewerBaseUrl.'/stone-webviewer/index.html?study='.urlencode($study->study_uid)
                                                : null;
                                            $studyDate = $study->study_date && strlen($study->study_date) >= 8
                                                ? substr($study->study_date, 0, 4).'/'.substr($study->study_date, 4, 2).'/'.substr($study->study_date, 6, 2)
                                                : ($study->study_date ?: 'Date inconnue');
                                        @endphp
                                        <tr class="ris26-row" style="background: rgba(255, 247, 237, 0.6);">
                                            <td>
                                                <div class="ris26-entity-title">{{ $study->accession_number ?: 'PACS-'.$study->orthanc_study_id }}</div>
                                                <div class="ris26-entity-meta">Étude du {{ $studyDate }}</div>
                                            </td>
                                            <td>
                                                <div class="ris26-entity-title">{{ $study->patient_name ?: 'Patient inconnu' }}</div>
                                                <div class="ris26-entity-meta">{{ $study->patient_id }}</div>
                                            </td>
                                            <td>
                                                <div class="ris26-entity-title">{{ $study->study_description ?: 'Description non renseignee' }}</div>
                                                <div class="ris26-entity-meta">{{ $study->modality ?: 'Modalite inconnue' }}</div>
                                            </td>
                                            <td>
                                                <div class="ris26-entity-title" style="color: #92400e;">Non lie au RIS</div>
                                                <div class="ris26-entity-meta">Orphelin PACS</div>
                                            </td>
                                            <td>
                                                <span class="ris26-chip" style="background: #fef3c7; color: #92400e; border-color: #fde68a;">
                                                    <i class="ti ti-cloud-off" style="font-size: 1rem;"></i>
                                                    Hors RIS
                                                </span>
                                            </td>
                                            <td>
                                                <div class="ris26-row-actions">
                                                    @if($studyViewerUrl)
                                                        <a href="{{ $studyViewerUrl }}" target="_blank" rel="noopener" class="ris26-btn ris26-btn-primary">Viewer</a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>

                    {{ $orders->links() }}
                </div>
            </section>

            <aside class="ris26-card" id="ris26ReportsAnchor">
                <div class="ris26-panel">
                    <div class="ris26-entity-title" style="margin-bottom: 4px;">Comptes rendus récents</div>
                    <div class="ris26-entity-meta" style="margin-bottom: 14px;">Vue condensée des dernières validations, utile pour reprendre un diagnostic vite.</div>

                    <div class="ris26-reports">
                        @forelse($recentReports as $report)
                            <article class="ris26-report-item">
                                <div class="ris26-toolbar">
                                    <div>
                                        <div class="ris26-entity-title">{{ $report->order?->patient?->full_name ?? 'Patient' }}</div>
                                        <div class="ris26-entity-meta">{{ $report->order?->procedure?->label ?? 'Acte RIS' }}</div>
                                    </div>
                                    @if($report->order)
                                        <a href="{{ route('ris.exams.show', $report->order) }}" class="ris26-btn ris26-btn-soft">Ouvrir</a>
                                    @endif
                                </div>
                                <div class="ris26-entity-meta" style="margin-top: 10px;">{{ \Illuminate\Support\Str::limit(strip_tags($report->content), 150) }}</div>
                                <div class="ris26-entity-meta" style="margin-top: 10px;">
                                    Valide le {{ optional($report->validated_at)->format('d/m/Y H:i') }}
                                    @if($report->signingPhysician)
                                        | {{ $report->signingPhysician->display_name }}
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="ris26-empty">Aucun compte rendu signe pour le moment.</div>
                        @endforelse
                    </div>
                </div>
            </aside>
        </section>
    </div>

    <button type="button" class="ris26-fab" id="ris26Fab" aria-label="Nouvelle demande RIS">
        <i class="ti ti-plus" style="font-size: 1.4rem;"></i>
    </button>

    <div class="ris26-overlay" id="ris26SpotlightOverlay" aria-hidden="true">
        <div class="ris26-spotlight-backdrop" data-close-spotlight></div>
        <div class="ris26-spotlight-dialog">
            <input id="ris26SpotlightInput" class="ris26-spotlight-input" type="search" placeholder="Rechercher un patient, un examen récent, ou une action...">
            <div id="ris26SpotlightResults" class="ris26-spotlight-groups"></div>
        </div>
    </div>

    <div class="ris26-overlay" id="ris26SlideOverlay" aria-hidden="true">
        <div class="ris26-slide-backdrop" data-close-slide></div>
        <aside class="ris26-slide">
            <div class="ris26-slide-head">
                <div>
                    <div class="ris26-small-title">Nouvelle demande</div>
                    <div class="ris26-entity-title" style="margin-top: 6px;">Créer un examen RIS</div>
                    <div class="ris26-entity-meta" style="margin-top: 4px;">Le formulaire vit dans un panneau latéral pour garder la file visible.</div>
                </div>
                <button type="button" class="ris26-btn" data-close-slide>Fermer</button>
            </div>
            <div class="ris26-slide-body">
                <form method="POST" action="{{ route('ris.exams.store') }}" class="ris26-form-grid">
                    @csrf
                    @if($selectedPatient)
                        <input type="hidden" name="patient_id" value="{{ $selectedPatient->id }}">
                        <div class="full ris26-field">
                            <label>Patient</label>
                            <div class="ris26-selected" style="margin-top: 0;">
                                <div class="ris26-patient">
                                    <div class="ris26-avatar">
                                        @if($selectedPatient->patient_photo_path)
                                            <img src="{{ asset($selectedPatient->patient_photo_path) }}" alt="">
                                        @else
                                            {{ $selectedInitials ?: 'P' }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="ris26-patient-name">{{ $selectedPatient->full_name }}</div>
                                        <div class="ris26-patient-meta">{{ $selectedPatient->medical_record_number }} | Patient verrouillé par le dossier actif</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="full ris26-field">
                            <label>Patient</label>
                            <select name="patient_id" required>
                                <option value="">Choisir un patient</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}" @selected((int) old('patient_id', $filters['patient_id']) === (int) $patient->id)>
                                        {{ $patient->full_name }} | {{ $patient->medical_record_number }} | {{ $patient->phone }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="ris26-field">
                        <label>Acte RIS</label>
                        <select name="procedure_id" required>
                            <option value="">Choisir</option>
                            @foreach($procedures as $procedure)
                                <option value="{{ $procedure->id }}" @selected((int) old('procedure_id') === (int) $procedure->id)>{{ $procedure->label }} | {{ number_format((float) $procedure->price, 2, ',', ' ') }} MAD</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ris26-field">
                        <label>Modalite</label>
                        <select name="modality_id" required>
                            <option value="">Choisir</option>
                            @foreach($modalities as $modality)
                                <option value="{{ $modality->id }}" @selected((int) old('modality_id') === (int) $modality->id)>{{ $modality->name }} | {{ strtoupper($modality->ae_title) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ris26-field">
                        <label>Priorite</label>
                        <select name="priority" required>
                            @foreach($priorityLabels as $priorityValue => $priorityLabel)
                                <option value="{{ $priorityValue }}" @selected(old('priority', \Modules\RIS\Models\RisOrder::PRIORITY_ROUTINE) === $priorityValue)>{{ $priorityLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ris26-field">
                        <label>Demandeur</label>
                        <select name="requested_by_user_id">
                            <option value="">Utilisateur connecte</option>
                            @foreach($requesters as $requester)
                                <option value="{{ $requester->id }}" @selected((int) old('requested_by_user_id') === (int) $requester->id)>{{ $requester->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ris26-field">
                        <label>Date demande</label>
                        <input type="datetime-local" name="requested_at" value="{{ old('requested_at', now()->format('Y-m-d\TH:i')) }}">
                    </div>

                    <div class="ris26-field">
                        <label>Date planifiee</label>
                        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}">
                    </div>

                    <div class="full ris26-field">
                        <label>Indication clinique</label>
                        <textarea name="clinical_indication" rows="5" placeholder="Douleur, contrôle, suspicion apicale, bilan implantaire...">{{ old('clinical_indication') }}</textarea>
                    </div>

                    <div class="full ris26-actions" style="justify-content: space-between;">
                        <label class="ris26-entity-meta" style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="sync_to_orthanc" value="1" @checked(old('sync_to_orthanc', true))>
                            Envoyer aussi vers la Modality Worklist Orthanc
                        </label>
                        <button type="submit" class="ris26-btn ris26-btn-primary">Créer l’examen RIS</button>
                    </div>
                </form>
            </div>
        </aside>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const spotlightOverlay = document.getElementById('ris26SpotlightOverlay');
            const spotlightTrigger = document.getElementById('ris26SpotlightTrigger');
            const spotlightInput = document.getElementById('ris26SpotlightInput');
            const spotlightResults = document.getElementById('ris26SpotlightResults');
            const slideOverlay = document.getElementById('ris26SlideOverlay');
            const fab = document.getElementById('ris26Fab');
            const filtersAnchor = document.getElementById('ris26FiltersAnchor');
            const reportsAnchor = document.getElementById('ris26ReportsAnchor');
            const orderRows = Array.from(document.querySelectorAll('[data-order-row]'));
            let searchTimer = null;
            let spotlightController = null;
            let pollTimer = null;
            const orderState = new Map(orderRows.map((row) => [row.dataset.orderId, row.dataset.orderStatus]));

            const quickActionHandlers = {
                'open-new-exam': () => openSlide(),
                'focus-filters': () => filtersAnchor?.scrollIntoView({ behavior: 'smooth', block: 'start' }),
                'open-reports': () => reportsAnchor?.scrollIntoView({ behavior: 'smooth', block: 'start' }),
            };

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const avatarHtml = (patient) => {
                if (patient.photo_url) {
                    return `<img src="${escapeHtml(patient.photo_url)}" alt="">`;
                }

                return escapeHtml(patient.initials || 'P');
            };

            const openSpotlight = () => {
                spotlightOverlay?.classList.add('is-open');
                spotlightOverlay?.setAttribute('aria-hidden', 'false');
                spotlightInput?.focus();
            };

            const closeSpotlight = () => {
                spotlightOverlay?.classList.remove('is-open');
                spotlightOverlay?.setAttribute('aria-hidden', 'true');
                if (spotlightInput) {
                    spotlightInput.value = '';
                }
                if (spotlightResults) {
                    spotlightResults.innerHTML = '';
                }
            };

            const openSlide = () => {
                slideOverlay?.classList.add('is-open');
                slideOverlay?.setAttribute('aria-hidden', 'false');
                window.setTimeout(() => {
                    slideOverlay?.querySelector('select, input, textarea')?.focus();
                }, 80);
            };

            const closeSlide = () => {
                slideOverlay?.classList.remove('is-open');
                slideOverlay?.setAttribute('aria-hidden', 'true');
            };

            const renderGroup = (title, items, renderItem) => {
                if (!items.length) {
                    return '';
                }

                return `
                    <section class="ris26-group">
                        <div class="ris26-group-title">${title}</div>
                        ${items.map(renderItem).join('')}
                    </section>
                `;
            };

            const renderSpotlight = (payload) => {
                if (!spotlightResults) return;

                const html = [
                    renderGroup('Patients', payload.patients || [], (patient) => `
                        <button type="button" class="ris26-result" data-kind="patient" data-patient-id="${patient.id}">
                            <span class="ris26-avatar">${avatarHtml(patient)}</span>
                            <span>
                                <span class="ris26-result-title">${escapeHtml(patient.full_name)}</span>
                                <span class="ris26-result-subtitle">${escapeHtml(patient.medical_record_number || '-')} | ${escapeHtml(patient.date_of_birth || '-')}</span>
                            </span>
                        </button>
                    `),
                    renderGroup('Examens recents', payload.orders || [], (order) => `
                        <button type="button" class="ris26-result" data-kind="order" data-order-url="${escapeHtml(order.url)}">
                            <span class="ris26-spotlight-icon"><i class="ti ti-stethoscope"></i></span>
                            <span>
                                <span class="ris26-result-title">${escapeHtml(order.label)}</span>
                                <span class="ris26-result-subtitle">${escapeHtml(order.subtitle)}</span>
                            </span>
                            <span class="ris26-result-badge">${escapeHtml(order.status_label)}</span>
                        </button>
                    `),
                    renderGroup('Actions rapides', payload.actions || [], (action) => `
                        <button type="button" class="ris26-result" data-kind="action" data-action-key="${escapeHtml(action.action)}">
                            <span class="ris26-spotlight-icon"><i class="ti ti-bolt"></i></span>
                            <span>
                                <span class="ris26-result-title">${escapeHtml(action.label)}</span>
                                <span class="ris26-result-subtitle">${escapeHtml(action.hint)}</span>
                            </span>
                        </button>
                    `),
                ].join('');

                spotlightResults.innerHTML = html || '<div class="ris26-empty">Aucun resultat.</div>';
            };

            const selectPatient = async (patientId) => {
                const response = await fetch('{{ route('ris.patients.select') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ patient_id: patientId }),
                });

                const payload = await response.json();
                window.location.href = payload.redirect || '{{ route('ris.exams.index') }}';
            };

            const runSpotlightSearch = async () => {
                const q = (spotlightInput?.value || '').trim();
                if (q.length < 2) {
                    spotlightResults.innerHTML = '<div class="ris26-empty">Tapez au moins 2 caractères pour rechercher.</div>';
                    return;
                }

                if (spotlightController) {
                    spotlightController.abort();
                }

                spotlightController = new AbortController();
                const response = await fetch(`{{ route('ris.spotlight') }}?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json' },
                    signal: spotlightController.signal,
                });
                const payload = await response.json();
                renderSpotlight(payload);
            };

            const updateLiveUi = (payload) => {
                Object.entries(payload.stats || {}).forEach(([key, value]) => {
                    const node = document.querySelector(`[data-kpi="${key}"]`);
                    if (node) {
                        node.textContent = value;
                    }
                });

                (payload.orders || []).forEach((order) => {
                    const row = document.querySelector(`[data-order-id="${order.id}"]`);
                    if (!row) return;

                    const oldStatus = orderState.get(String(order.id)) || row.dataset.orderStatus;
                    row.dataset.orderStatus = order.status;
                    orderState.set(String(order.id), order.status);

                    const statusChip = row.querySelector('[data-status-chip]');
                    if (statusChip) {
                        statusChip.textContent = order.status_label;
                        statusChip.className = `ris26-chip ris26-chip-status-${order.status}`;

                        if (oldStatus !== order.status && order.status === 'images_recues') {
                            statusChip.classList.add('is-pulsing');
                            window.setTimeout(() => statusChip.classList.remove('is-pulsing'), 2400);
                        }
                    }

                    const priorityChip = row.querySelector('[data-priority-chip]');
                    if (priorityChip) {
                        priorityChip.textContent = order.priority_label;
                        priorityChip.className = `ris26-chip ris26-chip-priority-${order.priority}`;
                    }
                });
            };

            const pollLive = async () => {
                if (!orderRows.length) {
                    return;
                }

                const ids = orderRows.map((row) => row.dataset.orderId);
                const url = new URL('{{ route('ris.exams.live') }}', window.location.origin);
                ids.forEach((id) => url.searchParams.append('ids[]', id));

                try {
                    const response = await fetch(url.toString(), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const payload = await response.json();
                    updateLiveUi(payload);
                } catch (_) {
                    // silent polling failure
                }
            };

            spotlightTrigger?.addEventListener('click', openSpotlight);
            fab?.addEventListener('click', openSlide);

            spotlightInput?.addEventListener('input', () => {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(() => {
                    runSpotlightSearch().catch(() => {
                        spotlightResults.innerHTML = '<div class="ris26-empty">Recherche momentanement indisponible.</div>';
                    });
                }, 160);
            });

            spotlightResults?.addEventListener('click', (event) => {
                const button = event.target.closest('.ris26-result');
                if (!button) return;

                const kind = button.dataset.kind;
                if (kind === 'patient') {
                    selectPatient(button.dataset.patientId);
                    return;
                }

                if (kind === 'order' && button.dataset.orderUrl) {
                    window.location.href = button.dataset.orderUrl;
                    return;
                }

                if (kind === 'action' && button.dataset.actionKey && quickActionHandlers[button.dataset.actionKey]) {
                    closeSpotlight();
                    quickActionHandlers[button.dataset.actionKey]();
                }
            });

            document.querySelectorAll('[data-close-spotlight]').forEach((node) => node.addEventListener('click', closeSpotlight));
            document.querySelectorAll('[data-close-slide]').forEach((node) => node.addEventListener('click', closeSlide));

            document.addEventListener('keydown', (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                    event.preventDefault();
                    openSpotlight();
                    return;
                }

                if (event.key === 'Escape') {
                    closeSpotlight();
                    closeSlide();
                }
            });

            pollLive();
            pollTimer = window.setInterval(pollLive, 12000);
        });
    </script>
@endsection
