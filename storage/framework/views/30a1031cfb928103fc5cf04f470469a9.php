<?php $__env->startSection('title', 'RIS Radiologie'); ?>
<?php $__env->startSection('page-title', 'RIS Radiologie'); ?>

<?php $__env->startSection('content'); ?>
    <style>
        :root {
            --ris-accent: #0d9488;
            --ris-accent-deep: #0f766e;
            --ris-accent-soft: rgba(13, 148, 136, 0.10);
            --ris-shell: #f0fdfa;
            --ris-card: rgba(255, 255, 255, 0.88);
            --ris-ink: #0f172a;
            --ris-muted: #64748b;
            --ris-line: rgba(204, 235, 229, 0.95);
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
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.04);
        }

        .ris26-card-flat {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .ris26-panel {
            padding: 22px 24px;
        }

        .ris26-hero {
            background:
                radial-gradient(circle at top right, rgba(13, 148, 136, 0.16), transparent 30%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(240, 253, 250, 0.92));
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

        .ris26-pill.is-ok { color: #0f766e; background: rgba(13, 148, 136, 0.10); border-color: rgba(13, 148, 136, 0.20); }
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
            border: 1px solid rgba(13, 148, 136, 0.22);
            border-radius: 22px;
            background: rgba(13, 148, 136, 0.06);
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
            background: linear-gradient(135deg, #0d9488, #2dd4bf);
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
            padding: 18px;
            border: 0;
        }

        .ris26-kpi::after {
            content: "";
            position: absolute;
            inset: auto -10px -18px auto;
            width: 72px;
            height: 72px;
            border-radius: 50%;
        }

        .ris26-kpi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .ris26-kpi-label {
            font-size: 0.82rem;
            font-weight: 700;
        }

        .ris26-kpi-value {
            margin-top: 12px;
            font-size: 1.8rem;
            line-height: 1;
            font-weight: 900;
            letter-spacing: -0.04em;
        }

        .ris26-kpi[data-kpi="total"]    { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
        .ris26-kpi[data-kpi="total"]::after { background: rgba(255,255,255,0.10); }
        .ris26-kpi[data-kpi="total"] .ris26-kpi-label { color: rgba(255,255,255,0.75); }
        .ris26-kpi[data-kpi="total"] .ris26-kpi-icon  { background: rgba(255,255,255,0.15); color: #fff; }

        .ris26-kpi[data-kpi="today"]   { background: linear-gradient(135deg, #f093fb, #f5576c); color: #fff; }
        .ris26-kpi[data-kpi="today"]::after { background: rgba(255,255,255,0.10); }
        .ris26-kpi[data-kpi="today"] .ris26-kpi-label { color: rgba(255,255,255,0.75); }
        .ris26-kpi[data-kpi="today"] .ris26-kpi-icon  { background: rgba(255,255,255,0.15); color: #fff; }

        .ris26-kpi[data-kpi="waiting"] { background: linear-gradient(135deg, #4facfe, #00f2fe); color: #fff; }
        .ris26-kpi[data-kpi="waiting"]::after { background: rgba(255,255,255,0.10); }
        .ris26-kpi[data-kpi="waiting"] .ris26-kpi-label { color: rgba(255,255,255,0.75); }
        .ris26-kpi[data-kpi="waiting"] .ris26-kpi-icon  { background: rgba(255,255,255,0.15); color: #fff; }

        .ris26-kpi[data-kpi="received"] { background: linear-gradient(135deg, #43e97b, #38f9d7); color: #fff; }
        .ris26-kpi[data-kpi="received"]::after { background: rgba(255,255,255,0.10); }
        .ris26-kpi[data-kpi="received"] .ris26-kpi-label { color: rgba(255,255,255,0.75); }
        .ris26-kpi[data-kpi="received"] .ris26-kpi-icon  { background: rgba(255,255,255,0.15); color: #fff; }

        .ris26-kpi[data-kpi="done"]    { background: linear-gradient(135deg, #a18cd1, #fbc2eb); color: #fff; }
        .ris26-kpi[data-kpi="done"]::after { background: rgba(255,255,255,0.10); }
        .ris26-kpi[data-kpi="done"] .ris26-kpi-label { color: rgba(255,255,255,0.75); }
        .ris26-kpi[data-kpi="done"] .ris26-kpi-icon  { background: rgba(255,255,255,0.15); color: #fff; }

        .ris26-kpi[data-kpi="urgent"]  { background: linear-gradient(135deg, #fa709a, #fee140); color: #fff; }
        .ris26-kpi[data-kpi="urgent"]::after { background: rgba(255,255,255,0.10); }
        .ris26-kpi[data-kpi="urgent"] .ris26-kpi-label { color: rgba(255,255,255,0.75); }
        .ris26-kpi[data-kpi="urgent"] .ris26-kpi-icon  { background: rgba(255,255,255,0.15); color: #fff; }

        .ris26-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(320px, 0.42fr);
            gap: 18px;
            align-items: start;
        }

        .ris26-table {
            width: 100%;
            border-collapse: collapse;
        }

        .ris26-table thead th {
            padding: 12px 16px;
            text-align: left;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #64748b;
            font-weight: 800;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        .ris26-table thead th:first-child { border-radius: 14px 0 0 0; }
        .ris26-table thead th:last-child  { border-radius: 0 14px 0 0; }

        .ris26-row {
            transition: background .15s ease, box-shadow .15s ease;
        }

        .ris26-row:hover {
            background: rgba(13, 148, 136, 0.04);
        }

        .ris26-row-orphan td {
            background: #fffbeb;
        }

        .ris26-row-orphan:hover td {
            background: #fef3c7;
        }

        .ris26-row td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .ris26-entity-title {
            font-weight: 800;
            font-size: 0.95rem;
        }

        .ris26-entity-meta {
            color: #94a3b8;
            font-size: 0.82rem;
            margin-top: 3px;
        }

        .ris26-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .ris26-badge-count {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(13, 148, 136, 0.10);
            color: #0f766e;
            font-size: 0.82rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .ris26-table .ris26-patient-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ris26-table .ris26-patient-avatar {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0d9488, #2dd4bf);
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 0.78rem;
            flex: 0 0 auto;
        }

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
        .ris26-chip-status-images_recues { background: #ccfbf1; color: #0f766e; border-color: #99f6e4; }
        .ris26-chip-status-termine { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .ris26-chip-status-annule { background: #fff1f2; color: #be123c; border-color: #fecdd3; }
        .ris26-chip-priority-routine { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
        .ris26-chip-priority-urgent { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
        .ris26-chip-priority-stat { background: #fff1f2; color: #be123c; border-color: #fecdd3; }

        .ris26-chip.is-pulsing {
            animation: ris26-pulse 1.1s ease 2;
        }

        @keyframes ris26-pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(13, 148, 136, 0.28); }
            70% { transform: scale(1.03); box-shadow: 0 0 0 12px rgba(13, 148, 136, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(13, 148, 136, 0); }
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
            background: linear-gradient(135deg, var(--ris-accent), #2dd4bf);
            border-color: transparent;
            color: #fff;
        }

        .ris26-btn-soft {
            background: rgba(13, 148, 136, 0.08);
            color: var(--ris-accent-deep);
            border-color: rgba(13, 148, 136, 0.18);
        }

        .ris26-reports {
            display: grid;
            gap: 12px;
        }

        .ris26-report-item {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px;
            background: #fff;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .ris26-report-item:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        }

        .ris26-empty {
            padding: 48px 20px;
            text-align: center;
            color: #94a3b8;
            font-weight: 700;
            font-size: 0.92rem;
        }

        .ris26-pagination {
            padding: 16px 0 4px;
            display: flex;
            justify-content: center;
        }

        .ris26-pagination nav [role="navigation"] {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .ris26-pagination nav span, .ris26-pagination nav a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border-radius: 10px;
            font-size: 0.84rem;
            font-weight: 700;
            text-decoration: none;
            color: #475569;
            background: #fff;
            border: 1px solid #e2e8f0;
            transition: all .15s ease;
        }

        .ris26-pagination nav a:hover {
            background: rgba(13, 148, 136, 0.08);
            border-color: rgba(13, 148, 136, 0.3);
            color: #0f766e;
        }

        .ris26-pagination nav span[aria-current="page"] {
            background: #0d9488;
            border-color: #0d9488;
            color: #fff;
        }

        .ris26-pagination nav span:not([aria-current]) {
            border-color: transparent;
            background: transparent;
            color: #94a3b8;
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
            background: linear-gradient(135deg, var(--ris-accent), #2dd4bf);
            color: #fff;
            box-shadow: 0 26px 44px rgba(13, 148, 136, 0.30);
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
            background: rgba(13, 148, 136, 0.08);
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

    <?php
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
    ?>

    <div class="ris26-shell">
        <?php if($selectedPatient): ?>
            <div id="module3-patient-context"
                 class="d-none"
                 data-patient-id="<?php echo e($selectedPatient->id); ?>"
                 data-patient-name="<?php echo e($selectedPatient->full_name); ?>"
                 data-patient-mrn="<?php echo e($selectedPatient->medical_record_number); ?>"
                 data-patient-age="<?php echo e($selectedPatient->age ?? optional($selectedPatient->date_of_birth)->age ?? '-'); ?>"
                 data-patient-phone="<?php echo e($selectedPatient->phone ?: '-'); ?>"
                 data-patient-release-url="<?php echo e(route('ris.patients.clear')); ?>"></div>
        <?php endif; ?>

        <section class="ris26-top">
            <article class="ris26-card ris26-hero">
                <div class="ris26-panel">
                    <div class="ris26-eyebrow">Radiologie dentaire</div>
                    <h1 class="ris26-title">RIS edge-to-edge, plus calme, plus rapide.</h1>
                    <p class="ris26-copy">Demandes, PACS , réception d’images et comptes rendus dans un flux unique plus lisible, avec recherche globale et suivi live des statuts.</p>

                    <div class="ris26-inline-meta">
                        <span class="ris26-pill <?php echo e(($orthancStatus['ok'] ?? false) ? 'is-ok' : 'is-bad'); ?>">
                            <i class="ti ti-activity-heartbeat"></i>
                            <?php echo e(($orthancStatus['ok'] ?? false) ? 'PACS connecte' : 'PACS indisponible'); ?>

                        </span>
                        <span class="ris26-pill"><?php echo e($patients->count()); ?> patients visibles</span>
                        <span class="ris26-pill"><?php echo e($modalities->count()); ?> modalites | <?php echo e($procedures->count()); ?> actes</span>
                    </div>
                </div>
            </article>

            <div class="ris26-side-stack">
                <article class="ris26-card">
                    <div class="ris26-panel">
                        <div class="ris26-small-title">Etat de la connexion</div>
                        <div class="ris26-status-title"><?php echo e(($orthancStatus['ok'] ?? false) ? 'PACS pret' : 'Connexion a verifier'); ?></div>
                        <div class="ris26-status-copy">
                            <?php if($orthancStatus['ok'] ?? false): ?>
                                HTTP <?php echo e($orthancStatus['status'] ?? 200); ?> | <?php echo e(data_get($orthancStatus, 'data.Name', 'Orthanc')); ?>

                            <?php else: ?>
                                <?php echo e($orthancStatus['message'] ?? 'Verifier la configuration RIS_ORTHANC_*'); ?>

                            <?php endif; ?>
                        </div>
                    </div>
                </article>

                <a href="<?php echo e(route('patient-portal.admin.index')); ?>" class="ris26-spotlight-trigger" style="text-decoration:none;">
                    <span class="ris26-spotlight-trigger-main">
                        <span class="ris26-spotlight-icon"><i class="ti ti-door-enter"></i></span>
                        <span class="ris26-spotlight-copy">
                            <strong>Portail patient</strong>
                            <span>Accéder à la gestion des accès patient, codes et mémo.</span>
                        </span>
                    </span>
                    <span class="ris26-kbd">Admin</span>
                </a>

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

        <?php if($selectedPatient): ?>
            <section class="ris26-selected">
                <div class="ris26-patient">
                    <div class="ris26-avatar">
                        <?php if($selectedPatient->patient_photo_path): ?>
                            <img src="<?php echo e(asset($selectedPatient->patient_photo_path)); ?>" alt="">
                        <?php else: ?>
                            <?php echo e($selectedInitials ?: 'P'); ?>

                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="ris26-patient-name"><?php echo e($selectedPatient->full_name); ?></div>
                        <div class="ris26-patient-meta"><?php echo e($selectedPatient->medical_record_number); ?> | Ne(e) le <?php echo e(optional($selectedPatient->date_of_birth)->format('d/m/Y') ?: '-'); ?></div>
                    </div>
                </div>
                <div class="ris26-actions">
                    <form method="POST" action="<?php echo e(route('ris.exams.sync-pacs')); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="patient_id" value="<?php echo e($selectedPatient->id); ?>">
                        <button type="submit" class="ris26-btn ris26-btn-soft">Synchroniser avec PACS</button>
                    </form>
                    <form method="POST" action="<?php echo e(route('ris.patients.clear')); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="ris26-btn">Fermer le dossier</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="ris26-kpis">
            <?php $__currentLoopData = $kpis; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kpi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <article class="ris26-kpi" data-kpi="<?php echo e($kpi['key']); ?>">
                    <div class="ris26-kpi-top">
                        <div class="ris26-kpi-label"><?php echo e($kpi['label']); ?></div>
                        <div class="ris26-kpi-icon"><i class="<?php echo e($kpi['icon']); ?>"></i></div>
                    </div>
                    <div class="ris26-kpi-value" data-kpi-value="<?php echo e($kpi['key']); ?>"><?php echo e($kpi['value']); ?></div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </section>

        <section class="ris26-card ris26-card-flat">
            <div class="ris26-panel">
                <div class="ris26-toolbar" id="ris26FiltersAnchor" style="margin-bottom: 16px;">
                    <div>
                        <div class="ris26-entity-title" style="font-size:1.1rem;">Filtres de lecture</div>
                        <div class="ris26-entity-meta">Affinez la liste des examens par texte, statut, priorité ou modalité.</div>
                    </div>
                </div>

                <form method="GET" class="ris26-filters">
                    <div class="ris26-field">
                        <label>Recherche</label>
                        <input type="text" name="search" value="<?php echo e($filters['search']); ?>" placeholder="Patient, MRN, accession, acte...">
                    </div>
                    <div class="ris26-field">
                        <label>Statut</label>
                        <select name="status">
                            <option value="">Tous</option>
                            <?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $statusValue => $statusLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($statusValue); ?>" <?php if($filters['status'] === $statusValue): echo 'selected'; endif; ?>><?php echo e($statusLabel); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="ris26-field">
                        <label>Priorite</label>
                        <select name="priority">
                            <option value="">Toutes</option>
                            <?php $__currentLoopData = $priorityLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $priorityValue => $priorityLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($priorityValue); ?>" <?php if(old('priority', \Modules\RIS\Models\RisOrder::PRIORITY_ROUTINE) === $priorityValue): echo 'selected'; endif; ?>><?php echo e($priorityLabel); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="ris26-field">
                        <label>Modalite</label>
                        <select name="modality_id">
                            <option value="">Toutes</option>
                            <?php $__currentLoopData = $modalities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $modality): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($modality->id); ?>" <?php if((int) $filters['modality_id'] === (int) $modality->id): echo 'selected'; endif; ?>><?php echo e($modality->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="ris26-field" style="display: flex; align-items: flex-end; padding-bottom: 4px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.82rem; text-transform: none; letter-spacing: normal; color: var(--ris-ink); font-weight: 700;">
                            <input type="checkbox" name="include_orphan" value="1" <?php if($filters['include_orphan']): echo 'checked'; endif; ?> style="width: 18px; height: 18px; border-radius: 6px; accent-color: var(--ris-accent);">
                            <span>Inclure les instances <span style="color: var(--ris-accent-deep);">PACS</span> non liees au RIS</span>
                        </label>
                        <?php if($filters['include_orphan'] && $orphanStudiesError): ?>
                            <div style="margin-top:6px; font-size:0.78rem; color:#b91c1c; font-weight:700;">
                                <i class="ti ti-alert-triangle"></i> <?php echo e($orphanStudiesError); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="ris26-actions" style="align-self: end;">
                        <button type="submit" class="ris26-btn ris26-btn-primary">Filtrer</button>
                        <a href="<?php echo e(route('ris.exams.index')); ?>" class="ris26-btn">Reset</a>
                    </div>
                </form>
            </div>
        </section>

        <section class="ris26-grid">
            <section class="ris26-card">
                <div class="ris26-panel">
                    <div class="ris26-table-header">
                        <div>
                            <div class="ris26-entity-title" style="font-size:1.1rem;">File d’examens RIS</div>
                            <div class="ris26-entity-meta">Les nouveaux examens ayant reçu leurs images pulsent discrètement.</div>
                        </div>
                        <span class="ris26-badge-count"><?php echo e($orders->total()); ?> examen(s)</span>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="ris26-table">
                            <thead>
                                <tr>
                                    <th>Accession</th>
                                    <th>Patient</th>
                                    <th>Examen</th>
                                    <th>Planification</th>
                                    <th>Statut</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php
                                        $payload = (array) ($order->orthanc_payload ?? []);
                                        $viewerStudyId = data_get($payload, 'study_uid')
                                            ?? data_get($payload, 'reconciliation.matched_study.study_instance_uid')
                                            ?? data_get($payload, 'orthanc_study_id')
                                            ?? data_get($payload, 'reconciliation.matched_study.study_id');
                                        $viewerUrl = $viewerStudyId
                                            ? rtrim((string) config('ris.orthanc.viewer_base_url', config('ris.orthanc.base_url', config('services.orthanc.base_url', 'http://127.0.0.1:8042'))), '/').'/stone-webviewer/index.html?study='.urlencode((string) $viewerStudyId)
                                            : null;
                                        $patientInitials = $order->patient
                                            ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) $order->patient->first_name, 0, 1).\Illuminate\Support\Str::substr((string) $order->patient->last_name, 0, 1))
                                            : '?';
                                    ?>
                                    <tr class="ris26-row" data-order-row data-order-id="<?php echo e($order->id); ?>" data-order-status="<?php echo e($order->status); ?>">
                                        <td>
                                            <div class="ris26-entity-title"><?php echo e($order->accession_number ?: 'RIS-'.$order->id); ?></div>
                                            <div class="ris26-entity-meta"><?php echo e(optional($order->requested_at)->format('d/m/Y H:i')); ?></div>
                                        </td>
                                        <td>
                                            <div class="ris26-patient-cell">
                                                <span class="ris26-patient-avatar"><?php echo e($patientInitials); ?></span>
                                                <div>
                                                    <div class="ris26-entity-title"><?php echo e($order->patient?->full_name ?? 'Patient inconnu'); ?></div>
                                                    <div class="ris26-entity-meta"><?php echo e($order->patient?->medical_record_number); ?> <?php echo e($order->patient?->phone ? '| '.$order->patient->phone : ''); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="ris26-entity-title"><?php echo e($order->procedure?->label ?? 'Acte non defini'); ?></div>
                                            <div class="ris26-entity-meta"><?php echo e($order->modality?->name ?? 'Modalite non definie'); ?></div>
                                        </td>
                                        <td>
                                            <div class="ris26-entity-title"><?php echo e(optional($order->scheduled_at)->format('d/m/Y H:i') ?: 'Non planifie'); ?></div>
                                            <div class="ris26-entity-meta"><?php echo e($order->requestedBy?->display_name ?? 'Utilisateur non renseigne'); ?></div>
                                        </td>
                                        <td>
                                            <div class="ris26-actions" style="gap: 8px;">
                                                <span class="ris26-chip ris26-chip-status-<?php echo e($order->status); ?>" data-status-chip><?php echo e($order->status_label); ?></span>
                                                <span class="ris26-chip ris26-chip-priority-<?php echo e($order->priority); ?>" data-priority-chip><?php echo e($order->priority_label); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="ris26-row-actions">
                                                <a href="<?php echo e(route('ris.exams.show', $order)); ?>" class="ris26-btn ris26-btn-soft">Ouvrir</a>
                                                <?php if($viewerUrl): ?>
                                                    <a href="<?php echo e($viewerUrl); ?>" target="_blank" rel="noopener" class="ris26-btn ris26-btn-primary">Viewer</a>
                                                <?php endif; ?>
                                                <?php if($order->status === \Modules\RIS\Models\RisOrder::STATUS_ORDONNE): ?>
                                                    <form method="POST" action="<?php echo e(route('ris.exams.waiting', $order)); ?>">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('PATCH'); ?>
                                                        <button type="submit" class="ris26-btn">Attente</button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if($order->status !== \Modules\RIS\Models\RisOrder::STATUS_TERMINE && $order->status !== \Modules\RIS\Models\RisOrder::STATUS_ANNULE): ?>
                                                    <form method="POST" action="<?php echo e(route('ris.exams.worklist', $order)); ?>">
                                                        <?php echo csrf_field(); ?>
                                                        <button type="submit" class="ris26-btn">MWL</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" class="ris26-empty">Aucun examen RIS ne correspond aux filtres.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php if($orphanStudies->isNotEmpty()): ?>
                                    <tr>
                                        <td colspan="6" style="padding: 24px 16px 8px;">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                                                <span style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.08em; color: #92400e; font-weight: 800;">
                                                    Études PACS orphelines — <?php echo e($orphanStudies->count()); ?> trouvée(s)
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                        $orthancViewerBaseUrl = rtrim((string) config('ris.orthanc.viewer_base_url', config('ris.orthanc.base_url', config('services.orthanc.base_url', 'http://127.0.0.1:8042'))), '/');
                                    ?>
                                    <?php $__currentLoopData = $orphanStudies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $study): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $studyViewerUrl = $study->study_uid
                                                ? $orthancViewerBaseUrl.'/stone-webviewer/index.html?study='.urlencode($study->study_uid)
                                                : null;
                                            $studyDate = $study->study_date && strlen($study->study_date) >= 8
                                                ? substr($study->study_date, 0, 4).'/'.substr($study->study_date, 4, 2).'/'.substr($study->study_date, 6, 2)
                                                : ($study->study_date ?: 'Date inconnue');
                                        ?>
                                        <tr class="ris26-row ris26-row-orphan">
                                            <td>
                                                <div class="ris26-entity-title"><?php echo e($study->accession_number ?: 'PACS-'.$study->orthanc_study_id); ?></div>
                                                <div class="ris26-entity-meta"><?php echo e($studyDate); ?></div>
                                            </td>
                                            <td>
                                                <div class="ris26-entity-title"><?php echo e($study->patient_name ?: 'Patient inconnu'); ?></div>
                                                <div class="ris26-entity-meta"><?php echo e($study->patient_id); ?></div>
                                            </td>
                                            <td>
                                                <div class="ris26-entity-title"><?php echo e($study->study_description ?: 'Description non renseignee'); ?></div>
                                                <div class="ris26-entity-meta"><?php echo e($study->modality ?: 'Modalite inconnue'); ?></div>
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
                                                    <?php if($studyViewerUrl): ?>
                                                        <a href="<?php echo e($studyViewerUrl); ?>" target="_blank" rel="noopener" class="ris26-btn ris26-btn-primary">Viewer</a>
                                                    <?php endif; ?>
                                                    <form method="POST" action="<?php echo e(route('ris.exams.import-orphan')); ?>" style="display:inline;">
                                                        <?php echo csrf_field(); ?>
                                                        <input type="hidden" name="orthanc_study_id" value="<?php echo e($study->orthanc_study_id); ?>">
                                                        <input type="hidden" name="study_uid" value="<?php echo e($study->study_uid); ?>">
                                                        <input type="hidden" name="patient_id" value="<?php echo e($study->patient_id); ?>">
                                                        <input type="hidden" name="patient_name" value="<?php echo e($study->patient_name); ?>">
                                                        <input type="hidden" name="accession_number" value="<?php echo e($study->accession_number); ?>">
                                                        <input type="hidden" name="study_description" value="<?php echo e($study->study_description); ?>">
                                                        <input type="hidden" name="modality" value="<?php echo e($study->modality); ?>">
                                                        <input type="hidden" name="study_date" value="<?php echo e($study->study_date); ?>">
                                                        <button type="submit" class="ris26-btn" style="background:#0d9488;color:#fff;border-color:#0d9488;" onclick="return confirm('Importer cette étude PACS dans le RIS ? Un nouvel examen urgent sera créé.');">
                                                            <i class="ti ti-plus"></i> Importer
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="ris26-pagination">
                        <?php echo e($orders->links()); ?>

                    </div>
                </div>
            </section>

            <aside class="ris26-card" id="ris26ReportsAnchor">
                <div class="ris26-panel">
                    <div class="ris26-entity-title" style="margin-bottom: 4px;">Comptes rendus récents</div>
                    <div class="ris26-entity-meta" style="margin-bottom: 14px;">Vue condensée des dernières validations, utile pour reprendre un diagnostic vite.</div>

                    <div class="ris26-reports">
                        <?php $__empty_1 = true; $__currentLoopData = $recentReports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <article class="ris26-report-item">
                                <div class="ris26-toolbar">
                                    <div>
                                        <div class="ris26-entity-title"><?php echo e($report->order?->patient?->full_name ?? 'Patient'); ?></div>
                                        <div class="ris26-entity-meta"><?php echo e($report->order?->procedure?->label ?? 'Acte RIS'); ?></div>
                                    </div>
                                    <?php if($report->order): ?>
                                        <a href="<?php echo e(route('ris.exams.show', $report->order)); ?>" class="ris26-btn ris26-btn-soft">Ouvrir</a>
                                    <?php endif; ?>
                                </div>
                                <div class="ris26-entity-meta" style="margin-top: 10px;"><?php echo e(\Illuminate\Support\Str::limit(strip_tags($report->content), 150)); ?></div>
                                <div class="ris26-entity-meta" style="margin-top: 10px;">
                                    Valide le <?php echo e(optional($report->validated_at)->format('d/m/Y H:i')); ?>

                                    <?php if($report->signingPhysician): ?>
                                        | <?php echo e($report->signingPhysician->display_name); ?>

                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="ris26-empty">Aucun compte rendu signe pour le moment.</div>
                        <?php endif; ?>
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
                <form method="POST" action="<?php echo e(route('ris.exams.store')); ?>" class="ris26-form-grid">
                    <?php echo csrf_field(); ?>
                    <?php if($selectedPatient): ?>
                        <input type="hidden" name="patient_id" value="<?php echo e($selectedPatient->id); ?>">
                        <div class="full ris26-field">
                            <label>Patient</label>
                            <div class="ris26-selected" style="margin-top: 0;">
                                <div class="ris26-patient">
                                    <div class="ris26-avatar">
                                        <?php if($selectedPatient->patient_photo_path): ?>
                                            <img src="<?php echo e(asset($selectedPatient->patient_photo_path)); ?>" alt="">
                                        <?php else: ?>
                                            <?php echo e($selectedInitials ?: 'P'); ?>

                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="ris26-patient-name"><?php echo e($selectedPatient->full_name); ?></div>
                                        <div class="ris26-patient-meta"><?php echo e($selectedPatient->medical_record_number); ?> | Patient verrouillé par le dossier actif</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="full ris26-field">
                            <label>Patient</label>
                            <div id="ris26PatientPicker" style="display: grid; grid-template-columns: 1fr auto; gap: 8px;">
                                <div style="position: relative;">
                                    <input type="text" id="ris26PatientSearch" name="patient_search" placeholder="Rechercher un patient (nom, tel, MRN...)" autocomplete="off" style="width: 100%;">
                                    <input type="hidden" name="patient_id" id="ris26PatientId" value="">
                                    <div id="ris26PatientResults" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; background: #fff; border: 1px solid #d7e5ef; border-radius: 14px; margin-top: 4px; max-height: 260px; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.10);"></div>
                                </div>
                                <button type="button" id="ris26NewPatientBtn" class="ris26-btn" style="white-space: nowrap;" title="Creer un nouveau patient">
                                    <i class="ti ti-plus"></i> Nouveau
                                </button>
                            </div>
                            <div id="ris26SelectedPatientBadge" style="display: none; margin-top: 8px;">
                                <div class="ris26-selected" style="padding: 10px 12px;">
                                    <div class="ris26-patient">
                                        <div class="ris26-avatar" style="width: 36px; height: 36px; border-radius: 12px; font-size: 0.82rem;">
                                            <span id="ris26SelInitials"></span>
                                        </div>
                                        <div>
                                            <div class="ris26-patient-name" style="font-size: 0.92rem;" id="ris26SelName"></div>
                                            <div class="ris26-patient-meta" style="font-size: 0.82rem;" id="ris26SelMeta"></div>
                                        </div>
                                    </div>
                                    <button type="button" id="ris26ClearPatient" class="ris26-btn" style="font-size: 0.78rem; padding: 6px 10px;">Changer</button>
                                </div>
                            </div>
                        </div>
                        <div id="ris26NewPatientForm" class="full ris26-field" style="display: none; border: 1px solid #d7e5ef; border-radius: 18px; padding: 14px; background: #f8fafc;">
                            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <i class="ti ti-user-plus" style="color: var(--ris-accent);"></i>
                                <span>Nouveau patient</span>
                            </label>
                            <div class="ris26-form-grid">
                                <div class="ris26-field">
                                    <label>Prenom *</label>
                                    <input type="text" id="ris26NewFirstName" placeholder="Prenom" style="width: 100%;">
                                </div>
                                <div class="ris26-field">
                                    <label>Nom *</label>
                                    <input type="text" id="ris26NewLastName" placeholder="Nom" style="width: 100%;">
                                </div>
                                <div class="ris26-field">
                                    <label>Date naissance *</label>
                                    <input type="date" id="ris26NewDob" style="width: 100%;">
                                </div>
                                <div class="ris26-field">
                                    <label>Telephone</label>
                                    <input type="text" id="ris26NewPhone" placeholder="0600000000" style="width: 100%;">
                                </div>
                                <div class="ris26-field">
                                    <label>Email</label>
                                    <input type="email" id="ris26NewEmail" placeholder="patient@exemple.com" style="width: 100%;">
                                </div>
                                <div class="ris26-field full" style="display: flex; gap: 10px; justify-content: flex-end; padding-top: 6px;">
                                    <button type="button" id="ris26NewPatientCancel" class="ris26-btn">Annuler</button>
                                    <button type="button" id="ris26NewPatientSave" class="ris26-btn ris26-btn-primary">Creer le patient</button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="ris26-field">
                        <label>Acte RIS</label>
                        <select name="procedure_id" required>
                            <option value="">Choisir</option>
                            <?php $__currentLoopData = $procedures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $procedure): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($procedure->id); ?>" <?php if((int) old('procedure_id') === (int) $procedure->id): echo 'selected'; endif; ?>><?php echo e($procedure->label); ?> | <?php echo e(number_format((float) $procedure->price, 2, ',', ' ')); ?> MAD</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="ris26-field">
                        <label>Modalite</label>
                        <select name="modality_id" required>
                            <option value="">Choisir</option>
                            <?php $__currentLoopData = $modalities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $modality): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($modality->id); ?>" <?php if((int) old('modality_id') === (int) $modality->id): echo 'selected'; endif; ?>><?php echo e($modality->name); ?> | <?php echo e(strtoupper($modality->ae_title)); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="ris26-field">
                        <label>Priorite</label>
                        <select name="priority" required>
                            <?php $__currentLoopData = $priorityLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $priorityValue => $priorityLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($priorityValue); ?>" <?php if(old('priority', \Modules\RIS\Models\RisOrder::PRIORITY_ROUTINE) === $priorityValue): echo 'selected'; endif; ?>><?php echo e($priorityLabel); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="ris26-field">
                        <label>Demandeur</label>
                        <select name="requested_by_user_id">
                            <option value="">Utilisateur connecte</option>
                            <?php $__currentLoopData = $requesters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requester): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($requester->id); ?>" <?php if((int) old('requested_by_user_id') === (int) $requester->id): echo 'selected'; endif; ?>><?php echo e($requester->display_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="ris26-field">
                        <label>Date demande</label>
                        <input type="datetime-local" name="requested_at" value="<?php echo e(old('requested_at', now()->format('Y-m-d\TH:i'))); ?>">
                    </div>

                    <div class="ris26-field">
                        <label>Date planifiee</label>
                        <input type="datetime-local" name="scheduled_at" value="<?php echo e(old('scheduled_at')); ?>">
                    </div>

                    <div class="full ris26-field">
                        <label>Indication clinique</label>
                        <textarea name="clinical_indication" rows="5" placeholder="Douleur, contrôle, suspicion apicale, bilan implantaire..."><?php echo e(old('clinical_indication')); ?></textarea>
                    </div>

                    <div class="full ris26-actions" style="justify-content: space-between;">
                        <label class="ris26-entity-meta" style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="sync_to_orthanc" value="1" <?php if(old('sync_to_orthanc', true)): echo 'checked'; endif; ?>>
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
                const response = await fetch('<?php echo e(route('ris.patients.select')); ?>', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ patient_id: patientId }),
                });

                const payload = await response.json();
                window.location.href = payload.redirect || '<?php echo e(route('ris.exams.index')); ?>';
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
                const response = await fetch(`<?php echo e(route('ris.spotlight')); ?>?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json' },
                    signal: spotlightController.signal,
                });
                const payload = await response.json();
                renderSpotlight(payload);
            };

            const updateLiveUi = (payload) => {
                Object.entries(payload.stats || {}).forEach(([key, value]) => {
                    const node = document.querySelector(`[data-kpi-value="${key}"]`);
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
                const url = new URL('<?php echo e(route('ris.exams.live')); ?>', window.location.origin);
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

        // Patient search autocomplete
        (function() {
            const searchInput = document.getElementById('ris26PatientSearch');
            const hiddenInput = document.getElementById('ris26PatientId');
            const resultsContainer = document.getElementById('ris26PatientResults');
            const selectedBadge = document.getElementById('ris26SelectedPatientBadge');
            const selName = document.getElementById('ris26SelName');
            const selMeta = document.getElementById('ris26SelMeta');
            const selInitials = document.getElementById('ris26SelInitials');
            const clearBtn = document.getElementById('ris26ClearPatient');
            const picker = document.getElementById('ris26PatientPicker');
            const newPatientForm = document.getElementById('ris26NewPatientForm');
            const newPatientBtn = document.getElementById('ris26NewPatientBtn');
            const cancelNewBtn = document.getElementById('ris26NewPatientCancel');
            const saveNewBtn = document.getElementById('ris26NewPatientSave');

            if (!searchInput) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            let searchTimer = null;
            let selectedPatient = null;

            const renderResults = (patients) => {
                if (!patients.length) {
                    resultsContainer.innerHTML = '<div style="padding: 12px 14px; color: #64748b; font-size: 0.88rem;">Aucun patient trouvé</div>';
                    resultsContainer.style.display = 'block';
                    return;
                }
                resultsContainer.innerHTML = patients.map(p =>
                    `<button type="button" class="ris26-patient-result" data-id="${p.id}" data-name="${p.full_name}" data-mrn="${p.medical_record_number || ''}" data-info="${p.phone || ''}" style="display: flex; align-items: center; gap: 12px; width: 100%; border: 0; background: transparent; padding: 10px 14px; text-align: left; cursor: pointer; border-bottom: 1px solid #f1f5f9;">
                        <span style="width: 36px; height: 36px; border-radius: 12px; background: linear-gradient(135deg, #0d9488, #2dd4bf); color: #fff; display: grid; place-items: center; font-weight: 800; font-size: 0.82rem; flex: 0 0 auto;">${p.initials || 'P'}</span>
                        <span>
                            <strong style="display: block; font-size: 0.92rem;">${p.full_name}</strong>
                            <span style="color: #64748b; font-size: 0.82rem;">${p.medical_record_number || '-'} ${p.phone ? '| ' + p.phone : ''}</span>
                        </span>
                    </button>`
                ).join('');
                resultsContainer.style.display = 'block';
            };

            searchInput.addEventListener('input', () => {
                const q = searchInput.value.trim();
                if (selectedPatient) {
                    selectedPatient = null;
                    hiddenInput.value = '';
                }
                clearTimeout(searchTimer);
                if (q.length < 2) {
                    resultsContainer.style.display = 'none';
                    return;
                }
                searchTimer = setTimeout(async () => {
                    try {
                        const res = await fetch(`<?php echo e(route('ris.patients.search')); ?>?q=${encodeURIComponent(q)}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        renderResults(data.results || []);
                    } catch { }
                }, 250);
            });

            resultsContainer.addEventListener('click', (e) => {
                const btn = e.target.closest('.ris26-patient-result');
                if (!btn) return;
                selectPatient({
                    id: btn.dataset.id,
                    full_name: btn.dataset.name,
                    medical_record_number: btn.dataset.mrn,
                    phone: btn.dataset.info,
                });
            });

            const selectPatient = (patient) => {
                selectedPatient = patient;
                hiddenInput.value = patient.id;
                searchInput.value = patient.full_name;
                resultsContainer.style.display = 'none';
                if (picker) picker.style.display = 'none';
                selectedBadge.style.display = 'block';
                selName.textContent = patient.full_name;
                selMeta.textContent = (patient.medical_record_number || 'MRN-...') + (patient.phone ? ' | ' + patient.phone : '');
                const parts = patient.full_name.split(' ');
                const initials = parts.map(p => p.charAt(0)).join('').toUpperCase().slice(0, 2);
                selInitials.textContent = initials || 'P';
                newPatientForm.style.display = 'none';
            };

            clearBtn.addEventListener('click', () => {
                selectedPatient = null;
                hiddenInput.value = '';
                searchInput.value = '';
                selectedBadge.style.display = 'none';
                if (picker) picker.style.display = 'grid';
            });

            newPatientBtn.addEventListener('click', () => {
                newPatientForm.style.display = 'block';
                newPatientBtn.style.display = 'none';
                if (resultsContainer) resultsContainer.style.display = 'none';
            });

            cancelNewBtn.addEventListener('click', () => {
                newPatientForm.style.display = 'none';
                newPatientBtn.style.display = 'inline-flex';
                document.getElementById('ris26NewFirstName').value = '';
                document.getElementById('ris26NewLastName').value = '';
                document.getElementById('ris26NewDob').value = '';
                document.getElementById('ris26NewPhone').value = '';
                document.getElementById('ris26NewEmail').value = '';
            });

            saveNewBtn.addEventListener('click', async () => {
                const firstName = document.getElementById('ris26NewFirstName').value.trim();
                const lastName = document.getElementById('ris26NewLastName').value.trim();
                const dob = document.getElementById('ris26NewDob').value;
                const phone = document.getElementById('ris26NewPhone').value.trim();
                const email = document.getElementById('ris26NewEmail').value.trim();

                if (!firstName || !lastName || !dob) {
                    saveNewBtn.textContent = 'Champs obligatoires manquants';
                    setTimeout(() => { saveNewBtn.textContent = 'Creer le patient'; }, 2000);
                    return;
                }

                saveNewBtn.disabled = true;
                saveNewBtn.textContent = 'Creation...';

                try {
                    const res = await fetch('<?php echo e(route('ris.patients.create')); ?>', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ first_name: firstName, last_name: lastName, date_of_birth: dob, phone: phone, email: email }),
                    });
                    const data = await res.json();
                    if (data.ok && data.patient) {
                        selectPatient(data.patient);
                        newPatientForm.style.display = 'none';
                        newPatientBtn.style.display = 'inline-flex';
                        cancelNewBtn.click();
                    } else {
                        saveNewBtn.textContent = 'Erreur: ' + (data.message || 'Inconnue');
                        setTimeout(() => { saveNewBtn.textContent = 'Creer le patient'; }, 3000);
                    }
                } catch { }
                saveNewBtn.disabled = false;
                if (saveNewBtn.textContent === 'Creation...') saveNewBtn.textContent = 'Creer le patient';
            });
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xamp8.1\htdocs\medical\Modules\RIS\Providers/../Resources/views/exams/index.blade.php ENDPATH**/ ?>