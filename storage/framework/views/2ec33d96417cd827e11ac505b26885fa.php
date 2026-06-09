<?php $__env->startSection('title', 'Module 2 - Agenda et Flux Patient'); ?>
<?php $__env->startSection('page_pretitle', 'Module 2'); ?>
<?php $__env->startSection('page_title', 'Agenda Intelligent et Flux Patient'); ?>

<?php $__env->startSection('content'); ?>
<div class="flow-shell" x-data="patientFlowApp()" x-init="init()">
    <section class="card filter-card">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" class="form-control" name="date" value="<?php echo e($date); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Praticien</label>
                <select class="form-select" name="professional_id">
                    <option value="">Tous</option>
                    <?php $__currentLoopData = $professionals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pro): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($pro->id); ?>" <?php if($selectedProfessionalId === $pro->id): echo 'selected'; endif; ?>><?php echo e($pro->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Filtrer</button>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill" @click="refreshBoard()">Rafraichir</button>
                <button type="button" class="btn btn-outline-dark flex-fill" @click="toggleView()" x-text="viewMode === 'table' ? 'Vue Kanban' : 'Vue Tableau'"></button>
            </div>
        </form>
    </section>

    <section class="kpi-grid">
        <article class="kpi-card kpi-blue">
            <div class="kpi-head"><span class="kpi-icon">users</span><span>Patients Jour</span></div>
            <div class="kpi-value" x-text="kpi.total"></div>
            <svg class="spark" viewBox="0 0 100 30"><polyline :points="spark.total"></polyline></svg>
        </article>
        <article class="kpi-card kpi-sky">
            <div class="kpi-head"><span class="kpi-icon">wait</span><span>Attendu</span></div>
            <div class="kpi-value" x-text="kpi.booked"></div>
            <svg class="spark" viewBox="0 0 100 30"><polyline :points="spark.booked"></polyline></svg>
        </article>
        <article class="kpi-card kpi-orange">
            <div class="kpi-head"><span class="kpi-icon">care</span><span>En soin</span></div>
            <div class="kpi-value" x-text="kpi.inCare"></div>
            <svg class="spark" viewBox="0 0 100 30"><polyline :points="spark.inCare"></polyline></svg>
        </article>
        <article class="kpi-card kpi-green">
            <div class="kpi-head"><span class="kpi-icon">done</span><span>Termine</span></div>
            <div class="kpi-value" x-text="kpi.completed"></div>
            <svg class="spark" viewBox="0 0 100 30"><polyline :points="spark.completed"></polyline></svg>
        </article>
    </section>

    <section class="card schedule-card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">Planning journalier par praticien</h3>
            <div class="text-secondary small">Clique sur un slot libre pour un RDV, bouton Bloquer pour un slot indisponible.</div>
        </div>
        <div class="card-body">
            <div class="schedule-grid">
                <template x-for="row in scheduleRows" :key="row.professional_id">
                    <section class="schedule-row">
                        <div class="schedule-meta">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <div class="fw-bold" x-text="row.professional_name"></div>
                                    <div class="small text-secondary" x-text="row.start_time ? `${row.start_time} - ${row.end_time} · ${row.consultation_minutes} min` : 'Planning inactif'"></div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-dark" @click="openBlockDrawer(row)">Bloquer</button>
                            </div>
                            <div class="slot-summary mt-2">
                                <span class="chip chip-blue">Attendu <span x-text="row.summary?.booked || 0"></span></span>
                                <span class="chip chip-orange">En soin <span x-text="row.summary?.in_care || 0"></span></span>
                                <span class="chip chip-green">Termine <span x-text="row.summary?.completed || 0"></span></span>
                                <span class="chip chip-gray">Bloque <span x-text="row.summary?.blocked || 0"></span></span>
                            </div>
                        </div>
                        <div class="slot-track">
                            <template x-for="slot in row.slots" :key="`${row.professional_id}-${slot.start_time}`">
                                <button
                                    type="button"
                                    class="slot-cell"
                                    :class="slotClass(slot)"
                                    :disabled="slot.status !== 'free'"
                                    @click="slot.status === 'free' ? openAppointmentDrawer(row, slot) : focusSlot(slot)"
                                >
                                    <span class="slot-label" x-text="slot.status === 'free' ? 'Libre' : (slot.label || slot.block_label || slot.patient_name || slot.status)"></span>
                                    <small x-text="`${fmtTime(slot.start_time)} - ${fmtTime(slot.end_time)}`"></small>
                                </button>
                            </template>
                        </div>
                    </section>
                </template>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="section-head"><h3>Salle d attente virtuelle</h3></div>

        <div x-show="viewMode === 'table'" x-cloak class="table-responsive">
            <table class="table table-vcenter align-middle">
                <thead>
                <tr>
                    <th>Heure</th>
                    <th>Patient</th>
                    <th>Praticien</th>
                    <th>Salle</th>
                    <th>Attente</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <template x-for="item in items" :key="item.appointment_id">
                    <tr>
                        <td x-text="fmtTime(item.start_time)"></td>
                        <td>
                            <div class="fw-bold" x-text="item.patient_name"></div>
                            <a class="small text-decoration-none" :href="item.waiting_room_url" target="_blank">lien mobile</a>
                        </td>
                        <td x-text="item.professional_name || '-'"></td>
                        <td x-text="item.room_name || '-'"></td>
                        <td><span class="wait-pill" :class="waitClass(item.wait_minutes)" x-text="item.wait_minutes + ' min'"></span></td>
                        <td><span class="badge" :class="statusBadge(item.flow_status)" x-text="statusLabel(item.flow_status)"></span></td>
                        <td>
                            <div class="action-row">
                                <button class="btn btn-sm btn-outline-primary" @click="callRoom(item)">Appeler</button>
                                <button class="btn btn-sm btn-outline-warning" @click="reschedule(item)">Reporter</button>
                                <button class="btn btn-sm btn-outline-secondary" @click="notify(item)">Notifier</button>
                                <button class="btn btn-sm btn-outline-success" @click="closeItem(item)">Cloturer</button>
                            </div>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>

        <div x-show="viewMode === 'kanban'" x-cloak class="kanban-grid">
            <template x-for="col in kanbanColumns" :key="col.key">
                <div class="kanban-col" @dragover.prevent @drop="dropColumn(col.key)">
                    <div class="kanban-head">
                        <strong x-text="col.label"></strong>
                        <span class="badge bg-azure-lt" x-text="kanbanItems(col.key).length"></span>
                    </div>
                    <template x-for="item in kanbanItems(col.key)" :key="item.appointment_id">
                        <article class="kanban-card" draggable="true" @dragstart="dragStart(item)">
                            <header>
                                <strong x-text="item.patient_name"></strong>
                                <span class="small" x-text="fmtTime(item.start_time)"></span>
                            </header>
                            <div class="small muted" x-text="item.professional_name || '-'"></div>
                            <div class="small mt-1">Attente: <span :class="waitClass(item.wait_minutes)" x-text="item.wait_minutes + ' min'"></span></div>
                        </article>
                    </template>
                </div>
            </template>
        </div>
    </section>

    <section class="card">
        <div class="section-head"><h3>Coordination inter-specialites</h3></div>
        <form class="row g-2 mb-2" @submit.prevent>
            <div class="col-md-3">
                <label class="form-label">Patient</label>
                <select class="form-select" x-model="coord.patient_id">
                    <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($patient->id); ?>"><?php echo e($patient->last_name); ?> <?php echo e($patient->first_name); ?> - <?php echo e($patient->medical_record_number); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">Du</label><input class="form-control" type="date" x-model="coord.from_date"></div>
            <div class="col-md-2"><label class="form-label">Au</label><input class="form-control" type="date" x-model="coord.to_date"></div>
            <div class="col-md-3">
                <label class="form-label">Specialites</label>
                <select class="form-select" multiple x-ref="specSelect">
                    <?php $__currentLoopData = $specialties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $spec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($spec->id); ?>"><?php echo e($spec->name); ?> (<?php echo e($spec->code); ?>)</option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2 d-grid gap-2">
                <button class="btn btn-outline-primary" type="button" @click="suggest()">Suggerer</button>
                <button class="btn btn-outline-dark" type="button" @click="autoSuggest()">Auto dossier</button>
            </div>
        </form>

        <div class="chips-wrap">
            <template x-for="chip in suggestionChips" :key="chip.id">
                <button class="time-chip" @click="bookSuggestion(chip)">
                    <span x-text="chip.label"></span>
                    <small x-text="chip.meta"></small>
                </button>
            </template>
            <div class="text-secondary" x-show="!suggestionChips.length" x-text="coordMessage"></div>
        </div>
    </section>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="slotDrawer" aria-labelledby="slotDrawerLabel">
        <div class="offcanvas-header border-bottom">
            <div>
                <h5 class="offcanvas-title" id="slotDrawerLabel">Creneau interactif</h5>
                <div class="small text-secondary" x-text="slotDrawerSubtitle"></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form class="d-grid gap-3" @submit.prevent="submitSlotDrawer()">
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="drawerMode" id="drawerModeAppointment" value="appointment" x-model="drawer.mode">
                    <label class="btn btn-outline-primary" for="drawerModeAppointment">Rendez-vous</label>
                    <input type="radio" class="btn-check" name="drawerMode" id="drawerModeBlock" value="block" x-model="drawer.mode">
                    <label class="btn btn-outline-secondary" for="drawerModeBlock">Blocage</label>
                </div>

                <template x-if="drawer.mode === 'appointment'">
                    <div class="d-grid gap-3">
                        <div>
                            <label class="form-label">Patient</label>
                            <input class="form-control" type="search" placeholder="Rechercher un patient" x-model="patientSearch.query" @input="queuePatientSearch()">
                            <div class="list-group mt-2" x-show="patientSearch.results.length">
                                <template x-for="patient in patientSearch.results" :key="patient.id">
                                    <button type="button" class="list-group-item list-group-item-action" @click="selectPatient(patient)">
                                        <div class="fw-bold" x-text="patient.full_name"></div>
                                        <div class="small text-secondary" x-text="patient.medical_record_number"></div>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="alert alert-info py-2 mb-0" x-show="selectedPatient">
                            <div class="fw-bold" x-text="selectedPatient?.full_name"></div>
                            <div class="small" x-text="selectedPatient?.medical_record_number"></div>
                            <div class="small text-secondary" x-text="selectedPatient?.phone || selectedPatient?.email || 'Aucun contact'"></div>
                        </div>
                        <div x-show="!selectedPatient" class="row g-2">
                            <div class="col-12"><label class="form-label">Nom patient</label><input class="form-control" type="text" x-model="drawer.patient_name"></div>
                            <div class="col-6"><label class="form-label">Telephone</label><input class="form-control" type="text" x-model="drawer.patient_phone"></div>
                            <div class="col-6"><label class="form-label">Email</label><input class="form-control" type="email" x-model="drawer.patient_email"></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6"><label class="form-label">Date</label><input class="form-control" type="date" x-model="drawer.appointment_date"></div>
                            <div class="col-6"><label class="form-label">Heure</label><input class="form-control" type="time" x-model="drawer.start_time"></div>
                            <div class="col-12"><label class="form-label">Motif consultation</label><input class="form-control" type="text" x-model="drawer.consultation_reason" required></div>
                            <div class="col-12">
                                <label class="form-label">Type consultation</label>
                                <select class="form-select" x-model="drawer.consultation_type">
                                    <option value="bilan">Bilan</option>
                                    <option value="soins">Soins</option>
                                    <option value="chirurgie">Chirurgie</option>
                                    <option value="controle">Controle</option>
                                </select>
                            </div>
                            <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" rows="3" x-model="drawer.notes"></textarea></div>
                        </div>
                    </div>
                </template>

                <template x-if="drawer.mode === 'block'">
                    <div class="d-grid gap-3">
                        <div class="alert alert-warning py-2 mb-0">Blocage de creneau pour pause, absence, formation.</div>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label">Type blocage</label>
                                <select class="form-select" x-model="drawer.block_type">
                                    <option value="break">Pause</option>
                                    <option value="formation">Formation</option>
                                    <option value="absence">Absence</option>
                                </select>
                            </div>
                            <div class="col-12"><label class="form-label">Libelle</label><input class="form-control" type="text" x-model="drawer.block_label" placeholder="Pause / Urgence seulement"></div>
                            <div class="col-6"><label class="form-label">Debut</label><input class="form-control" type="time" x-model="drawer.start_time"></div>
                            <div class="col-6"><label class="form-label">Fin</label><input class="form-control" type="time" x-model="drawer.end_time"></div>
                        </div>
                    </div>
                </template>

                <div class="d-grid gap-2 mt-2">
                    <button class="btn btn-primary" type="submit" :disabled="drawer.loading">
                        <span x-show="!drawer.loading">Valider</span>
                        <span x-show="drawer.loading">En cours...</span>
                    </button>
                    <button class="btn btn-outline-secondary" type="button" @click="closeDrawer()">Annuler</button>
                </div>
                <div class="small text-secondary" x-text="drawer.message"></div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('head'); ?>
<style>
.flow-shell{display:grid;gap:12px}
.filter-card{padding:12px}
.kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.kpi-card{padding:12px;border-radius:14px;color:#fff;box-shadow:0 8px 24px rgba(15,23,42,.15)}
.kpi-blue{background:linear-gradient(135deg,#1d4ed8,#1e3a8a)}
.kpi-sky{background:linear-gradient(135deg,#0284c7,#0c4a6e)}
.kpi-orange{background:linear-gradient(135deg,#f97316,#9a3412)}
.kpi-green{background:linear-gradient(135deg,#16a34a,#14532d)}
.kpi-head{display:flex;justify-content:space-between;opacity:.92;font-size:.84rem}
.kpi-icon{font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.kpi-value{font-size:1.8rem;font-weight:800;line-height:1.1}
.spark{width:100%;height:24px;margin-top:4px}
.spark polyline{fill:none;stroke:#fff;stroke-width:2}
.section-head{display:flex;justify-content:space-between;align-items:center}
.wait-pill{padding:4px 8px;border-radius:999px;font-weight:700;font-size:.76rem;background:#e2e8f0}
.wait-mid{background:#fde68a;color:#92400e}
.wait-high{background:#fecaca;color:#991b1b}
.action-row{display:flex;gap:4px;flex-wrap:wrap}
.kanban-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.kanban-col{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:8px;min-height:220px}
.kanban-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.kanban-card{background:#fff;border:1px solid #dbeafe;border-radius:10px;padding:8px;margin-bottom:6px;box-shadow:0 4px 12px rgba(15,23,42,.06);cursor:grab}
.muted{color:#64748b}
.chips-wrap{display:flex;flex-wrap:wrap;gap:8px}
.time-chip{border:1px solid #93c5fd;background:#eff6ff;border-radius:10px;padding:8px 10px;text-align:left}
.time-chip small{display:block;color:#475569}
.badge-status-booked{background:#dbeafe;color:#1d4ed8}
.badge-status-arrived{background:#bfdbfe;color:#1e40af}
.badge-status-in_care{background:#ffedd5;color:#c2410c}
.badge-status-awaiting_payment{background:#f3e8ff;color:#7e22ce}
.badge-status-completed{background:#dcfce7;color:#166534}
.schedule-card{overflow:hidden}
.schedule-grid{display:grid;gap:12px}
.schedule-row{display:grid;grid-template-columns:300px 1fr;gap:12px;align-items:stretch;padding:12px;border:1px solid #e2e8f0;border-radius:14px;background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%)}
.schedule-meta{display:flex;flex-direction:column;justify-content:space-between;gap:8px}
.slot-summary{display:flex;flex-wrap:wrap;gap:6px}
.chip-gray{color:#334155;background:#e2e8f0;border-color:#cbd5e1}
.slot-track{display:grid;grid-auto-flow:column;grid-auto-columns:84px;gap:8px;overflow-x:auto;padding-bottom:4px}
.slot-cell{min-height:72px;border-radius:12px;border:1px solid #dbeafe;background:#eff6ff;color:#0f172a;padding:8px;display:flex;flex-direction:column;justify-content:space-between;text-align:left;transition:transform .15s ease,box-shadow .15s ease;box-shadow:0 4px 12px rgba(15,23,42,.04)}
.slot-cell:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 8px 18px rgba(15,23,42,.08)}
.slot-cell:disabled{cursor:default;opacity:1}
.slot-cell small{display:block;font-size:.72rem;color:#475569}
.slot-cell .slot-label{font-size:.78rem;font-weight:700;line-height:1.1}
.slot-free{background:linear-gradient(180deg,#f8fbff 0%,#edf5ff 100%)}
.slot-booked{background:linear-gradient(180deg,#dbeafe 0%,#bfdbfe 100%);border-color:#93c5fd;color:#1d4ed8}
.slot-arrived{background:linear-gradient(180deg,#dbeafe 0%,#bfdbfe 100%);border-color:#93c5fd;color:#1e40af}
.slot-in_care{background:linear-gradient(180deg,#ffedd5 0%,#fed7aa 100%);border-color:#fdba74;color:#c2410c}
.slot-awaiting_payment{background:linear-gradient(180deg,#ffedd5 0%,#fed7aa 100%);border-color:#fdba74;color:#9a3412}
.slot-completed{background:linear-gradient(180deg,#dcfce7 0%,#bbf7d0 100%);border-color:#86efac;color:#166534}
.slot-blocked{background:linear-gradient(180deg,#e5e7eb 0%,#cbd5e1 100%);border-color:#94a3b8;color:#334155}
.offcanvas .list-group-item{cursor:pointer}
@media (max-width:1200px){.kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.kanban-grid{grid-template-columns:1fr 1fr}}
@media (max-width:992px){.schedule-row{grid-template-columns:1fr}.slot-track{grid-auto-columns:76px}}
@media (max-width:768px){.kpi-grid,.kanban-grid{grid-template-columns:1fr}.action-row{flex-direction:column}}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function patientFlowApp() {
    return {
        csrf: '<?php echo e(csrf_token()); ?>',
        dateValue: <?php echo json_encode($date, 15, 512) ?>,
        professionalId: <?php echo json_encode($selectedProfessionalId, 15, 512) ?>,
        viewMode: 'table',
        items: <?php echo json_encode($board['items'], 15, 512) ?>,
        scheduleRows: <?php echo json_encode($scheduleGrid['rows'] ?? [], 15, 512) ?>,
        dragItem: null,
        kpi: { total: 0, booked: 0, inCare: 0, completed: 0 },
        spark: { total: '', booked: '', inCare: '', completed: '' },
        coord: { patient_id: '<?php echo e($patients->first()?->id); ?>', from_date: <?php echo json_encode($date, 15, 512) ?>, to_date: <?php echo json_encode($date, 15, 512) ?> },
        coordMessage: 'Aucune suggestion pour l instant.',
        suggestionChips: [],
        kanbanColumns: [
            { key: 'waiting', label: 'En attente' },
            { key: 'preparing', label: 'En preparation' },
            { key: 'incare', label: 'En soin' },
            { key: 'out', label: 'Sortie' },
        ],
        selectedPatient: null,
        slotDrawerSubtitle: '',
        patientSearch: { query: '', results: [], timer: null },
        drawer: {
            mode: 'appointment',
            professional_id: null,
            appointment_date: <?php echo json_encode($date, 15, 512) ?>,
            start_time: '09:00',
            end_time: '09:20',
            consultation_reason: '',
            consultation_type: 'bilan',
            notes: '',
            patient_id: '',
            patient_name: '',
            patient_phone: '',
            patient_email: '',
            block_type: 'break',
            block_label: 'Pause',
            loading: false,
            message: '',
        },
        drawerInstance: null,

        init() {
            this.recompute();
            this.setupDrawer();
            this.setupRealtime();
            setInterval(() => this.recomputeWait(), 60000);
        },

        setupDrawer() {
            const el = document.getElementById('slotDrawer');
            if (el && window.bootstrap) {
                this.drawerInstance = bootstrap.Offcanvas.getOrCreateInstance(el);
            }
        },

        toggleView() { this.viewMode = this.viewMode === 'table' ? 'kanban' : 'table'; },
        fmtTime(t) { return String(t || '').substring(0, 5); },

        statusLabel(s) {
            const map = { booked: 'Attendu', arrived: 'En preparation', in_care: 'En soin', awaiting_payment: 'Paiement', completed: 'Termine' };
            return map[s] || s;
        },
        statusBadge(s) { return `badge-status-${s}`; },
        slotClass(slot) { return `slot-${slot.status || 'free'}`; },

        waitClass(minutes) {
            if (minutes >= 30) return 'wait-pill wait-high';
            if (minutes >= 15) return 'wait-pill wait-mid';
            return 'wait-pill';
        },

        recomputeWait() {
            const now = new Date();
            this.items = this.items.map((it) => {
                let base = `${this.dateValue}T${this.fmtTime(it.start_time)}:00`;
                if (it.arrived_at) base = it.arrived_at.replace(' ', 'T');
                const diff = Math.max(0, Math.round((now - new Date(base)) / 60000));
                it.wait_minutes = diff;
                return it;
            });
        },

        recompute() {
            this.recomputeWait();
            const by = (status) => this.items.filter((i) => i.flow_status === status).length;
            this.kpi.total = this.items.length;
            this.kpi.booked = by('booked') + by('arrived');
            this.kpi.inCare = by('in_care') + by('awaiting_payment');
            this.kpi.completed = by('completed');
            this.spark.total = this.makeSpark([this.kpi.total, this.kpi.booked, this.kpi.inCare, this.kpi.completed, this.kpi.total]);
            this.spark.booked = this.makeSpark([by('booked'), by('arrived'), by('booked'), by('arrived'), this.kpi.booked]);
            this.spark.inCare = this.makeSpark([by('in_care'), by('awaiting_payment'), by('in_care'), by('awaiting_payment'), this.kpi.inCare]);
            this.spark.completed = this.makeSpark([this.kpi.completed, Math.max(0, this.kpi.completed - 1), this.kpi.completed, this.kpi.completed + 1, this.kpi.completed]);
        },

        makeSpark(values) {
            const max = Math.max(1, ...values);
            return values.map((value, index) => `${index * 25},${28 - (value / max) * 24}`).join(' ');
        },

        kanbanKey(item) {
            if (item.flow_status === 'booked') return 'waiting';
            if (item.flow_status === 'arrived') return 'preparing';
            if (['in_care', 'awaiting_payment'].includes(item.flow_status)) return 'incare';
            return 'out';
        },
        kanbanItems(key) { return this.items.filter((item) => this.kanbanKey(item) === key); },
        dragStart(item) { this.dragItem = item; },

        async dropColumn(key) {
            if (!this.dragItem) return;
            if (key === 'preparing') await this.post(`/care/module-2/appointments/${this.dragItem.appointment_id}/check-in`);
            if (key === 'incare') await this.post(`/care/module-2/appointments/${this.dragItem.appointment_id}/transition`, { status: 'in_care' });
            if (key === 'out') await this.post(`/care/module-2/appointments/${this.dragItem.appointment_id}/close`);
            this.dragItem = null;
            await this.refreshBoard();
        },

        async callRoom(item) {
            const room = prompt('Salle (optionnel)', item.room_name || '');
            await this.post(`/care/module-2/appointments/${item.appointment_id}/call-room`, { room_label: room || null });
            await this.refreshBoard();
        },
        async reschedule(item) {
            const date = prompt('Nouvelle date YYYY-MM-DD', this.dateValue);
            const time = prompt('Nouvelle heure HH:MM', this.fmtTime(item.start_time));
            if (!date || !time) return;
            await this.post(`/care/module-2/appointments/${item.appointment_id}/reschedule`, { appointment_date: date, start_time: time });
            await this.refreshBoard();
        },
        async notify(item) {
            const channel = prompt('Canal: sms, email, both', 'sms') || 'sms';
            const message = prompt('Message retard', 'Nous avons un leger retard, merci de votre patience.') || '';
            await this.post(`/care/module-2/appointments/${item.appointment_id}/notify-delay`, { channel, message });
            alert('Notification envoyee.');
        },
        async closeItem(item) {
            await this.post(`/care/module-2/appointments/${item.appointment_id}/close`);
            await this.refreshBoard();
        },

        openAppointmentDrawer(row, slot) {
            this.selectedPatient = null;
            this.drawer.mode = 'appointment';
            this.drawer.professional_id = row.professional_id;
            this.drawer.appointment_date = this.dateValue;
            this.drawer.start_time = this.fmtTime(slot.start_time);
            this.drawer.end_time = this.fmtTime(slot.end_time);
            this.drawer.consultation_reason = '';
            this.drawer.consultation_type = 'bilan';
            this.drawer.notes = '';
            this.drawer.patient_id = '';
            this.drawer.patient_name = '';
            this.drawer.patient_phone = '';
            this.drawer.patient_email = '';
            this.drawer.message = '';
            this.slotDrawerSubtitle = `${row.professional_name} · ${this.fmtTime(slot.start_time)} - ${this.fmtTime(slot.end_time)}`;
            this.drawerInstance?.show();
        },

        openBlockDrawer(row) {
            const firstFree = row.slots?.find((slot) => slot.status === 'free') || row.slots?.[0] || null;
            this.drawer.mode = 'block';
            this.drawer.professional_id = row.professional_id;
            this.drawer.appointment_date = this.dateValue;
            this.drawer.start_time = this.fmtTime(firstFree?.start_time || '09:00:00');
            this.drawer.end_time = this.fmtTime(firstFree?.end_time || '09:20:00');
            this.drawer.block_type = 'break';
            this.drawer.block_label = 'Pause';
            this.drawer.message = '';
            this.slotDrawerSubtitle = `${row.professional_name} · blocage de creneau`;
            this.drawerInstance?.show();
        },

        closeDrawer() {
            this.drawerInstance?.hide();
        },

        focusSlot(slot) {
            this.drawer.message = slot.label || slot.block_label || slot.patient_name || 'Creneau occupe.';
            this.drawerInstance?.show();
        },

        queuePatientSearch() {
            clearTimeout(this.patientSearch.timer);
            this.patientSearch.timer = setTimeout(() => this.searchPatients(), 220);
        },

        async searchPatients() {
            const query = String(this.patientSearch.query || '').trim();
            if (!query) {
                this.patientSearch.results = [];
                return;
            }
            const res = await fetch(`/care/module-2/patients/search?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } });
            const data = await res.json().catch(() => ({}));
            this.patientSearch.results = data.items || [];
        },

        selectPatient(patient) {
            this.selectedPatient = patient;
            this.drawer.patient_id = patient.id;
            this.drawer.patient_name = patient.full_name;
            this.drawer.patient_phone = patient.phone || '';
            this.drawer.patient_email = patient.email || '';
            this.patientSearch.query = patient.full_name;
            this.patientSearch.results = [];
        },

        async submitSlotDrawer() {
            if (!this.drawer.professional_id) return;
            this.drawer.loading = true;
            this.drawer.message = '';
            try {
                if (this.drawer.mode === 'block') {
                    await this.post('/care/module-2/availability-blocks', {
                        practitioner_id: this.drawer.professional_id,
                        date: this.drawer.appointment_date,
                        start_time: this.drawer.start_time,
                        end_time: this.drawer.end_time,
                        type: this.drawer.block_type,
                        label: this.drawer.block_label,
                    });
                    this.drawer.message = 'Creneau bloque.';
                } else {
                    const payload = {
                        professional_id: this.drawer.professional_id,
                        patient_id: this.drawer.patient_id || null,
                        patient_name: this.selectedPatient ? null : this.drawer.patient_name,
                        patient_phone: this.selectedPatient ? null : this.drawer.patient_phone,
                        patient_email: this.selectedPatient ? null : this.drawer.patient_email,
                        appointment_date: this.drawer.appointment_date,
                        start_time: this.drawer.start_time,
                        consultation_reason: this.drawer.consultation_reason || 'Consultation',
                        consultation_type: this.drawer.consultation_type,
                        notes: this.drawer.notes,
                    };
                    await this.post('/care/module-2/appointments', payload);
                    this.drawer.message = 'Rendez-vous cree.';
                }
                this.closeDrawer();
                await this.refreshBoard();
            } finally {
                this.drawer.loading = false;
            }
        },

        selectedSpecialties() {
            return Array.from(this.$refs.specSelect?.selectedOptions || []).map((option, index) => ({
                specialty_id: Number(option.value),
                priority: index + 1,
            }));
        },

        async suggest() {
            const payload = { ...this.coord, specialties: this.selectedSpecialties() };
            const data = await this.post('/care/module-2/grouped-suggestion', payload);
            this.renderSuggestion(data?.optimal_day, false);
        },

        async autoSuggest() {
            const payload = { patient_id: Number(this.coord.patient_id), from_date: this.coord.from_date, to_date: this.coord.to_date };
            const data = await this.post('/care/module-2/grouped-auto-suggestion', payload);
            this.renderSuggestion(data?.optimal_day, true);
        },

        renderSuggestion(optimalDay, isAuto) {
            if (!optimalDay) {
                this.suggestionChips = [];
                this.coordMessage = isAuto ? 'Auto: aucun chevauchement disponible.' : 'Aucune suggestion.';
                return;
            }
            const slots = optimalDay.slots || [];
            this.suggestionChips = slots.length ? slots.map((slot, index) => ({
                id: `${optimalDay.date}-${index}`,
                date: optimalDay.date,
                label: `${slot.start_time} - ${slot.end_time}`,
                meta: `${slot.practitioner_name || ''} | attente ${optimalDay.total_wait_minutes || 0} min`,
            })) : [{
                id: optimalDay.date,
                date: optimalDay.date,
                label: `Jour optimal ${optimalDay.date}`,
                meta: `attente ${optimalDay.total_wait_minutes || 0} min`,
            }];
            this.coordMessage = '';
        },

        async bookSuggestion(chip) {
            const payload = { patient_id: Number(this.coord.patient_id), date: chip.date, specialties: this.selectedSpecialties() };
            const data = await this.post('/care/module-2/grouped-book', payload);
            const n = (data?.appointments || []).length;
            this.coordMessage = `${n} rendez-vous reserves sur ${chip.date}.`;
            await this.refreshBoard();
        },

        async post(url, body = {}) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                },
                body: JSON.stringify(body),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || 'Erreur');
            }
            return data;
        },

        async refreshBoard() {
            const params = new URLSearchParams({ date: this.dateValue });
            if (this.professionalId) params.set('professional_id', this.professionalId);
            const res = await fetch(`/care/module-2/board-data?${params.toString()}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            this.items = data.items || [];
            this.scheduleRows = data.schedule_grid?.rows || [];
            this.recompute();
        },

        setupRealtime() {
            if (window.Echo) {
                try {
                    window.Echo.channel('care.module2')
                        .listen('.PatientFlowUpdated', () => this.refreshBoard())
                        .listen('.AppointmentUpdated', () => this.refreshBoard())
                        .listen('.PlanningUpdated', () => this.refreshBoard());
                } catch (_) {}
            }

            window.addEventListener('care:data-updated', () => this.refreshBoard());
            window.addEventListener('storage', (event) => {
                if (event.key === 'care.lastDataUpdateAt') this.refreshBoard();
            });
        },
    };
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xamp8.1\htdocs\medical\resources\views/modules/patient-flow.blade.php ENDPATH**/ ?>