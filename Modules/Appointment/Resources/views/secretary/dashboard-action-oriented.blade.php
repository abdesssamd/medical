@extends('layouts.admin')

@section('title', 'Dashboard Secretaire - Planning Journalier')

@section('content')
<div x-data="secretaryDashboard()" x-init="init()" class="sec-dashboard" @keydown.window="handleKeyboardShortcuts">
    <section class="sec-header">
        <div class="sec-header-left">
            <h1>Planning journalier par praticien</h1>
            <p class="sec-subtitle">Pilotage en temps reel de l'agenda et de la salle d'attente</p>
        </div>
        <div class="sec-header-right">
            <button type="button" class="btn btn-primary btn-new-rdv" @click="openNewAppointmentModal()">
                + Nouveau RDV
            </button>
            <button type="button" class="btn btn-light" @click="refreshDashboard()">Rafraichir</button>
        </div>
    </section>

    <section class="kpi-grid">
        <article class="kpi-card">
            <small>Patients du jour</small>
            <strong x-text="kpis.total_patients || 0"></strong>
        </article>
        <article class="kpi-card">
            <small>Attente moyenne</small>
            <strong x-text="(kpis.avg_wait_minutes || 0) + ' min'"></strong>
        </article>
        <article class="kpi-card">
            <small>Dossiers incomplets</small>
            <strong x-text="(kpis.incomplete_files_percent || 0) + '%'" class="txt-warning"></strong>
        </article>
        <article class="kpi-card">
            <small>Urgences critiques</small>
            <strong x-text="kpis.critical_urgencies || 0" class="txt-danger"></strong>
        </article>
    </section>

    <section class="planning-card">
        <header class="planning-toolbar">
            <div class="planning-title">Agenda Time Grid (vertical)</div>
            <div class="planning-filters">
                <input type="date" x-model="selectedDate" @change="onDateChange" class="form-control compact" />
                <select x-model="selectedProfessionalId" @change="onProfessionalChange" class="form-control compact practitioner-select">
                    <option value="">Tous les praticiens</option>
                    <template x-for="professional in professionals" :key="professional.id">
                        <option :value="professional.id" x-text="professional.label"></option>
                    </template>
                </select>
            </div>
        </header>
        <div id="secretary-time-grid"></div>
    </section>

    <section class="waiting-card">
        <header class="waiting-toolbar">
            <div class="waiting-title">Salle d'attente virtuelle</div>
            <div class="table-controls">
                <input
                    type="text"
                    x-model="filterPatient"
                    @input="filteredItems = filterItems()"
                    placeholder="Chercher patient... (Ctrl+F)"
                    class="form-control compact search-input"
                >
                <select x-model="filterUrgency" @change="filteredItems = filterItems()" class="form-control compact">
                    <option value="">Urgence: toutes</option>
                    <option value="critical">Critique</option>
                    <option value="high">Elevee</option>
                    <option value="normal">Normale</option>
                    <option value="low">Faible</option>
                </select>
            </div>
        </header>

        <div class="table-responsive">
            <table class="waiting-table">
                <thead>
                    <tr>
                        <th>Urg.</th>
                        <th>Patient</th>
                        <th>Heure / attente cumulee</th>
                        <th>Statut</th>
                        <th>Prochaine action</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="filteredItems.length === 0">
                        <tr>
                            <td colspan="6" class="empty-cell">Aucun rendez-vous pour ce filtre.</td>
                        </tr>
                    </template>
                    <template x-for="item in filteredItems" :key="item.appointment_id">
                        <tr :class="{ 'is-late': item.late_threshold_exceeded }">
                            <td>
                                <span class="urgency-dot" :class="'urg-' + item.urgency_level"></span>
                            </td>
                            <td>
                                <div class="patient-stack">
                                    <strong x-text="item.patient_name"></strong>
                                    <small x-text="item.phone || '-'" class="txt-muted"></small>
                                </div>
                            </td>
                            <td>
                                <div :class="['wait-text', item.late_threshold_exceeded ? 'wait-danger' : '']" x-text="formatWaitText(item)"></div>
                            </td>
                            <td>
                                <span class="status-chip" :class="statusClass(item.flow_status)" x-text="formatFlowStatus(item.flow_status)"></span>
                            </td>
                            <td>
                                <span class="action-chip" x-text="getActionLabel(item.next_action)"></span>
                            </td>
                            <td>
                                <div class="action-row-buttons">
                                    <button class="checkin-btn" x-show="item.flow_status === 'booked'" title="Check-in" @click="runFlowAction(item, 'check_in')">Check-in</button>
                                    <button class="icon-btn success" title="Appeler / En soin" @click="runFlowAction(item, 'start')">▶</button>
                                    <button class="icon-btn" title="Voir dossier" @click="openPatientRecord(item)">👁</button>
                                    <button class="icon-btn warning" title="Retard / Mettre en pause" @click="runFlowAction(item, 'pause')">⏸</button>
                                    <button class="icon-btn danger" title="Annuler / No-show" @click="runFlowAction(item, 'no_show')">✖</button>
                                    <button class="icon-btn" title="Note rapide" @click="openQuickNote(item)">📝</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>

    <div x-show="modalQuickNote" class="modal-overlay" @click.self="modalQuickNote = false">
        <div class="modal-card">
            <div class="modal-head">
                <h3>Note rapide</h3>
                <button class="icon-btn" @click="modalQuickNote = false">✕</button>
            </div>
            <div class="modal-body">
                <label>Tag</label>
                <select x-model="quickNoteData.tag" class="form-control">
                    <option value="document_missing">Document manquant</option>
                    <option value="insurance_verify">Assurance a verifier</option>
                    <option value="consent_pending">Consentement en attente</option>
                    <option value="payment_issue">Probleme paiement</option>
                    <option value="urgent">Urgent</option>
                    <option value="other">Autre</option>
                </select>
                <label>Message</label>
                <textarea x-model="quickNoteData.message" rows="3" class="form-control" placeholder="Message court..."></textarea>
                <label>Priorite</label>
                <select x-model="quickNoteData.priority" class="form-control">
                    <option value="normal">Normale</option>
                    <option value="high">Elevee</option>
                    <option value="critical">Critique</option>
                </select>
            </div>
            <div class="modal-foot">
                <button class="btn btn-light" @click="modalQuickNote = false">Annuler</button>
                <button class="btn btn-primary" @click="submitQuickNote()">Envoyer</button>
            </div>
        </div>
    </div>

    <div x-show="modalNewAppointment" class="modal-overlay" @click.self="modalNewAppointment = false">
        <div class="modal-card modal-lg">
            <div class="modal-head">
                <h3>Nouveau rendez-vous</h3>
                <button class="icon-btn" @click="modalNewAppointment = false">✕</button>
            </div>
            <div class="modal-body">
                <div class="split-grid">
                    <div>
                        <label>Praticien</label>
                        <select x-model="newAppointment.professional_id" class="form-control">
                            <option value="">Choisir un praticien</option>
                            <template x-for="professional in professionals" :key="professional.id">
                                <option :value="professional.id" x-text="professional.label"></option>
                            </template>
                        </select>
                        <small class="field-error" x-text="validationErrors.professional_id?.[0]" x-show="validationErrors.professional_id"></small>
                    </div>
                    <div>
                        <label>Date</label>
                        <input type="date" x-model="newAppointment.appointment_date" class="form-control">
                        <small class="field-error" x-text="validationErrors.appointment_date?.[0]" x-show="validationErrors.appointment_date"></small>
                    </div>
                    <div>
                        <label>Heure</label>
                        <input type="time" x-model="newAppointment.start_time" class="form-control">
                        <small class="field-error" x-text="validationErrors.start_time?.[0]" x-show="validationErrors.start_time"></small>
                    </div>
                </div>

                <div class="tabs">
                    <button class="tab-btn" :class="{ 'active': newAppointment.mode === 'existing' }" @click="newAppointment.mode = 'existing'">Patient existant</button>
                    <button class="tab-btn" :class="{ 'active': newAppointment.mode === 'express' }" @click="newAppointment.mode = 'express'">Fiche express</button>
                </div>

                <template x-if="newAppointment.mode === 'existing'">
                    <div>
                        <label>Recherche patient</label>
                        <div class="search-input-wrap">
                            <input type="text" x-model="patientSearchQuery" @input="searchPatients()" class="form-control" placeholder="Nom, CIN, MRN...">
                            <span x-show="isSearchingPatients" class="inline-spinner" aria-label="Chargement"></span>
                        </div>
                        <div class="search-results" x-show="patientSearchResults.length">
                            <template x-for="patient in patientSearchResults" :key="patient.id">
                                <button type="button" class="search-item" @click="selectPatient(patient)">
                                    <strong x-text="patient.name"></strong>
                                    <small x-text="(patient.phone || '-') + ' | ' + (patient.mrn || '-')"></small>
                                </button>
                            </template>
                        </div>
                        <p class="txt-muted" x-show="newAppointment.patient_id">Patient selectionne ID: <span x-text="newAppointment.patient_id"></span></p>
                        <small class="field-error" x-text="validationErrors.patient_id?.[0] || validationErrors.first_name?.[0]" x-show="validationErrors.patient_id || validationErrors.first_name"></small>
                    </div>
                </template>

                <template x-if="newAppointment.mode === 'express'">
                    <div class="split-grid">
                        <div>
                            <label>Nom</label>
                            <input type="text" x-model="newAppointment.last_name" class="form-control">
                            <small class="field-error" x-text="validationErrors.last_name?.[0]" x-show="validationErrors.last_name"></small>
                        </div>
                        <div>
                            <label>Prenom</label>
                            <input type="text" x-model="newAppointment.first_name" class="form-control">
                            <small class="field-error" x-text="validationErrors.first_name?.[0]" x-show="validationErrors.first_name"></small>
                        </div>
                        <div style="grid-column: 1 / span 2;">
                            <label>Telephone</label>
                            <input type="text" x-model="newAppointment.phone" class="form-control">
                            <small class="field-error" x-text="validationErrors.phone?.[0]" x-show="validationErrors.phone"></small>
                        </div>
                    </div>
                </template>

                <label>Notes</label>
                <textarea x-model="newAppointment.notes" class="form-control" rows="2"></textarea>
                <small class="field-error" x-text="validationErrors.notes?.[0]" x-show="validationErrors.notes"></small>

                <label class="checkbox-line">
                    <input type="checkbox" x-model="newAppointment.immediate_checkin">
                    <span>Arrivée immédiate (Check-in)</span>
                </label>
            </div>
            <div class="modal-foot">
                <button class="btn btn-light" @click="modalNewAppointment = false">Annuler</button>
                <button class="btn btn-primary" @click="submitNewAppointment()" :disabled="isCreatingAppointment">
                    <span x-show="!isCreatingAppointment">Creer RDV</span>
                    <span x-show="isCreatingAppointment" class="btn-inline-loading">
                        <span class="inline-spinner"></span>
                        Creation...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <div class="shortcuts-help">
        <small>
            Ctrl+F chercher | Q note rapide | R rafraichir | N nouveau RDV
        </small>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/main.min.css">
<style>
.sec-dashboard {
    --bg-soft: #f5f7fb;
    --line: #e5e7eb;
    --ink: #0f172a;
    --muted: #6b7280;
    --blue: #2563eb;
    --red: #dc2626;
    --amber: #d97706;
    --green: #15803d;
    color: var(--ink);
}

.sec-header,
.kpi-grid,
.planning-card,
.waiting-card {
    margin-bottom: 1rem;
}

.sec-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 0.9rem 1rem;
}

.sec-header h1 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
}

.sec-subtitle {
    margin: 0.2rem 0 0;
    color: var(--muted);
    font-size: 0.85rem;
}

.sec-header-right {
    display: flex;
    gap: 0.5rem;
}

.btn-new-rdv {
    font-weight: 700;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.6rem;
}

.kpi-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 0.55rem 0.75rem;
    min-height: 68px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.kpi-card small {
    color: var(--muted);
    font-size: 0.78rem;
}

.kpi-card strong {
    font-size: 1.06rem;
}

.txt-warning { color: var(--amber); }
.txt-danger { color: var(--red); }
.txt-muted { color: var(--muted); }

.planning-card,
.waiting-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 0.85rem;
}

.planning-toolbar,
.waiting-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.65rem;
    gap: 0.6rem;
}

.planning-title,
.waiting-title {
    font-weight: 700;
}

.planning-filters,
.table-controls {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.form-control.compact {
    min-width: 140px;
}

.practitioner-select {
    min-width: 240px;
}

#secretary-time-grid {
    min-height: 520px;
}

.fc .fc-timegrid-axis-cushion,
.fc .fc-timegrid-slot-label-cushion {
    font-size: 0.82rem;
    color: var(--muted);
}

.fc .fc-event {
    border: 0;
    border-radius: 8px;
    padding: 2px 4px;
}

.fc-event.event-blocked {
    background: repeating-linear-gradient(-45deg, #d1d5db, #d1d5db 6px, #9ca3af 6px, #9ca3af 12px) !important;
    color: #1f2937 !important;
}

.waiting-table {
    width: 100%;
    border-collapse: collapse;
}

.waiting-table th,
.waiting-table td {
    border-bottom: 1px solid var(--line);
    padding: 0.6rem;
    font-size: 0.9rem;
    vertical-align: middle;
}

.waiting-table thead th {
    color: var(--muted);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.waiting-table tr.is-late {
    background: #fff7ed;
}

.patient-stack {
    display: flex;
    flex-direction: column;
}

.urgency-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

.urg-critical { background: var(--red); }
.urg-high { background: #f97316; }
.urg-normal { background: #2563eb; }
.urg-low { background: var(--green); }

.wait-text {
    font-size: 0.86rem;
    color: #111827;
}

.wait-danger {
    color: var(--red);
    font-weight: 700;
}

.status-chip,
.action-chip {
    display: inline-flex;
    padding: 0.2rem 0.5rem;
    border-radius: 999px;
    font-size: 0.78rem;
    background: #eef2ff;
    color: #1e3a8a;
}

.status-booked {
    background: #e5e7eb;
    color: #374151;
}

.status-checked-in {
    background: #dcfce7;
    color: #166534;
}

.status-in-care {
    background: #ffedd5;
    color: #9a3412;
}

.status-completed {
    background: #e0f2fe;
    color: #075985;
}

.action-row-buttons {
    display: flex;
    gap: 0.32rem;
    flex-wrap: nowrap;
}

.checkin-btn {
    border: 1px solid #86efac;
    background: #dcfce7;
    color: #166534;
    border-radius: 8px;
    height: 32px;
    padding: 0 0.65rem;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
}

.checkbox-line {
    display: inline-flex !important;
    align-items: center;
    gap: 0.45rem;
    color: #111827 !important;
    font-weight: 600;
}

.checkbox-line input {
    width: 16px;
    height: 16px;
}

.icon-btn {
    border: 1px solid var(--line);
    background: #fff;
    border-radius: 8px;
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.icon-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(15, 23, 42, 0.1);
}

.icon-btn.success { color: var(--green); }
.icon-btn.warning { color: var(--amber); }
.icon-btn.danger { color: var(--red); }

.empty-cell {
    text-align: center;
    color: var(--muted);
    padding: 1rem;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
    background: rgba(15, 23, 42, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-card {
    width: min(640px, 96vw);
    background: #fff;
    border-radius: 12px;
    border: 1px solid var(--line);
    box-shadow: 0 20px 60px rgba(15, 23, 42, 0.24);
}

.modal-lg {
    width: min(860px, 98vw);
}

.modal-head,
.modal-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--line);
}

.modal-foot {
    border-bottom: 0;
    border-top: 1px solid var(--line);
    justify-content: flex-end;
    gap: 0.5rem;
}

.modal-body {
    padding: 0.85rem 1rem;
}

.modal-body label {
    display: block;
    font-size: 0.82rem;
    color: var(--muted);
    margin: 0.4rem 0 0.2rem;
}

.split-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.6rem;
    margin-bottom: 0.5rem;
}

.tabs {
    display: flex;
    gap: 0.35rem;
    margin: 0.75rem 0;
}

.tab-btn {
    border: 1px solid var(--line);
    background: #fff;
    color: #111827;
    border-radius: 999px;
    padding: 0.3rem 0.8rem;
    font-size: 0.85rem;
}

.tab-btn.active {
    background: #dbeafe;
    color: #1d4ed8;
    border-color: #bfdbfe;
}

.search-results {
    border: 1px solid var(--line);
    border-radius: 8px;
    margin-top: 0.35rem;
    max-height: 180px;
    overflow: auto;
}

.search-item {
    width: 100%;
    border: 0;
    background: #fff;
    border-bottom: 1px solid var(--line);
    text-align: left;
    padding: 0.45rem 0.6rem;
    display: flex;
    flex-direction: column;
}

.search-item:last-child {
    border-bottom: 0;
}

.search-item:hover {
    background: #f8fafc;
}

.search-input-wrap {
    position: relative;
}

.search-input-wrap .form-control {
    padding-right: 2.2rem;
}

.inline-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid #d1d5db;
    border-top-color: #2563eb;
    border-radius: 50%;
    display: inline-block;
    animation: spin 0.7s linear infinite;
}

.search-input-wrap .inline-spinner {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
}

.btn-inline-loading {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.field-error {
    color: #dc2626;
    font-size: 0.78rem;
    margin-top: 0.2rem;
    display: block;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.shortcuts-help {
    position: fixed;
    right: 16px;
    bottom: 16px;
    background: #0f172a;
    color: #fff;
    border-radius: 8px;
    padding: 0.45rem 0.7rem;
    font-size: 0.77rem;
}

@media (max-width: 1080px) {
    .kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .planning-toolbar,
    .waiting-toolbar,
    .sec-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .planning-filters,
    .table-controls,
    .sec-header-right {
        width: 100%;
        flex-wrap: wrap;
    }

    .split-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js"></script>
<script>
function secretaryDashboard() {
    const dashboardRoute = `{{ route('secretary.dashboard.data') }}`;
    const quickCreateRoute = `{{ route('secretary.appointments.quick-create') }}`;
    const patientSearchRoute = `{{ route('secretary.patients.search') }}`;
    const noteCreateTemplate = `{{ route('secretary.notes.create', ['appointment' => '__ID__']) }}`;
    const flowActionTemplate = `{{ route('secretary.appointments.flow-action', ['appointment' => '__ID__']) }}`;
    const scheduleTemplate = `{{ route('secretary.appointments.schedule', ['appointment' => '__ID__']) }}`;

    return {
        dashboardItems: @json($dashboardData['items']),
        filteredItems: @json($dashboardData['items']),
        kpis: @json($dashboardData['kpis']),
        selectedDate: @json($date),
        selectedProfessionalId: @json($professionalId),
        professionals: @json($professionals->map(fn ($professional) => [
            'id' => $professional->id,
            'label' => trim(($professional->professional_title ? $professional->professional_title.' ' : '').$professional->name),
        ])->values()),

        filterPatient: '',
        filterUrgency: '',

        modalQuickNote: false,
        modalNewAppointment: false,
        currentPatient: null,

        quickNoteData: {
            tag: 'document_missing',
            message: '',
            priority: 'normal',
        },

        newAppointment: {
            mode: 'existing',
            patient_id: null,
            professional_id: @json($professionalId) || '',
            appointment_date: @json($date),
            start_time: '09:00',
            first_name: '',
            last_name: '',
            phone: '',
            notes: '',
            immediate_checkin: false,
        },

        patientSearchQuery: '',
        patientSearchResults: [],
        calendar: null,
        searchDebounce: null,
        isSearchingPatients: false,
        isCreatingAppointment: false,
        validationErrors: {},

        init() {
            this.selectedProfessionalId = this.selectedProfessionalId ? String(this.selectedProfessionalId) : '';
            this.newAppointment.professional_id = this.newAppointment.professional_id
                ? String(this.newAppointment.professional_id)
                : (this.selectedProfessionalId || (this.professionals[0]?.id ? String(this.professionals[0].id) : ''));
            this.initCalendar();
            this.renderCalendar();
            setInterval(() => this.refreshDashboard(), 30000);
        },

        initCalendar() {
            const container = document.getElementById('secretary-time-grid');
            if (!container || typeof FullCalendar === 'undefined') {
                return;
            }

            this.calendar = new FullCalendar.Calendar(container, {
                locale: 'fr',
                initialView: 'timeGridDay',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridDay,timeGridWeek'
                },
                slotDuration: '00:20:00',
                slotLabelInterval: '01:00:00',
                allDaySlot: false,
                editable: true,
                eventDurationEditable: true,
                selectable: true,
                nowIndicator: true,
                progressiveEventRendering: true,
                height: 'auto',
                slotMinTime: '08:00:00',
                slotMaxTime: '20:00:00',
                initialDate: this.selectedDate,
                select: (info) => {
                    this.openNewAppointmentModal(info.startStr);
                },
                eventDrop: async (info) => {
                    await this.persistEventSchedule(info.event, info.revert);
                },
                eventResize: async (info) => {
                    await this.persistEventSchedule(info.event, info.revert);
                },
                eventDidMount: (info) => {
                    if (info.event.extendedProps.isBlocked) {
                        info.el.classList.add('event-blocked');
                    }
                }
            });

            this.calendar.render();
        },

        onDateChange() {
            if (this.calendar) {
                this.calendar.gotoDate(this.selectedDate);
            }
            this.refreshDashboard();
        },

        onProfessionalChange() {
            this.newAppointment.professional_id = this.selectedProfessionalId
                || (this.professionals[0]?.id ? String(this.professionals[0].id) : '');
            this.refreshDashboard();
        },

        filterItems() {
            return this.dashboardItems.filter((item) => {
                const patientMatch = (item.patient_name || '').toLowerCase().includes((this.filterPatient || '').toLowerCase());
                const urgencyMatch = !this.filterUrgency || item.urgency_level === this.filterUrgency;
                return patientMatch && urgencyMatch;
            });
        },

        renderCalendar() {
            if (!this.calendar) {
                return;
            }

            const events = this.dashboardItems
                .filter((item) => item.appointment_date && item.start_time)
                .map((item) => {
                    const start = `${item.appointment_date}T${(item.start_time || '09:00:00').substring(0, 8)}`;
                    const fallbackEnd = this.addMinutesToTime(item.start_time || '09:00:00', 20);
                    const end = `${item.appointment_date}T${(item.end_time || fallbackEnd).substring(0, 8)}`;
                    const style = this.calendarEventStyle(item);

                    return {
                        id: String(item.appointment_id),
                        title: item.patient_name || 'Patient',
                        start,
                        end,
                        backgroundColor: style.backgroundColor,
                        borderColor: style.borderColor,
                        textColor: style.textColor,
                        extendedProps: {
                            isBlocked: style.isBlocked,
                        }
                    };
                });

            this.calendar.batchRendering(() => {
                this.calendar.removeAllEvents();
                this.calendar.addEventSource(events);
            });
        },

        calendarEventStyle(item) {
            if (item.status === 'no_show' || item.status === 'cancelled') {
                return {
                    backgroundColor: '#9ca3af',
                    borderColor: '#6b7280',
                    textColor: '#111827',
                    isBlocked: true,
                };
            }

            if (item.urgency_level === 'critical' || item.urgency_level === 'high') {
                return {
                    backgroundColor: '#dc2626',
                    borderColor: '#b91c1c',
                    textColor: '#ffffff',
                    isBlocked: false,
                };
            }

            return {
                backgroundColor: '#2563eb',
                borderColor: '#1d4ed8',
                textColor: '#ffffff',
                isBlocked: false,
            };
        },

        async persistEventSchedule(event, revertCallback) {
            try {
                const start = event.start;
                const end = event.end || new Date(start.getTime() + 20 * 60000);
                const payload = {
                    appointment_date: start.toISOString().slice(0, 10),
                    start_time: start.toTimeString().slice(0, 8),
                    end_time: end.toTimeString().slice(0, 8),
                };

                const route = scheduleTemplate.replace('__ID__', event.id);
                const response = await fetch(route, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (!response.ok) {
                    throw new Error('Echec mise a jour planning');
                }

                await this.refreshDashboard();
            } catch (error) {
                if (typeof revertCallback === 'function') {
                    revertCallback();
                }
                alert(error.message || 'Erreur lors de la mise a jour du planning.');
            }
        },

        async refreshDashboard() {
            try {
                const params = new URLSearchParams();
                if (this.selectedDate) {
                    params.set('date', this.selectedDate);
                }
                if (this.selectedProfessionalId) {
                    params.set('professional_id', this.selectedProfessionalId);
                }

                const response = await fetch(`${dashboardRoute}?${params.toString()}`);
                const data = await response.json();

                this.dashboardItems = data.items || [];
                this.kpis = data.kpis || {};
                this.filteredItems = this.filterItems();
                this.renderCalendar();
            } catch (error) {
                alert('Impossible de rafraichir les donnees dashboard.');
            }
        },

        formatWaitText(item) {
            const hhmm = (item.start_time || '--:--').substring(0, 5);
            const minutes = Number(item.wait_minutes || 0);
            if (item.flow_status === 'booked') {
                return `${hhmm} (pas encore arrive)`;
            }
            return `${hhmm} (En attente depuis ${minutes} min)`;
        },

        formatFlowStatus(status) {
            const labels = {
                booked: 'Booked',
                arrived: 'Checked-in',
                in_care: 'In Care',
                awaiting_payment: 'Paiement',
                completed: 'Done',
                cancelled: 'Annule',
            };
            return labels[status] || status || '-';
        },

        statusClass(status) {
            const classes = {
                booked: 'status-booked',
                arrived: 'status-checked-in',
                in_care: 'status-in-care',
                awaiting_payment: 'status-in-care',
                completed: 'status-completed',
            };
            return classes[status] || 'status-booked';
        },

        getActionLabel(action) {
            const labels = {
                check_in: 'Check-in',
                document_missing: 'Document',
                payment_pending: 'Paiement',
                notify_practitioner: 'Notifier',
                checkout: 'Cloturer',
                none: 'RAS',
            };
            return labels[action] || action;
        },

        openQuickNote(item) {
            this.currentPatient = item;
            this.quickNoteData.message = '';
            this.quickNoteData.priority = 'normal';
            this.quickNoteData.tag = 'document_missing';
            this.modalQuickNote = true;
        },

        async submitQuickNote() {
            if (!this.currentPatient?.appointment_id) {
                return;
            }

            if (!this.quickNoteData.message.trim()) {
                alert('Le message est obligatoire.');
                return;
            }

            try {
                const route = noteCreateTemplate.replace('__ID__', this.currentPatient.appointment_id);
                const response = await fetch(route, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(this.quickNoteData),
                });

                if (!response.ok) {
                    throw new Error('Echec creation note');
                }

                this.modalQuickNote = false;
                await this.refreshDashboard();
            } catch (error) {
                alert(error.message || 'Erreur creation note.');
            }
        },

        openNewAppointmentModal(fromDateTime = null) {
            if (fromDateTime) {
                const date = fromDateTime.slice(0, 10);
                const time = fromDateTime.slice(11, 16);
                this.newAppointment.appointment_date = date;
                this.newAppointment.start_time = time;
            }

            if (!this.newAppointment.professional_id) {
                this.newAppointment.professional_id = this.selectedProfessionalId
                    || (this.professionals[0]?.id ? String(this.professionals[0].id) : '');
            }

            this.validationErrors = {};
            this.modalNewAppointment = true;
        },

        async searchPatients() {
            const query = (this.patientSearchQuery || '').trim();
            if (query.length < 2) {
                this.patientSearchResults = [];
                this.isSearchingPatients = false;
                return;
            }

            clearTimeout(this.searchDebounce);
            this.searchDebounce = setTimeout(async () => {
                this.isSearchingPatients = true;
                try {
                    const response = await fetch(`${patientSearchRoute}?q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    this.patientSearchResults = data.items || [];
                } catch (_error) {
                    this.patientSearchResults = [];
                } finally {
                    this.isSearchingPatients = false;
                }
            }, 300);
        },

        selectPatient(patient) {
            this.newAppointment.patient_id = patient.id;
            this.patientSearchQuery = patient.name;
            this.patientSearchResults = [];
        },

        async submitNewAppointment() {
            this.validationErrors = {};
            this.isCreatingAppointment = true;

            try {
                const payload = {
                    professional_id: Number(this.newAppointment.professional_id || 0),
                    appointment_date: this.newAppointment.appointment_date,
                    start_time: this.newAppointment.start_time,
                    notes: this.newAppointment.notes,
                    immediate_checkin: this.newAppointment.immediate_checkin,
                };

                if (this.newAppointment.mode === 'existing') {
                    payload.patient_id = this.newAppointment.patient_id;
                } else {
                    payload.first_name = this.newAppointment.first_name;
                    payload.last_name = this.newAppointment.last_name;
                    payload.phone = this.newAppointment.phone;
                }

                const response = await fetch(quickCreateRoute, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (!response.ok) {
                    const errorBody = await response.json().catch(() => ({}));
                    if (response.status === 422) {
                        this.validationErrors = errorBody.errors || {};
                    }
                    throw new Error(errorBody.message || 'Echec creation rendez-vous');
                }

                this.modalNewAppointment = false;
                this.newAppointment.patient_id = null;
                this.newAppointment.first_name = '';
                this.newAppointment.last_name = '';
                this.newAppointment.phone = '';
                this.newAppointment.immediate_checkin = false;
                this.patientSearchQuery = '';
                this.patientSearchResults = [];
                await this.refreshDashboard();
            } catch (error) {
                alert(error.message || 'Erreur creation rendez-vous.');
            } finally {
                this.isCreatingAppointment = false;
            }
        },

        async runFlowAction(item, action) {
            const needConfirm = action === 'no_show';
            if (needConfirm && !confirm('Confirmer No-show pour ce patient ?')) {
                return;
            }

            try {
                const route = flowActionTemplate.replace('__ID__', item.appointment_id);
                const response = await fetch(route, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action }),
                });

                if (!response.ok) {
                    throw new Error('Action non appliquee');
                }

                await this.refreshDashboard();
            } catch (error) {
                alert(error.message || 'Erreur action salle d\'attente.');
            }
        },

        openPatientRecord(item) {
            if (!item.patient_id) {
                alert('Aucun dossier patient associe.');
                return;
            }

            window.open(`/clinical/patients/${item.patient_id}`, '_blank');
        },

        addMinutesToTime(timeText, minutesToAdd) {
            const base = (timeText || '09:00:00').split(':').map(Number);
            const d = new Date();
            d.setHours(base[0] || 9, base[1] || 0, base[2] || 0, 0);
            d.setMinutes(d.getMinutes() + minutesToAdd);
            return d.toTimeString().slice(0, 8);
        },

        handleKeyboardShortcuts(e) {
            const key = (e.key || '').toLowerCase();

            if (e.ctrlKey && e.key.toLowerCase() === 'f') {
                e.preventDefault();
                document.querySelector('.search-input')?.focus();
            }

            if (key === 'escape') {
                this.modalNewAppointment = false;
                this.modalQuickNote = false;
            }

            if (key === 'enter') {
                const tag = (e.target?.tagName || '').toUpperCase();
                if (tag !== 'TEXTAREA' && this.modalNewAppointment && !this.isCreatingAppointment) {
                    e.preventDefault();
                    this.submitNewAppointment();
                }
            }

            if (key === 'r') {
                this.refreshDashboard();
            }

            if (key === 'n') {
                this.openNewAppointmentModal();
            }

            if (key === 'q' && this.filteredItems.length) {
                this.openQuickNote(this.filteredItems[0]);
            }
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },
    };
}
</script>
@endpush
@endsection
