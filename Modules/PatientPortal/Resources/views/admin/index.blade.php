@extends('layouts.admin')

@section('title', 'Accès portail patient')
@section('page-title', 'Suivi des accès portail patient')

@section('content')
<style>
    .ppx-wrap { display:grid; gap:16px; }
    .ppx-hero { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; padding:18px; border:1px solid #dbe8f3; border-radius:18px; background:#fff; box-shadow:0 18px 40px rgba(15,23,42,0.06); }
    .ppx-hero h2 { margin:0 0 8px; font-size:1.2rem; font-weight:900; }
    .ppx-hero p { margin:0; color:#64748b; line-height:1.6; }
    .ppx-stats { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:12px; }
    .ppx-stat { background:#fff; border:1px solid #dbe8f3; border-radius:18px; padding:16px; box-shadow:0 10px 28px rgba(15,23,42,0.05); }
    .ppx-label { color:#64748b; text-transform:uppercase; letter-spacing:.08em; font-size:.72rem; font-weight:900; }
    .ppx-value { font-size:1.6rem; font-weight:900; margin-top:6px; }
    .ppx-grid { display:grid; grid-template-columns: minmax(0, 1.3fr) minmax(340px, 0.7fr); gap:16px; align-items:start; }
    .ppx-card { background:#fff; border:1px solid #dbe8f3; border-radius:18px; box-shadow:0 18px 40px rgba(15,23,42,0.06); overflow:hidden; }
    .ppx-card-inner { padding:18px; }
    .ppx-toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between; margin-bottom:14px; }
    .ppx-input, .ppx-select { width:100%; min-height:42px; border:1px solid #cbd5e1; border-radius:12px; padding:10px 12px; background:#fff; }
    .ppx-btn { display:inline-flex; align-items:center; justify-content:center; min-height:40px; border-radius:12px; border:1px solid #cbd5e1; background:#fff; color:#0f172a; font-weight:800; padding:8px 14px; text-decoration:none; }
    .ppx-btn-primary { background:#2563eb; border-color:#2563eb; color:#fff; }
    .ppx-btn-danger { background:#fee2e2; border-color:#fecaca; color:#991b1b; }
    .ppx-table { width:100%; border-collapse:collapse; }
    .ppx-table th, .ppx-table td { padding:12px 10px; border-bottom:1px solid #eef2f7; text-align:left; vertical-align:top; }
    .ppx-table th { color:#64748b; text-transform:uppercase; letter-spacing:.08em; font-size:.72rem; }
    .ppx-badge { display:inline-flex; padding:4px 10px; border-radius:999px; font-size:.74rem; font-weight:900; background:#e0f2fe; color:#075985; }
    .ppx-badge.ok { background:#dcfce7; color:#166534; }
    .ppx-badge.warn { background:#fef3c7; color:#92400e; }
    .ppx-badge.err { background:#fee2e2; color:#991b1b; }
    .ppx-small { color:#64748b; font-size:.82rem; line-height:1.5; }
    .ppx-log { display:grid; gap:10px; }
    .ppx-log-item { border:1px solid #e2e8f0; border-radius:14px; padding:12px; background:#fbfdff; }
    .ppx-log-item strong { display:block; margin-bottom:4px; }
    .ppx-actions { display:flex; gap:8px; flex-wrap:wrap; }
    @media (max-width: 1100px) { .ppx-stats, .ppx-grid { grid-template-columns:1fr; } .ppx-hero { flex-direction:column; } }
</style>

<div class="ppx-wrap">
    <section class="ppx-hero">
        <div>
            <h2>Accès portail patient</h2>
            <p>Suivez les accès générés depuis le RIS, les ouvertures du portail, les échecs de connexion et les téléchargements de PDF.</p>
        </div>
        <div class="ppx-actions">
            <a href="{{ route('ris.exams.index') }}" class="ppx-btn">Retour RIS</a>
            <a href="{{ route('patient-portal.login') }}" class="ppx-btn ppx-btn-primary" target="_blank">Ouvrir le portail</a>
        </div>
    </section>

    <section class="ppx-stats">
        <div class="ppx-stat"><div class="ppx-label">Total</div><div class="ppx-value">{{ $stats['total'] }}</div></div>
        <div class="ppx-stat"><div class="ppx-label">Actifs</div><div class="ppx-value">{{ $stats['active'] }}</div></div>
        <div class="ppx-stat"><div class="ppx-label">Expirés</div><div class="ppx-value">{{ $stats['expired'] }}</div></div>
        <div class="ppx-stat"><div class="ppx-label">Verrouillés</div><div class="ppx-value">{{ $stats['locked'] }}</div></div>
    </section>

    <section class="ppx-grid">
        <div class="ppx-card">
            <div class="ppx-card-inner">
                <div class="ppx-toolbar">
                    <div>
                        <div style="font-size:1.02rem; font-weight:900;">Liste des accès</div>
                        <div class="ppx-small">Recherche par MRN, accession, token ou derniers chiffres du code.</div>
                    </div>
                </div>

                <form method="GET" class="ppx-toolbar" style="margin-bottom:18px;">
                    <div style="flex:1; min-width:240px;">
                        <input class="ppx-input" name="search" value="{{ request('search') }}" placeholder="Rechercher un patient, MRN, accession...">
                    </div>
                    <div style="width:220px;">
                        <select class="ppx-select" name="state">
                            <option value="">Tous les états</option>
                            <option value="active" @selected(request('state') === 'active')>Actifs</option>
                            <option value="expired" @selected(request('state') === 'expired')>Expirés</option>
                            <option value="locked" @selected(request('state') === 'locked')>Verrouillés</option>
                            <option value="revoked" @selected(request('state') === 'revoked')>Révoqués</option>
                        </select>
                    </div>
                    <button class="ppx-btn ppx-btn-primary" type="submit">Filtrer</button>
                    <a class="ppx-btn" href="{{ route('patient-portal.admin.index') }}">Reset</a>
                </form>

                <div style="overflow:auto;">
                    <table class="ppx-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Examen</th>
                                <th>État</th>
                                <th>Accès</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accesses as $access)
                                @php
                                    $isExpired = $access->isExpired();
                                    $isLocked = $access->isLocked();
                                    $isRevoked = $access->isRevoked();
                                    $badgeClass = $isRevoked ? 'err' : ($isLocked ? 'warn' : ($isExpired ? 'warn' : 'ok'));
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $access->patient?->full_name ?? '-' }}</strong><br>
                                        <span class="ppx-small">MRN {{ $access->patient?->medical_record_number ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $access->order?->procedure?->label ?? 'Examen' }}</strong><br>
                                        <span class="ppx-small">{{ $access->order?->accession_number ?? '-' }}</span>
                                    </td>
                                    <td><span class="ppx-badge {{ $badgeClass }}">{{ $isRevoked ? 'Révoqué' : ($isLocked ? 'Verrouillé' : ($isExpired ? 'Expiré' : 'Actif')) }}</span></td>
                                    <td>
                                        <div class="ppx-small">Derniers chiffres: <strong>{{ $access->access_code_last4 ?? '-' }}</strong></div>
                                        <div class="ppx-small">Expire: {{ optional($access->expires_at)->format('d/m/Y H:i') ?: '-' }}</div>
                                        <div class="ppx-small">Logs: {{ $access->logs_count }}</div>
                                    </td>
                                    <td>
                                        <div class="ppx-actions">
                                            <a class="ppx-btn" href="{{ route('patient-portal.admin.show', $access) }}">Détails</a>
                                            <a class="ppx-btn" href="{{ route('patient-portal.admin.memo', $access) }}" target="_blank">Mémo</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="ppx-small">Aucun accès trouvé.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:18px;">{{ $accesses->links() }}</div>
            </div>
        </div>

        <aside class="ppx-card">
            <div class="ppx-card-inner">
                <div style="font-size:1.02rem; font-weight:900; margin-bottom:12px;">Activité récente</div>
                <div class="ppx-log">
                    @forelse($recentLogs as $access)
                        <div class="ppx-log-item">
                            <strong>{{ $access->patient?->full_name ?? '-' }}</strong>
                            <div class="ppx-small">{{ $access->logs->first()?->event_type ?? 'aucun événement' }} • {{ optional($access->last_access_at)->format('d/m/Y H:i') ?: '-' }}</div>
                            <div class="ppx-small">{{ $access->patient?->medical_record_number ?? '-' }}</div>
                        </div>
                    @empty
                        <div class="ppx-small">Aucune activité récente.</div>
                    @endforelse
                </div>
            </div>
        </aside>
    </section>
</div>
@endsection
