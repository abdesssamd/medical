<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portail patient') | MediOffice</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --portal-bg: #07111f;
            --portal-surface: rgba(255,255,255,0.92);
            --portal-line: rgba(148,163,184,0.24);
            --portal-ink: #0f172a;
            --portal-muted: #64748b;
            --portal-primary: #0ea5e9;
            --portal-primary-deep: #0369a1;
            --portal-accent: #14b8a6;
            --portal-shadow: 0 28px 70px rgba(2, 8, 23, 0.18);
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: 'Inter', sans-serif;
            color: var(--portal-ink);
            background:
                radial-gradient(circle at top left, rgba(14,165,233,0.16), transparent 28%),
                radial-gradient(circle at top right, rgba(20,184,166,0.16), transparent 24%),
                linear-gradient(180deg, #f8fbff 0%, #edf4fb 100%);
        }
        .portal-shell { min-height: 100vh; display: grid; grid-template-rows: auto 1fr; }
        .portal-topbar {
            position: sticky; top: 0; z-index: 20;
            display:flex; align-items:center; justify-content:space-between; gap:16px;
            padding: 18px 24px; background: rgba(255,255,255,0.72); backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--portal-line);
        }
        .portal-brand { display:flex; align-items:center; gap:12px; }
        .portal-mark {
            width: 44px; height: 44px; border-radius: 14px;
            background: linear-gradient(135deg, var(--portal-primary), #38bdf8);
            display:grid; place-items:center; color:#fff; font-weight:900;
            box-shadow: 0 16px 34px rgba(14,165,233,0.28);
        }
        .portal-brand-text { display:grid; gap:2px; }
        .portal-brand-text strong { font-size: 0.98rem; }
        .portal-brand-text span { font-size: 0.82rem; color: var(--portal-muted); }
        .portal-main { width: min(1260px, calc(100% - 32px)); margin: 24px auto 40px; }
        .portal-card {
            background: var(--portal-surface); border: 1px solid rgba(219,232,243,0.9); border-radius: 28px;
            box-shadow: var(--portal-shadow); overflow: hidden;
        }
        .portal-alert { margin-bottom: 16px; padding: 14px 16px; border-radius: 16px; font-weight: 700; }
        .portal-alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
        .portal-alert-error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .portal-alert-warning { background: #fff7ed; border: 1px solid #fdba74; color: #9a3412; }
        .portal-grid { display:grid; grid-template-columns: minmax(0, 1.15fr) minmax(330px, 0.85fr); gap: 0; }
        .portal-content { padding: 28px; }
        .portal-side { padding: 28px; border-left: 1px solid rgba(219,232,243,0.9); background: linear-gradient(180deg, rgba(247,251,255,0.96), rgba(239,246,255,0.96)); }
        .portal-section + .portal-section { margin-top: 20px; }
        .portal-eyebrow { color: var(--portal-primary-deep); text-transform: uppercase; letter-spacing: 0.14em; font-size: 0.72rem; font-weight: 900; margin-bottom: 8px; }
        .portal-h1 { margin: 0; font-size: clamp(1.8rem, 3vw, 2.8rem); line-height: 1.02; }
        .portal-subtitle { margin-top: 10px; color: var(--portal-muted); line-height: 1.6; }
        .portal-btn {
            display:inline-flex; align-items:center; justify-content:center; gap:8px;
            min-height: 46px; border: 0; border-radius: 14px; padding: 10px 18px;
            background: linear-gradient(135deg, var(--portal-primary), #38bdf8); color:#fff; font-weight:800;
            text-decoration:none; cursor:pointer; box-shadow: 0 14px 28px rgba(14,165,233,0.22);
        }
        .portal-btn-secondary { background:#fff; color: var(--portal-ink); border:1px solid var(--portal-line); box-shadow:none; }
        .portal-btn-danger { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; box-shadow:none; }
        .portal-grid-2 { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .portal-stat, .portal-panel {
            background: rgba(255,255,255,0.82); border:1px solid rgba(219,232,243,0.95); border-radius: 22px; padding: 18px;
        }
        .portal-stat-label { color: var(--portal-muted); font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 900; margin-bottom: 6px; }
        .portal-stat-value { font-size: 1rem; font-weight: 800; }
        .portal-form { display:grid; gap: 14px; }
        .portal-label { display:block; margin-bottom: 6px; color: var(--portal-muted); text-transform: uppercase; letter-spacing: 0.08em; font-size: 0.72rem; font-weight: 900; }
        .portal-input {
            width: 100%; min-height: 48px; border-radius: 14px; border: 1px solid #cbd5e1; background:#fff;
            padding: 12px 14px; font: inherit; color: var(--portal-ink);
        }
        .portal-input:focus { outline: 2px solid rgba(14,165,233,0.22); border-color: var(--portal-primary); }
        .portal-footer-note { color: var(--portal-muted); font-size: 0.86rem; line-height: 1.55; }
        .portal-qrcode { display:grid; gap: 10px; justify-items:center; text-align:center; }
        .portal-qrcode svg { width: 180px; height: 180px; background:#fff; padding: 10px; border-radius: 18px; border:1px solid rgba(219,232,243,0.95); }
        .portal-list { margin: 0; padding-left: 18px; color: #334155; line-height: 1.65; }
        .portal-chip-row { display:flex; flex-wrap:wrap; gap:8px; }
        .portal-chip { display:inline-flex; align-items:center; min-height: 30px; padding: 4px 10px; border-radius: 999px; background:#e0f2fe; color:#075985; font-weight:800; font-size: 0.76rem; }
        .portal-viewer { width: 100%; min-height: 68vh; border: 0; border-radius: 20px; background: #0f172a; }
        @media (max-width: 980px) {
            .portal-grid { grid-template-columns: 1fr; }
            .portal-side { border-left: 0; border-top: 1px solid rgba(219,232,243,0.9); }
            .portal-grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="portal-shell">
        <header class="portal-topbar">
            <div class="portal-brand">
                <div class="portal-mark">M</div>
                <div class="portal-brand-text">
                    <strong>MediOffice Patient Portal</strong>
                    <span>Accès sécurisé aux résultats d’imagerie</span>
                </div>
            </div>
            <div class="portal-chip-row">
                <span class="portal-chip">MRN + Code + DOB</span>
                <span class="portal-chip">PDF</span>
                <span class="portal-chip">Viewer</span>
            </div>
        </header>

        <main class="portal-main">
            @if(session('success'))
                <div class="portal-alert portal-alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="portal-alert portal-alert-error">{{ session('error') }}</div>
            @endif
            @if(session('warning'))
                <div class="portal-alert portal-alert-warning">{{ session('warning') }}</div>
            @endif

            <div class="portal-card">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
