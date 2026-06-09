@extends('layouts.admin')

@section('title', 'Détail accès portail')
@section('page-title', 'Détail de l’accès patient')

@section('content')
<style>
    .ppd-wrap { display:grid; gap:16px; }
    .ppd-hero { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; padding:18px; border:1px solid #dbe8f3; border-radius:18px; background:#fff; box-shadow:0 18px 40px rgba(15,23,42,0.06); }
    .ppd-grid { display:grid; grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr); gap:16px; }
    .ppd-card { background:#fff; border:1px solid #dbe8f3; border-radius:18px; box-shadow:0 18px 40px rgba(15,23,42,0.06); }
    .ppd-card-inner { padding:18px; }
    .ppd-stat { display:grid; gap:4px; padding:14px 0; border-bottom:1px solid #eef2f7; }
    .ppd-stat:last-child { border-bottom:0; }
    .ppd-label { color:#64748b; text-transform:uppercase; letter-spacing:.08em; font-size:.72rem; font-weight:900; }
    .ppd-value { font-weight:800; }
    .ppd-chip { display:inline-flex; padding:4px 10px; border-radius:999px; background:#e0f2fe; color:#075985; font-weight:900; font-size:.74rem; }
    .ppd-chip.ok { background:#dcfce7; color:#166534; }
    .ppd-chip.warn { background:#fef3c7; color:#92400e; }
    .ppd-chip.err { background:#fee2e2; color:#991b1b; }
    .ppd-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .ppd-btn { display:inline-flex; align-items:center; justify-content:center; min-height:40px; border:1px solid #cbd5e1; border-radius:12px; background:#fff; padding:8px 14px; color:#0f172a; text-decoration:none; font-weight:800; }
    .ppd-btn-primary { background:#2563eb; border-color:#2563eb; color:#fff; }
    .ppd-btn-danger { background:#fee2e2; border-color:#fecaca; color:#991b1b; }
    .ppd-qr { display:grid; gap:14px; justify-items:center; }
    .ppd-qr svg { width:190px; height:190px; background:#fff; padding:10px; border:1px solid #dbe8f3; border-radius:18px; }
    .ppd-log { display:grid; gap:10px; max-height: 420px; overflow:auto; }
    .ppd-log-item { border:1px solid #e2e8f0; border-radius:14px; padding:12px; background:#fbfdff; }
    .ppd-small { color:#64748b; font-size:.82rem; line-height:1.55; }
    @media (max-width: 1100px) { .ppd-grid, .ppd-hero { grid-template-columns:1fr; display:grid; } }
</style>

<div class="ppd-wrap">
    <section class="ppd-hero">
        <div>
            <div class="ppd-chip {{ $access->isRevoked() ? 'err' : ($access->isLocked() ? 'warn' : 'ok') }}">{{ $access->isRevoked() ? 'Révoqué' : ($access->isLocked() ? 'Verrouillé' : ($access->isExpired() ? 'Expiré' : 'Actif')) }}</div>
            <h2 style="margin:10px 0 8px; font-size:1.25rem; font-weight:900;">{{ $access->patient?->full_name ?? 'Patient' }}</h2>
            <div class="ppd-small">MRN {{ $access->patient?->medical_record_number ?? '-' }} • Examen {{ $access->order?->procedure?->label ?? '-' }}</div>
        </div>
        <div class="ppd-actions">
            <a class="ppd-btn" href="{{ route('patient-portal.admin.index') }}">Retour</a>
            <a class="ppd-btn ppd-btn-primary" href="{{ route('patient-portal.admin.memo', $access) }}" target="_blank">Imprimer mémo</a>
            <a class="ppd-btn" href="{{ route('patient-portal.login', ['token' => $access->access_token]) }}" target="_blank">Ouvrir portail</a>
            <form method="POST" action="{{ route('patient-portal.admin.send-email', $access) }}" style="display:inline;">
                @csrf
                <button type="submit" class="ppd-btn" {{ ! $access->patient?->email ? 'disabled' : '' }}>
                    Envoyer par email
                </button>
            </form>
        </div>
    </section>

    <section class="ppd-grid">
        <div class="ppd-card">
            <div class="ppd-card-inner">
                <div class="ppd-stat"><div class="ppd-label">Token</div><div class="ppd-value">{{ $access->access_token }}</div></div>
                <div class="ppd-stat"><div class="ppd-label">Code visible</div><div class="ppd-value">{{ $access->access_code_last4 ? '•••• '. $access->access_code_last4 : '-' }}</div></div>
                <div class="ppd-stat"><div class="ppd-label">Lien portail</div><div class="ppd-value" style="word-break:break-all;">{{ $portalUrl }}</div></div>
                <div class="ppd-stat"><div class="ppd-label">Expiration</div><div class="ppd-value">{{ optional($access->expires_at)->format('d/m/Y H:i') ?: '-' }}</div></div>
                <div class="ppd-stat"><div class="ppd-label">Dernière ouverture</div><div class="ppd-value">{{ optional($access->last_access_at)->format('d/m/Y H:i') ?: '-' }}</div></div>
                <div class="ppd-stat"><div class="ppd-label">Email patient</div><div class="ppd-value">{{ $access->patient?->email ?: 'Non renseigné' }}</div></div>
                <div class="ppd-stat"><div class="ppd-label">Canal</div><div class="ppd-value">{{ $access->delivery_channel }}</div></div>
                <div class="ppd-stat"><div class="ppd-label">Accès verrouillé</div><div class="ppd-value">{{ $access->locked_until_at ? optional($access->locked_until_at)->format('d/m/Y H:i') : 'Non' }}</div></div>
            </div>
        </div>

        <div class="ppd-card">
            <div class="ppd-card-inner ppd-qr">
                <div>
                    <div class="ppd-label">QR code patient</div>
                    <div class="ppd-small">À imprimer sur la fiche mémo remise au patient.</div>
                </div>
                @if($qrCodeSvg)
                    {!! $qrCodeSvg !!}
                @endif
                <div class="ppd-small" style="text-align:center; word-break:break-all;">{{ $portalUrl }}</div>
                @if($printableCode)
                    <div class="ppd-chip ok">Code d’accès: {{ $printableCode }}</div>
                @endif
            </div>
        </div>
    </section>

    <section class="ppd-card">
        <div class="ppd-card-inner">
            <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center; margin-bottom:14px;">
                <div>
                    <div style="font-weight:900; font-size:1.02rem;">Journal des événements</div>
                    <div class="ppd-small">Ouvertures, échecs de connexion, téléchargements et vues du viewer.</div>
                </div>
            </div>

            <div class="ppd-log">
                @forelse($access->logs as $log)
                    <div class="ppd-log-item">
                        <strong>{{ $log->event_type }}</strong>
                        <div class="ppd-small">{{ optional($log->created_at)->format('d/m/Y H:i') ?: '-' }} • IP {{ $log->ip_address ?? '-' }}</div>
                        @if(! empty($log->context))
                            <div class="ppd-small">{{ json_encode($log->context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</div>
                        @endif
                    </div>
                @empty
                    <div class="ppd-small">Aucun log disponible.</div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
