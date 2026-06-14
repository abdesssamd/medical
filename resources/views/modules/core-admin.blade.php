@extends('layouts.admin')

@section('title', 'Module 1 - Noyau Administratif')
@section('page_pretitle', 'Module 1')
@section('page_title', 'Pilotage Global — KPI & Administration')

@section('content')
<div style="display:grid; gap:20px;">
    {{-- KPI Cards --}}
    <div class="stats-grid">
        <div class="stat-card-modern" style="background:linear-gradient(135deg, #0f766e, #0f172a); color:white; border:none;">
            <div class="stat-label" style="color:rgba(255,255,255,0.7);">CA Total</div>
            <div class="stat-value" style="color:white;">{{ number_format($kpi['ca_total'], 2, ',', ' ') }} MAD</div>
        </div>
        <div class="stat-card-modern" style="background:linear-gradient(135deg, #1d4ed8, #0f172a); color:white; border:none;">
            <div class="stat-label" style="color:rgba(255,255,255,0.7);">Devis acceptés</div>
            <div class="stat-value" style="color:white;">{{ $kpi['quotes']['acceptance_rate_percent'] }}%</div>
        </div>
        <div class="stat-card-modern" style="background:linear-gradient(135deg, #f97316, #0f172a); color:white; border:none;">
            <div class="stat-label" style="color:rgba(255,255,255,0.7);">Annulation/No-show</div>
            <div class="stat-value" style="color:white;">{{ $kpi['appointments']['cancellation_rate_percent'] }}%</div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-icon stat-icon-blue"><i class="ti ti-calendar-time"></i></div>
            <div class="stat-label">Période</div>
            <div class="stat-value" style="font-size:18px;">{{ $from }} → {{ $to }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="content-card">
        <div class="card-header-custom">
            <h3><i class="ti ti-filter" style="margin-right:8px;"></i>Filtres KPI</h3>
        </div>
        <form method="GET" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
            <div class="form-group" style="flex:1; min-width:160px;">
                <label>Du</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control-modern">
            </div>
            <div class="form-group" style="flex:1; min-width:160px;">
                <label>Au</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control-modern">
            </div>
            <div class="form-group">
                <button class="btn-modern btn-primary-modern"><i class="ti ti-refresh"></i> Actualiser</button>
            </div>
        </form>
    </div>

    {{-- Two column section --}}
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
        {{-- Role Assignment --}}
        <div class="content-card">
            <div class="card-header-custom">
                <h3><i class="ti ti-shield" style="margin-right:8px;"></i>Affectation des rôles</h3>
            </div>
            <form method="POST" id="roleForm">
                @csrf
                <div class="form-group">
                    <label for="roleUserSelect">Utilisateur</label>
                    <select class="form-control-modern" id="roleUserSelect" name="user_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Rôles</label>
                    <select class="form-control-modern" name="role_codes[]" multiple size="4">
                        @foreach($roles as $role)
                            <option value="{{ $role->code }}">{{ $role->name }} ({{ $role->code }})</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn-modern btn-outline-modern"><i class="ti ti-device-floppy"></i> Mettre à jour</button>
            </form>
        </div>

        {{-- Accounting Profile --}}
        <div class="content-card">
            <div class="card-header-custom">
                <h3><i class="ti ti-report-money" style="margin-right:8px;"></i>Profil comptable praticien</h3>
            </div>
            <form method="POST" id="accountingForm">
                @csrf
                <div class="form-group">
                    <label>Praticien</label>
                    <select class="form-control-modern" id="accountingUserSelect" name="user_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label>Code entité</label>
                        <input class="form-control-modern" name="entity_code">
                    </div>
                    <div class="form-group">
                        <label>Préfixe facture</label>
                        <input class="form-control-modern" name="invoice_prefix" value="FAC">
                    </div>
                    <div class="form-group">
                        <label>Devise</label>
                        <input class="form-control-modern" name="currency" value="MAD">
                    </div>
                    <div class="form-group">
                        <label>Taxe %</label>
                        <input class="form-control-modern" type="number" step="0.01" name="default_tax_rate" value="0">
                    </div>
                </div>
                <button class="btn-modern btn-success-modern"><i class="ti ti-device-floppy"></i> Enregistrer</button>
            </form>
        </div>
    </div>

    {{-- Permission Overrides --}}
    <div class="content-card">
        <div class="card-header-custom">
            <h3><i class="ti ti-lock" style="margin-right:8px;"></i>Surcharges permissions utilisateur</h3>
        </div>
        <form method="POST" id="permissionForm">
            @csrf
            <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:180px;">
                    <label>Utilisateur</label>
                    <select class="form-control-modern" id="permissionUserSelect" name="user_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="flex:2; min-width:200px;">
                    <label>Permission</label>
                    <select class="form-control-modern" name="permissions[0][code]">
                        @foreach($permissions as $permission)
                            <option value="{{ $permission->code }}">{{ $permission->name }} ({{ $permission->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="flex:1; min-width:120px;">
                    <label>État</label>
                    <select class="form-control-modern" name="permissions[0][is_granted]">
                        <option value="1">Accorder</option>
                        <option value="0">Refuser</option>
                    </select>
                </div>
                <div class="form-group">
                    <button class="btn-modern btn-primary-modern"><i class="ti ti-checks"></i> Appliquer</button>
                </div>
            </div>
        </form>
    </div>

    {{-- CA by Specialty --}}
    <div class="content-card">
        <div class="card-header-custom">
            <h3><i class="ti ti-chart-bar" style="margin-right:8px;"></i>CA par spécialité</h3>
        </div>
        <div class="table-wrap">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Spécialité</th>
                        <th style="text-align:right;">CA</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kpi['ca_by_specialty'] as $row)
                        <tr>
                            <td><span class="badge-modern badge-blue">{{ $row->specialty_name }}</span></td>
                            <td style="text-align:right; font-weight:700;">{{ number_format((float)$row->total, 2, ',', ' ') }} MAD</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" style="text-align:center; color:#94a3b8; padding:40px;">Aucune donnée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const roleForm = document.getElementById('roleForm');
    const roleUserSelect = document.getElementById('roleUserSelect');
    roleForm.addEventListener('submit', () => roleForm.action = `/care/module-1/users/${roleUserSelect.value}/roles`);

    const accountingForm = document.getElementById('accountingForm');
    const accountingUserSelect = document.getElementById('accountingUserSelect');
    accountingForm.addEventListener('submit', () => accountingForm.action = `/care/module-1/users/${accountingUserSelect.value}/accounting-profile`);

    const permissionForm = document.getElementById('permissionForm');
    const permissionUserSelect = document.getElementById('permissionUserSelect');
    permissionForm.addEventListener('submit', () => permissionForm.action = `/care/module-1/users/${permissionUserSelect.value}/permissions`);
})();
</script>
@endpush
