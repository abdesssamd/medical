@extends('layouts.admin')

@section('title', 'Dashboard Professionnel RDV')
@section('page_pretitle', 'Role Professionnel')
@section('page_title', 'Pilotage Rendez-vous et Capacite')

@section('content')
@php
    $days = [0 => 'Dimanche', 1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];
    $week = collect(range(0, 6))->mapWithKeys(function ($day) use ($plannings) {
        $p = $plannings->get($day);
        return [$day => [
            'day' => $day,
            'start_time' => $p ? substr((string) $p->start_time, 0, 5) : '09:00',
            'end_time' => $p ? substr((string) $p->end_time, 0, 5) : '17:00',
            'consultation_minutes' => $p?->consultation_minutes ?? 20,
            'max_patients_per_day' => $p?->max_patients_per_day ?? 16,
            'is_active' => (bool) ($p?->is_active ?? false),
        ]];
    })->all();

    $goal = (float) ($stats['revenue_goal'] ?? 0);
    $achieved = (float) ($stats['month_commissions'] ?? 0);
    $goalPct = $goal > 0 ? min(100, round(($achieved / $goal) * 100, 1)) : 0;
    $currency = $settings->currency ?? 'MAD';
@endphp

<style>
    .bi-card { border: 1px solid rgba(15, 23, 42, 0.08); border-radius: 16px; box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06); }
    .bi-soft { background: linear-gradient(135deg, #f8fbff 0%, #f4f8ff 100%); }
    .chip { border-radius: 999px; padding: 0.25rem 0.65rem; font-size: 0.75rem; font-weight: 600; border: 1px solid transparent; }
    .chip-blue { color: #1d4ed8; background: #dbeafe; border-color: #bfdbfe; }
    .chip-orange { color: #9a3412; background: #ffedd5; border-color: #fdba74; }
    .chip-red { color: #b91c1c; background: #fee2e2; border-color: #fca5a5; }
    .shortcut-card { min-height: 110px; }
    .kpi-title { color: #475569; font-size: 0.8rem; letter-spacing: .02em; }
    .kpi-value { font-size: 1.35rem; font-weight: 700; color: #0f172a; }
    .stepper { display: flex; gap: .25rem; align-items: center; }
    .step { height: 6px; flex: 1; border-radius: 999px; background: #e2e8f0; }
    .step.active { background: #2563eb; }
    .step.done { background: #16a34a; }
</style>

<div class="card bi-card bi-soft mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Praticien</label>
                <select class="form-select" id="filterProfessional">
                    @foreach($professionals as $pro)
                        <option value="{{ $pro->id }}" @selected((int) $selectedProfessionalId === (int) $pro->id)>{{ $pro->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Fauteuil / Salle</label>
                <select class="form-select" id="filterRoom">
                    <option value="">Tous les fauteuils</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" @selected((int) $selectedRoomId === (int) $room->id)>{{ $room->name }} ({{ $room->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <button class="btn btn-outline-primary w-100" id="btnApplyFilters"><i class="ti ti-filter me-1"></i>Appliquer filtres</button>
            </div>
            <div class="col-lg-3 col-md-6">
                <button class="btn btn-primary w-100" id="btnOptimizeWeek"><i class="ti ti-brain me-1"></i>Optimiser la semaine</button>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards mb-2">
    <div class="col-md-3">
        <a class="card card-link shortcut-card" href="{{ route('care.module2.index') }}">
            <div class="card-body">
                <div class="kpi-title">Flux patient temps reel</div>
                <div class="kpi-value">{{ $stats['today_appointments'] }}</div>
                <div class="text-secondary small">Prochain patient: {{ $nextPatient?->patient?->full_name ?? 'Aucun' }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a class="card card-link shortcut-card" href="{{ route('care.module3.index') }}">
            <div class="card-body">
                <div class="kpi-title">Dossier clinique 3D</div>
                <div class="kpi-value">{{ $stats['today_consulted'] }}</div>
                <div class="text-secondary small">Consultes aujourd'hui</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a class="card card-link shortcut-card" href="{{ route('care.module4.index') }}">
            <div class="card-body">
                <div class="kpi-title">Sterile & laboratoire</div>
                <div class="kpi-value">{{ $stats['remaining_emergency_slots'] }}</div>
                <div class="text-secondary small">Slots urgence restants</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a class="card card-link shortcut-card" href="{{ route('appointment.pro.dashboard') }}">
            <div class="card-body">
                <div class="kpi-title">No-show</div>
                <div class="kpi-value">{{ $stats['no_show_rate'] }}%</div>
                <div class="text-secondary small">{{ $stats['today_no_show'] }} absences aujourd'hui</div>
            </div>
        </a>
    </div>
</div>

<div class="row row-cards">
    <div class="col-lg-4">
        <div class="card bi-card">
            <div class="card-body">
                <div class="kpi-title">CA realise vs objectif</div>
                <div class="kpi-value">{{ number_format($achieved, 2, ',', ' ') }} {{ $currency }}</div>
                <div class="progress mt-2" style="height:8px"><div class="progress-bar bg-blue" style="width: {{ $goalPct }}%"></div></div>
                <div class="small text-secondary mt-1">Objectif: {{ number_format($goal, 2, ',', ' ') }} {{ $currency }} ({{ $goalPct }}%)</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card bi-card">
            <div class="card-body">
                <div class="kpi-title">Taux de recouvrement</div>
                <div class="kpi-value">{{ $stats['collection_rate'] }}%</div>
                <div class="small text-secondary mt-1">Paye: {{ number_format($stats['month_paid_commissions'], 2, ',', ' ') }} {{ $currency }} / Du: {{ number_format($stats['month_commissions'], 2, ',', ' ') }} {{ $currency }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card bi-card">
            <div class="card-body">
                <div class="kpi-title">Panier moyen par patient</div>
                <div class="kpi-value">{{ number_format($stats['avg_basket'], 2, ',', ' ') }} {{ $currency }}</div>
                <div class="small text-secondary mt-1">Annules: {{ $stats['today_cancelled'] }} | No-show: {{ $stats['today_no_show'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards mt-2">
    <div class="col-lg-8">
        <div class="card bi-card">
            <div class="card-header"><h3 class="card-title">Tendance occupation fauteuils (4 semaines)</h3></div>
            <div class="card-body"><canvas id="occupancyChart" height="110"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card bi-card">
            <div class="card-header"><h3 class="card-title">No-show & rappels</h3></div>
            <div class="card-body">
                <div class="mb-2"><span class="chip chip-red">Taux no-show: {{ $stats['no_show_rate'] }}%</span></div>
                <button class="btn btn-outline-danger w-100" id="btnLoadNoShow"><i class="ti ti-phone-call me-1"></i>Generer liste rappel absents</button>
                <div id="noShowResult" class="small text-secondary mt-3">Cliquez pour charger la liste.</div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards mt-2">
    <div class="col-lg-8">
        <x-tabler-card title="Planning hebdomadaire capacite">
            <x-slot:options>
                <x-tabler-button type="button" id="btn-duplicate-monday" variant="outline">
                    <i class="ti ti-copy me-1"></i>Appliquer lundi a tous les jours
                </x-tabler-button>
            </x-slot:options>

            <div class="d-grid gap-2" id="planning-list">
                @foreach($week as $row)
                    @php
                        $start = \Carbon\Carbon::createFromFormat('H:i', $row['start_time']);
                        $end = \Carbon\Carbon::createFromFormat('H:i', $row['end_time']);
                        $openMinutes = max(0, $start->diffInMinutes($end));
                        $occupiedMinutes = (int) ($row['consultation_minutes'] * (int) $row['max_patients_per_day']);
                        $capacityPct = $openMinutes > 0 ? min(100, (int) round(($occupiedMinutes / $openMinutes) * 100)) : 0;
                        $badgeClass = $capacityPct < 80 ? 'chip-blue' : ($capacityPct < 95 ? 'chip-orange' : 'chip-red');
                        $warnLow = $row['is_active'] && $capacityPct < 50;
                        $dayStats = $weekAppointmentsStats[$row['day']] ?? ['scheduled' => 0, 'urgent' => 0];
                        $tooltip = 'Programmes: '.$dayStats['scheduled'].' | Urgences: '.$dayStats['urgent'];
                    @endphp
                    <div class="border rounded-3 p-3 planning-row" data-day="{{ $row['day'] }}">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <strong class="fs-4">{{ $days[$row['day']] }}</strong>
                                <label class="form-check form-switch mb-0">
                                    <input class="form-check-input toggle-active" type="checkbox" @checked($row['is_active'])>
                                    <span class="form-check-label">{{ $row['is_active'] ? 'Actif' : 'Inactif' }}</span>
                                </label>
                                <span class="chip {{ $badgeClass }} field-capacity-chip" title="{{ $tooltip }}">{{ $capacityPct }}%</span>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-3 text-secondary">
                                <span><i class="ti ti-clock-hour-4 me-1"></i> <span class="field-hours">{{ $row['start_time'] }} - {{ $row['end_time'] }}</span></span>
                                <span><i class="ti ti-hourglass me-1"></i> <span class="field-duration">{{ $row['consultation_minutes'] }}</span> min</span>
                                <span><i class="ti ti-users-group me-1"></i> Quota: <span class="field-quota">{{ $row['max_patients_per_day'] }}</span></span>
                                <x-tabler-button type="button" variant="ghost" class="btn-edit-row" data-day="{{ $row['day'] }}"><i class="ti ti-edit me-1"></i>Modifier</x-tabler-button>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 8px;"><div class="progress-bar field-progress" style="width: {{ $capacityPct }}%"></div></div>
                        <div class="alert alert-warning mt-2 mb-0 py-2 px-3 small @if(!$warnLow) d-none @endif field-warning">Quota faible pour la plage horaire.</div>
                    </div>
                @endforeach
            </div>
        </x-tabler-card>
    </div>

    <div class="col-lg-4">
        <div class="card bi-card">
            <div class="card-header"><h3 class="card-title">Parametres capacite</h3></div>
            <div class="card-body">
                <form id="capacitySettingsForm" class="row g-2">
                    <div class="col-12">
                        <label class="form-label">Slots urgence / jour</label>
                        <input class="form-control" type="number" min="0" max="20" name="emergency_slots_per_day" value="{{ (int) ($settings->emergency_slots_per_day ?? 0) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Objectif CA hebdo ({{ $currency }})</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="weekly_revenue_target" value="{{ (float) ($settings->weekly_revenue_target ?? 0) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Exceptions et conges (YYYY-MM-DD, 1 ligne/date)</label>
                        <textarea class="form-control" rows="3" name="exceptions">{{ collect($settings->capacity_exceptions ?? [])->implode("\n") }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="external_sync_enabled" value="1" @checked($settings->external_sync_enabled)>
                            <span class="form-check-label">Activer synchronisation externe</span>
                        </label>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Provider</label>
                        <select class="form-select" name="external_sync_provider">
                            <option value="">-- Choisir --</option>
                            <option value="google" @selected($settings->external_sync_provider === 'google')>Google Calendar</option>
                            <option value="outlook" @selected($settings->external_sync_provider === 'outlook')>Outlook</option>
                        </select>
                    </div>
                    <div class="col-12 d-grid">
                        <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Enregistrer parametres</button>
                    </div>
                    <div class="col-12 small text-secondary" id="settingsMsg">Les exceptions remplacent le planning type pour les dates indiquees.</div>
                </form>
            </div>
        </div>

        <div class="card bi-card mt-2">
            <div class="card-header"><h3 class="card-title">Suggestions Optimiseur</h3></div>
            <div class="card-body">
                <div id="optimizerResult" class="small text-secondary">Aucune suggestion chargee.</div>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="planningModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier planning: <span id="modalDayName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="planningForm" class="row g-3">
                    <input type="hidden" id="modalDay">
                    <div class="col-md-6"><label class="form-label">Heure debut</label><input type="time" class="form-control" id="modalStart" required></div>
                    <div class="col-md-6"><label class="form-label">Heure fin</label><input type="time" class="form-control" id="modalEnd" required></div>
                    <div class="col-md-6"><label class="form-label">Duree consultation (min)</label><input type="number" min="5" max="180" class="form-control" id="modalDuration" required></div>
                    <div class="col-md-6"><label class="form-label">Quota / jour</label><input type="number" min="1" max="500" class="form-control" id="modalQuota" required></div>
                    <div class="col-12"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" id="modalActive"><span class="form-check-label">Jour actif</span></label></div>
                </form>
            </div>
            <div class="modal-footer">
                <x-tabler-button type="button" variant="outline" data-bs-dismiss="modal">Annuler</x-tabler-button>
                <x-tabler-button type="button" id="btnSavePlanning">Enregistrer</x-tabler-button>
            </div>
        </div>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1090">
    <div id="saveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Pilotage</strong>
            <small>Maintenant</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="saveToastBody">Succes</div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(() => {
    const csrf = '{{ csrf_token() }}';
    const days = @json($days);
    const trend = @json($weeksTrend);
    const modalEl = document.getElementById('planningModal');
    const modal = new bootstrap.Modal(modalEl);
    const toastEl = document.getElementById('saveToast');
    const toast = new bootstrap.Toast(toastEl, { delay: 2400 });
    function notify(msg) { document.getElementById('saveToastBody').textContent = msg; toast.show(); }

    const ctx = document.getElementById('occupancyChart');
    if (ctx && window.Chart) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trend.map(x => x.label),
                datasets: [{
                    label: 'Occupation %',
                    data: trend.map(x => x.rate),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.12)',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 4,
                }],
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, max: 100 } },
            },
        });
    }

    function parseHHMM(value) { const [h, m] = value.split(':').map(Number); return h * 60 + m; }
    function progressClass(pct) {
        if (pct < 80) return 'bg-blue';
        if (pct < 95) return 'bg-orange';
        return 'bg-red';
    }

    function renderRow(day, plan) {
        const row = document.querySelector(`.planning-row[data-day="${day}"]`);
        if (!row) return;
        const openMinutes = Math.max(0, parseHHMM(plan.end_time) - parseHHMM(plan.start_time));
        const occupiedMinutes = (Number(plan.consultation_minutes) || 0) * (Number(plan.max_patients_per_day) || 0);
        const pct = openMinutes > 0 ? Math.min(100, Math.round((occupiedMinutes / openMinutes) * 100)) : 0;
        row.querySelector('.field-hours').textContent = `${plan.start_time} - ${plan.end_time}`;
        row.querySelector('.field-duration').textContent = plan.consultation_minutes;
        row.querySelector('.field-quota').textContent = plan.max_patients_per_day;
        const chip = row.querySelector('.field-capacity-chip');
        chip.textContent = `${pct}%`;
        chip.classList.remove('chip-blue', 'chip-orange', 'chip-red');
        chip.classList.add(pct < 80 ? 'chip-blue' : (pct < 95 ? 'chip-orange' : 'chip-red'));
        const progress = row.querySelector('.field-progress');
        progress.style.width = `${pct}%`;
        progress.classList.remove('bg-blue', 'bg-orange', 'bg-red');
        progress.classList.add(progressClass(pct));
        const warning = row.querySelector('.field-warning');
        if (plan.is_active && pct < 50) warning.classList.remove('d-none'); else warning.classList.add('d-none');
        const toggle = row.querySelector('.toggle-active');
        const label = row.querySelector('.form-check-label');
        toggle.checked = !!plan.is_active;
        label.textContent = plan.is_active ? 'Actif' : 'Inactif';
    }

    document.querySelectorAll('.planning-row').forEach((row) => {
        const pct = Number((row.querySelector('.field-capacity-chip')?.textContent || '0').replace('%', ''));
        const progress = row.querySelector('.field-progress');
        progress.classList.add(progressClass(pct));
    });

    document.getElementById('btnApplyFilters')?.addEventListener('click', () => {
        const q = new URLSearchParams(window.location.search);
        q.set('professional_id', document.getElementById('filterProfessional').value);
        const room = document.getElementById('filterRoom').value;
        if (room) q.set('room_id', room); else q.delete('room_id');
        window.location.search = q.toString();
    });

    document.querySelectorAll('.btn-edit-row').forEach(btn => {
        btn.addEventListener('click', () => {
            const day = Number(btn.dataset.day);
            const row = document.querySelector(`.planning-row[data-day="${day}"]`);
            document.getElementById('modalDay').value = day;
            document.getElementById('modalDayName').textContent = days[day] || day;
            document.getElementById('modalStart').value = row.querySelector('.field-hours').textContent.split(' - ')[0];
            document.getElementById('modalEnd').value = row.querySelector('.field-hours').textContent.split(' - ')[1];
            document.getElementById('modalDuration').value = row.querySelector('.field-duration').textContent.trim();
            document.getElementById('modalQuota').value = row.querySelector('.field-quota').textContent.trim();
            document.getElementById('modalActive').checked = row.querySelector('.toggle-active').checked;
            modal.show();
        });
    });

    document.getElementById('btnSavePlanning')?.addEventListener('click', async () => {
        const day = Number(document.getElementById('modalDay').value);
        const payload = {
            start_time: document.getElementById('modalStart').value,
            end_time: document.getElementById('modalEnd').value,
            consultation_minutes: Number(document.getElementById('modalDuration').value),
            max_patients_per_day: Number(document.getElementById('modalQuota').value),
            is_active: document.getElementById('modalActive').checked ? 1 : 0,
        };
        const res = await fetch(`{{ route('appointment.pro.planning.update', ['day' => '__DAY__']) }}`.replace('__DAY__', day), {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(payload),
        });
        if (!res.ok) { notify('Erreur de validation.'); return; }
        const data = await res.json();
        renderRow(day, data.plan);
        modal.hide();
        notify('Planning enregistre avec succes.');
    });

    document.querySelectorAll('.toggle-active').forEach(input => {
        input.addEventListener('change', async () => {
            const row = input.closest('.planning-row');
            const day = Number(row.dataset.day);
            const res = await fetch(`{{ route('appointment.pro.planning.toggle', ['day' => '__DAY__']) }}`.replace('__DAY__', day), {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ is_active: input.checked ? 1 : 0 }),
            });
            if (!res.ok) { input.checked = !input.checked; notify('Impossible de changer l etat.'); return; }
            const data = await res.json();
            renderRow(day, data.plan);
            notify(data.message || 'Etat mis a jour.');
        });
    });

    document.getElementById('btn-duplicate-monday')?.addEventListener('click', async () => {
        const res = await fetch(`{{ route('appointment.pro.planning.duplicate-monday') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({}),
        });
        if (!res.ok) { notify('Duplication impossible.'); return; }
        notify('Lundi applique a tous les jours.');
        setTimeout(() => window.location.reload(), 600);
    });

    document.getElementById('btnOptimizeWeek')?.addEventListener('click', async () => {
        const professional_id = document.getElementById('filterProfessional').value;
        const room_id = document.getElementById('filterRoom').value;
        const res = await fetch(`{{ route('appointment.pro.planning.optimize-week') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ professional_id, room_id: room_id || null }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) { notify(data.message || 'Optimisation impossible.'); return; }
        const wrap = document.getElementById('optimizerResult');
        const rows = (data.suggestions || []).map((s) => `<div class="mb-2"><strong>#${s.appointment_id}</strong> ${s.patient_name || ''}<br><span class="text-secondary">${s.from} -> ${s.to}</span><br><span>${s.reason}</span></div>`);
        wrap.innerHTML = rows.join('') || '<span class="text-secondary">Aucune suggestion.</span>';
        notify(data.message || 'Optimisation terminee.');
    });

    document.getElementById('btnLoadNoShow')?.addEventListener('click', async () => {
        const params = new URLSearchParams({ days: '30', professional_id: document.getElementById('filterProfessional').value });
        const room = document.getElementById('filterRoom').value;
        if (room) params.set('room_id', room);
        const res = await fetch(`{{ route('appointment.pro.planning.no-show-list') }}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) { notify('Liste no-show indisponible.'); return; }
        const html = (data.items || []).slice(0, 12).map((it) => `<div class="mb-1"><strong>${it.patient}</strong> - ${it.slot}<br><span class="text-secondary">${it.phone || '-'} | ${it.email || '-'}</span></div>`).join('');
        document.getElementById('noShowResult').innerHTML = html || '<span class="text-secondary">Aucun no-show recent.</span>';
        notify(`${data.count || 0} patients absents trouves.`);
    });

    document.getElementById('capacitySettingsForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.currentTarget;
        const fd = new FormData(form);
        const payload = {
            emergency_slots_per_day: Number(fd.get('emergency_slots_per_day') || 0),
            weekly_revenue_target: Number(fd.get('weekly_revenue_target') || 0),
            exceptions: String(fd.get('exceptions') || ''),
            external_sync_enabled: fd.get('external_sync_enabled') ? 1 : 0,
            external_sync_provider: String(fd.get('external_sync_provider') || ''),
        };
        const res = await fetch(`{{ route('appointment.pro.planning.capacity-settings') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) { notify('Echec enregistrement parametres.'); return; }
        document.getElementById('settingsMsg').textContent = `${data.message || 'Parametres enregistres.'} Provider: ${data.settings?.external_sync_provider || 'non configure'}`;
        notify(data.message || 'Parametres enregistres.');
    });
})();
</script>
@endpush
