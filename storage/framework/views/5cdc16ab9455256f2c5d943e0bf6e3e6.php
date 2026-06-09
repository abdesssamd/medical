<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['dashboard' => null, 'selectedPatientId' => 0]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['dashboard' => null, 'selectedPatientId' => 0]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<section id="gynecology-dashboard" class="card gyneco-card" data-care-tab-panel="clinical" data-pregnancy-id="<?php echo e(($dashboard && $dashboard['active_pregnancy']) ? $dashboard['active_pregnancy']->id : ''); ?>">
    <div class="section-head">
        <h3 class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-pink-500"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            Tableau de Bord Obstétrical
        </h3>
        <div class="gyneco-toolbar">
            <button type="button" class="btn btn-sm btn-outline-pink" id="gynecoRefreshBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                Actualiser
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#pregnancyFormModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nouvelle grossesse
            </button>
            <button type="button" class="btn btn-sm btn-outline-pink" data-bs-toggle="modal" data-bs-target="#gynecologicalHistoryModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                Nouveaux Antécédents
            </button>
            <?php if($dashboard && $dashboard['active_pregnancy']): ?>
                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#fetalBiometryModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    Nouvelle échographie
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if($dashboard && $dashboard['active_pregnancy']): ?>
        <?php $ob = $dashboard['obstetric_dashboard']; ?>

        <div class="gyneco-alerts" id="gynecoAlerts">
            <?php $__currentLoopData = $dashboard['alerts']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="gyneco-alert gyneco-alert-<?php echo e($alert['type']); ?>">
                    <strong><?php echo e($alert['message']); ?></strong>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="obstetric-grid">
            <div class="obstetric-card obstetric-card-primary">
                <div class="obstetric-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="obstetric-card-body">
                    <div class="obstetric-label">Âge gestationnel</div>
                    <div class="obstetric-value"><?php echo e($ob['gestational_age_display']); ?></div>
                    <div class="obstetric-sub"><?php echo e($ob['trimester_label']); ?></div>
                </div>
                <div class="obstetric-progress">
                    <div class="obstetric-progress-bar" style="width: <?php echo e(min(100, round(($ob['gestational_age']['weeks'] ?? 0) / 42 * 100))); ?>%"></div>
                </div>
            </div>

            <div class="obstetric-card obstetric-card-calendar">
                <div class="obstetric-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="obstetric-card-body">
                    <div class="obstetric-label">Date présumée d'accouchement</div>
                    <div class="obstetric-value">
                        <?php echo e($ob['estimated_delivery_date'] ? $ob['estimated_delivery_date']->format('d/m/Y') : '-'); ?>

                    </div>
                    <div class="obstetric-sub">
                        <?php if($ob['days_until_delivery'] !== null): ?>
                            <?php if($ob['days_until_delivery'] > 0): ?>
                                Dans <?php echo e($ob['days_until_delivery']); ?> jours
                            <?php elseif($ob['days_until_delivery'] === 0): ?>
                                Aujourd'hui
                            <?php else: ?>
                                Terme dépassé de <?php echo e(abs($ob['days_until_delivery'])); ?> jours
                            <?php endif; ?>
                        <?php else: ?>
                            Non calculée
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="obstetric-card obstetric-card-risk obstetric-card-risk-<?php echo e($ob['risk_level']); ?>">
                <button type="button" class="ob-card-edit" title="Modifier le risque" data-bs-toggle="modal" data-bs-target="#quickEditRiskModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <div class="obstetric-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div class="obstetric-card-body">
                    <div class="obstetric-label">Niveau de risque</div>
                    <div class="obstetric-value">
                        <?php switch($ob['risk_level']):
                            case ('low'): ?> Faible <?php break; ?>
                            <?php case ('moderate'): ?> Modéré <?php break; ?>
                            <?php case ('high'): ?> Élevé <?php break; ?>
                        <?php endswitch; ?>
                    </div>
                    <div class="obstetric-sub"><?php echo e($ob['visit_count']); ?> visite(s) prénatale(s)</div>
                </div>
            </div>

            <div class="obstetric-card obstetric-card-vitals">
                <button type="button" class="ob-card-edit" title="Modifier le poids" data-bs-toggle="modal" data-bs-target="#quickEditWeightModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <div class="obstetric-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div class="obstetric-card-body">
                    <div class="obstetric-label">Prise de poids</div>
                    <div class="obstetric-value">
                        <?php echo e($ob['weight_gain']['total'] !== null ? '+' . $ob['weight_gain']['total'] . ' kg' : '-'); ?>

                    </div>
                    <div class="obstetric-sub">
                        Poids actuel: <?php echo e($ob['weight_gain']['current'] ?? '-'); ?> kg
                    </div>
                </div>
            </div>
        </div>

        <div class="obstetric-sections">
            <div class="obstetric-section">
                <h4>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Prochaines étapes
                </h4>
                <div class="milestone-list">
                    <?php $__empty_1 = true; $__currentLoopData = $ob['next_milestones']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $milestone): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="milestone-item">
                            <span class="milestone-week"><?php echo e($milestone['week']); ?> SA</span>
                            <span class="milestone-label"><?php echo e($milestone['label']); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-secondary small">Aucune étape à venir</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="obstetric-section">
                <h4>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Examens requis (<?php echo e($ob['trimester_label']); ?>)
                </h4>
                <div class="exam-checklist">
                    <?php $__currentLoopData = $ob['completed_exams']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $exam): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="exam-check-item <?php echo e($exam['completed'] ? 'is-done' : 'is-pending'); ?>">
                            <span class="exam-check-icon">
                                <?php if($exam['completed']): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                                <?php endif; ?>
                            </span>
                            <span class="exam-check-label"><?php echo e($exam['label']); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            <div class="obstetric-section">
                <h4>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Sérologies
                </h4>
                <div class="serology-grid">
                    <?php $__currentLoopData = $ob['serology_summary']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name => $result): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="serology-item <?php echo e($result !== 'Non fait' ? 'is-done' : 'is-pending'); ?>">
                            <span class="serology-name"><?php echo e(ucfirst(str_replace('_', ' ', $name))); ?></span>
                            <span class="serology-result"><?php echo e($result); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            <div class="obstetric-section">
                <h4>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Tension artérielle
                </h4>
                <div class="bp-trend-list">
                    <?php $__empty_1 = true; $__currentLoopData = $ob['blood_pressure_trend']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="bp-trend-item <?php echo e($bp['alert'] ? 'bp-alert' : ''); ?>">
                            <span class="bp-date"><?php echo e($bp['date']); ?></span>
                            <span class="bp-value"><?php echo e($bp['systolic']); ?>/<?php echo e($bp['diastolic']); ?></span>
                            <?php if($bp['alert']): ?>
                                <span class="bp-warning">HTA</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-secondary small">Aucune mesure enregistrée</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="obstetric-section">
                <h4>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    Dernières échographies
                </h4>
                <div class="ultrasound-list">
                    <?php $__empty_1 = true; $__currentLoopData = $ob['ultrasounds']->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $us): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="ultrasound-item">
                            <div class="ultrasound-date"><?php echo e($us->exam_date->format('d/m/Y')); ?></div>
                            <div class="ultrasound-biometry">
                                <?php if($us->bip_mm): ?><span class="biom-tag">BIP: <?php echo e($us->bip_mm); ?>mm</span><?php endif; ?>
                                <?php if($us->hc_mm): ?><span class="biom-tag">PC: <?php echo e($us->hc_mm); ?>mm</span><?php endif; ?>
                                <?php if($us->ac_mm): ?><span class="biom-tag">PA: <?php echo e($us->ac_mm); ?>mm</span><?php endif; ?>
                                <?php if($us->fl_mm): ?><span class="biom-tag">LF: <?php echo e($us->fl_mm); ?>mm</span><?php endif; ?>
                                <?php if($us->efw_grams): ?><span class="biom-tag biom-tag-epf">EPF: <?php echo e($us->efw_grams); ?>g</span><?php endif; ?>
                            </div>
                            <?php if($us->conclusion): ?>
                                <div class="ultrasound-conclusion"><?php echo e(Str::limit($us->conclusion, 80)); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-secondary small">Aucune échographie enregistrée</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="gyneco-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-pink-300"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <h4>Aucune grossesse active</h4>
            <p>Cliquez sur "Nouvelle grossesse" pour créer un dossier obstétrical, ou consultez l'historique gynécologique.</p>
        </div>
    <?php endif; ?>
</section>

<?php if($dashboard && $dashboard['active_pregnancy']): ?>
<div class="modal fade" id="quickEditRiskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#fdf2f8,#fce7f3);border-bottom:1px solid #f9a8d4">
                <h5 class="modal-title" style="font-weight:800;color:#be185d;font-size:.92rem">Modifier le niveau de risque</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickEditRiskForm">
                    <div class="qe-risk-options">
                        <?php $__currentLoopData = ['low' => ['Faible', '#16a34a', '#f0fdf4'], 'moderate' => ['Modéré', '#d97706', '#fffbeb'], 'high' => ['Élevé', '#dc2626', '#fef2f2']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $level => [$label, $color, $bg]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="qe-risk-option <?php echo e($ob['risk_level'] === $level ? 'active' : ''); ?>" style="border-color:<?php echo e($ob['risk_level'] === $level ? $color : '#e2e8f0'); ?>;background:<?php echo e($ob['risk_level'] === $level ? $bg : '#fff'); ?>">
                                <input type="radio" name="risk_level" value="<?php echo e($level); ?>" <?php echo e($ob['risk_level'] === $level ? 'checked' : ''); ?> hidden>
                                <span class="qe-risk-dot" style="background:<?php echo e($color); ?>"></span>
                                <?php echo e($label); ?>

                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-sm btn-pink" id="submitQuickEditRisk">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="quickEditWeightModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);border-bottom:1px solid #c4b5fd">
                <h5 class="modal-title" style="font-weight:800;color:#7c3aed;font-size:.92rem">Ajouter une visite rapide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickEditWeightForm">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.78rem;font-weight:700">Date</label>
                        <input type="date" name="visit_date" class="form-control" value="<?php echo e(date('Y-m-d')); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.78rem;font-weight:700">Poids (kg)</label>
                        <input type="number" name="weight_kg" step="0.1" min="30" max="250" class="form-control" placeholder="kg">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-size:.78rem;font-weight:700">TA systolique</label>
                            <input type="number" name="blood_pressure_systolic" min="60" max="250" class="form-control" placeholder="mmHg">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:.78rem;font-weight:700">TA diastolique</label>
                            <input type="number" name="blood_pressure_diastolic" min="30" max="160" class="form-control" placeholder="mmHg">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.78rem;font-weight:700">Hauteur utérine (cm)</label>
                        <input type="number" name="fundal_height_cm" step="0.1" min="5" max="55" class="form-control" placeholder="cm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.78rem;font-weight:700">BCF (bpm)</label>
                        <input type="number" name="fetal_heart_rate" min="60" max="220" class="form-control" placeholder="bpm">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none" id="submitQuickEditWeight">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.gyneco-card{padding:14px;background:linear-gradient(180deg,#fdf2f8 0%,#fce7f3 100%);border:1px solid #f9a8d4}
.gyneco-toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.btn-outline-pink{color:#db2777;border-color:#f9a8d4}
.btn-outline-pink:hover{background:#fce7f3;border-color:#db2777;color:#be185d}
.gyneco-alerts{display:grid;gap:8px;margin-bottom:14px}
.gyneco-alert{padding:10px 14px;border-radius:10px;font-size:.88rem}
.gyneco-alert-warning{background:#fffbeb;border:1px solid #fcd34d;color:#92400e}
.gyneco-alert-danger{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b}
.gyneco-alert-info{background:#eff6ff;border:1px solid #93c5fd;color:#1e40af}
.obstetric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}
.obstetric-card{padding:16px;border-radius:14px;border:1px solid #e2e8f0;background:#fff;display:grid;gap:6px;position:relative;overflow:hidden}
.ob-card-edit{position:absolute;top:8px;inset-inline-end:8px;width:28px;height:28px;border:1px solid #e2e8f0;border-radius:8px;background:rgba(255,255,255,.85);cursor:pointer;display:flex;align-items:center;justify-content:center;color:#94a3b8;opacity:0;transition:all .2s ease;z-index:2}
.obstetric-card:hover .ob-card-edit{opacity:1}
.ob-card-edit:hover{border-color:#f9a8d4;background:#fce7f3;color:#db2777}
.qe-risk-options{display:grid;gap:8px}
.qe-risk-option{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;border:2px solid #e2e8f0;cursor:pointer;font-size:.88rem;font-weight:700;transition:all .15s ease}
.qe-risk-option:hover{border-color:#f9a8d4;background:#fdf2f8}
.qe-risk-option.active{border-width:2px}
.qe-risk-dot{width:12px;height:12px;border-radius:50%;flex-shrink:0}
.obstetric-card-icon{color:#64748b;margin-bottom:4px}
.obstetric-card-primary{border-color:#f9a8d4;background:linear-gradient(135deg,#fdf2f8,#fff)}
.obstetric-card-primary .obstetric-card-icon{color:#db2777}
.obstetric-card-calendar{border-color:#93c5fd;background:linear-gradient(135deg,#eff6ff,#fff)}
.obstetric-card-calendar .obstetric-card-icon{color:#2563eb}
.obstetric-card-risk-low{border-color:#86efac;background:linear-gradient(135deg,#f0fdf4,#fff)}
.obstetric-card-risk-low .obstetric-card-icon{color:#16a34a}
.obstetric-card-risk-moderate{border-color:#fcd34d;background:linear-gradient(135deg,#fffbeb,#fff)}
.obstetric-card-risk-moderate .obstetric-card-icon{color:#d97706}
.obstetric-card-risk-high{border-color:#fca5a5;background:linear-gradient(135deg,#fef2f2,#fff)}
.obstetric-card-risk-high .obstetric-card-icon{color:#dc2626}
.obstetric-card-vitals{border-color:#c4b5fd;background:linear-gradient(135deg,#f5f3ff,#fff)}
.obstetric-card-vitals .obstetric-card-icon{color:#7c3aed}
.obstetric-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#64748b;font-weight:700}
.obstetric-value{font-size:1.35rem;font-weight:800;color:#0f172a;line-height:1.2}
.obstetric-sub{font-size:.82rem;color:#64748b}
.obstetric-progress{height:6px;background:#e2e8f0;border-radius:999px;margin-top:8px;overflow:hidden}
.obstetric-progress-bar{height:100%;background:linear-gradient(90deg,#ec4899,#db2777);border-radius:999px;transition:width .4s ease}
.obstetric-sections{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.obstetric-section{padding:14px;border:1px solid #e2e8f0;border-radius:14px;background:#fff}
.obstetric-section h4{display:flex;align-items:center;gap:8px;font-size:.92rem;font-weight:800;color:#0f172a;margin:0 0 10px}
.milestone-list{display:grid;gap:8px}
.milestone-item{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0}
.milestone-week{font-weight:800;font-size:.82rem;color:#db2777;background:#fce7f3;padding:3px 8px;border-radius:999px;white-space:nowrap}
.milestone-label{font-size:.85rem;color:#334155;font-weight:600}
.exam-checklist{display:grid;gap:6px}
.exam-check-item{display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:8px;font-size:.84rem}
.exam-check-item.is-done{background:#f0fdf4;color:#166534}
.exam-check-item.is-done .exam-check-icon{color:#16a34a}
.exam-check-item.is-pending{background:#fffbeb;color:#92400e}
.exam-check-item.is-pending .exam-check-icon{color:#d97706}
.exam-check-label{font-weight:600}
.serology-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
.serology-item{display:flex;justify-content:space-between;padding:6px 8px;border-radius:8px;font-size:.82rem}
.serology-item.is-done{background:#f0fdf4}
.serology-item.is-pending{background:#fffbeb}
.serology-name{font-weight:600;color:#334155}
.serology-result{color:#64748b}
.bp-trend-list{display:grid;gap:6px}
.bp-trend-item{display:flex;align-items:center;gap:10px;padding:6px 8px;border-radius:8px;background:#f8fafc;font-size:.84rem}
.bp-trend-item.bp-alert{background:#fef2f2;border:1px solid #fca5a5}
.bp-date{color:#64748b;min-width:80px}
.bp-value{font-weight:700;color:#0f172a}
.bp-warning{font-size:.72rem;font-weight:800;color:#dc2626;background:#fee2e2;padding:2px 6px;border-radius:999px}
.ultrasound-list{display:grid;gap:8px}
.ultrasound-item{padding:10px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0}
.ultrasound-date{font-size:.78rem;font-weight:700;color:#1e40af;margin-bottom:4px}
.ultrasound-biometry{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:4px}
.biom-tag{font-size:.72rem;font-weight:600;padding:2px 8px;border-radius:999px;background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe}
.biom-tag-epf{background:#f0fdf4;color:#166534;border-color:#86efac;font-weight:800}
.ultrasound-conclusion{font-size:.78rem;color:#64748b;font-style:italic;margin-top:4px}
.gyneco-empty-state{text-align:center;padding:48px 24px;color:#64748b}
.gyneco-empty-state h4{color:#0f172a;margin:12px 0 6px}
.gyneco-empty-state p{font-size:.9rem;max-width:400px;margin:0 auto}
@media (max-width:1200px){.obstetric-grid{grid-template-columns:repeat(2,1fr)}.obstetric-sections{grid-template-columns:1fr}}
@media (max-width:768px){.obstetric-grid{grid-template-columns:1fr}}
</style>
<?php /**PATH D:\xampp8.2\htdocs\fils_attente\Modules\Gynecology\Providers/../Resources/views/partials/obstetric-dashboard.blade.php ENDPATH**/ ?>