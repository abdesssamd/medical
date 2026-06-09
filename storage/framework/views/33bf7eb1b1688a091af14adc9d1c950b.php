

<?php
    $selectedPatientId = (int) ($selectedPatientId ?? 0);
    $currentTab = in_array(($currentTab ?? 'overview'), ['overview', 'clinical', 'care', 'documents'], true) ? $currentTab : 'overview';
    $allergies = collect($allergies ?? []);
    $riskTags = collect($riskTags ?? []);
    $patientAllergies = collect($patientAllergies ?? $allergies);
    $patientMedicalHistory = collect($patientMedicalHistory ?? []);
    $patientCriticalConditions = collect($patientCriticalConditions ?? $riskTags);
    $criticalBannerItems = collect($criticalBannerItems ?? []);
    $prescriptionTemplates = collect($prescriptionTemplates ?? []);
    $prescriptions = collect($prescriptions ?? []);
    $patientAlerts = collect($patientAlerts ?? ($selectedPatient?->critical_health_alerts ?? []));
    $consultations = collect($consultations ?? []);
    $questionnaireResponses = collect($questionnaireResponses ?? []);
    $consultationTypes = $consultationTypes ?? [];
    $diagnosisCatalog = $diagnosisCatalog ?? [];
    $appointmentsForPatient = collect($appointmentsForPatient ?? []);
    $practitioners = collect($practitioners ?? []);
    $radiologyRequests = collect($radiologyRequests ?? []);
    $treatmentPlans = collect($treatmentPlans ?? []);
    $estimatedBalance = (float) ($estimatedBalance ?? $treatmentPlans->sum(fn ($plan) => (float) ($plan->remaining_amount ?? 0)));
    $treatmentQuotes = collect($treatmentQuotes ?? []);
    $periodontalHistorySeed = $periodontalHistorySeed ?? [];
    $periodontalSeed = $periodontalSeed ?? [];
    $questionnaireTemplatesPayload = $questionnaireTemplatesPayload ?? [];
    $odontogramTeethStatus = $odontogramTeethStatus ?? [];
    $teethSummary = $teethSummary ?? [];
    $bmi = $bmi ?? null;
    $nextAppointment = $nextAppointment ?? null;
    $hasMajorRisk = $allergies->isNotEmpty() || $riskTags->isNotEmpty();
    $rxCatalogPayload = collect($medications ?? collect())->map(fn ($m) => [
        'id' => $m->id,
        'name' => $m->name,
        'category' => $m->category,
        'strength' => $m->strength,
        'default_unit' => $m->default_unit,
        'default_frequency' => $m->default_frequency,
        'default_duration_days' => $m->default_duration_days,
        'allergen_keywords' => $m->allergen_keywords ?? [],
        'contraindication_tags' => $m->contraindication_tags ?? [],
    ])->values();
    $rxTemplatePayload = $prescriptionTemplates->map(fn ($tpl) => [
        'id' => $tpl->id,
        'name' => $tpl->name,
        'notes' => $tpl->notes ?? null,
        'items' => collect($tpl->items ?? [])->map(fn ($item) => [
            'medication_id' => $item->medication_id,
            'medication_name' => $item->medication_name,
            'dosage' => $item->dosage,
            'unit' => $item->unit,
            'frequency' => $item->frequency,
            'duration_days' => $item->duration_days,
            'instructions' => $item->instructions,
        ])->values(),
    ])->values();
    $rxPatientPreview = $selectedPatient ? [
        'full_name' => $selectedPatient->full_name,
        'first_name' => $selectedPatient->first_name,
        'last_name' => $selectedPatient->last_name,
        'age' => $selectedPatient->age,
        'mrn' => $selectedPatient->medical_record_number,
    ] : null;
?>

<?php $__env->startSection('content'); ?>
<section id="patient-directory" class="card patient-directory-card patient-nav-footer">
    <div class="directory-head">
        <div>
            <h3>Navigation Rapide - Changer de Patient</h3>
            <div class="muted small">Recherche instantanee par nom, prenom, telephone ou MRN</div>
        </div>
        <button type="button" class="btn btn-primary" id="openPatientSlideover">Nouveau patient</button>
    </div>

    <div class="omnibar-wrap">
        <input type="search" id="patientOmniSearch" class="omnibar-input" placeholder="Rechercher (Nom, Prenom, Tel, MRN)..." autocomplete="off">
    </div>

    <div id="patientSkeletonList" class="patient-skeleton-list">
        <?php for($i = 0; $i < 6; $i++): ?>
            <div class="patient-skeleton-row"></div>
        <?php endfor; ?>
    </div>

    <div id="patientRows" class="patient-rows d-none"></div>

    <script type="application/json" id="patientDirectoryJson"><?php echo json_encode($patientDirectory ?? [], 15, 512) ?></script>
    <div class="d-none" id="patientDirectoryMeta"
        data-selected-patient-id="<?php echo e((int) ($selectedPatientId ?? 0)); ?>"
        data-patient-view-base="<?php echo e(route('care.module3.index')); ?>"
        data-clinical-file-base="<?php echo e(url('/care/module-3/patients')); ?>"
        data-module2-base="<?php echo e(route('care.module2.index')); ?>"
        data-new-patient="<?php echo e(request()->boolean('new_patient') ? '1' : '0'); ?>"></div>
</section>

<div class="clinical-shell">
    <aside class="clinical-sidebar card clinical-context-card">
        <div class="sidebar-head">
            <div class="sidebar-kicker">Dossier patient</div>
            <?php if($selectedPatient): ?>
                <h3><?php echo e($selectedPatient->full_name); ?></h3>
                <p>MRN <?php echo e($selectedPatient->medical_record_number); ?> • <?php echo e($selectedPatient->age); ?> ans</p>
            <?php else: ?>
                <h3>Patient non selectionne</h3>
                <p>Choisir un patient pour afficher le contexte clinique.</p>
            <?php endif; ?>
        </div>

        <?php if($selectedPatient): ?>
            <div class="sidebar-context-metrics">
                <div class="context-metric">
                    <span>Alertes</span>
                    <strong><?php echo e($patientAlerts->count()); ?></strong>
                </div>
                <div class="context-metric">
                    <span>Solde estime</span>
                    <strong><?php echo e(number_format($estimatedBalance, 2, ',', ' ')); ?> MAD</strong>
                </div>
            </div>
            <div class="sidebar-alert-list">
                <?php $__empty_1 = true; $__currentLoopData = $patientAlerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <span class="alert-pill alert-pill-danger"><?php echo e($alert); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <span class="alert-pill alert-pill-success">Aucune alerte critique</span>
                <?php endif; ?>
            </div>
            <div class="sidebar-actions">
                <a class="btn btn-primary w-100" href="<?php echo e(route('care.module3.export', ['patientId' => $selectedPatientId])); ?>" target="_blank">Exporter PDF dossier</a>
                <button type="button" class="btn btn-outline-primary w-100 mt-2" onclick="openQuestionnaireSheet()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                    Questionnaire & Constantes
                </button>
                <a class="btn btn-outline-secondary w-100 mt-2" href="<?php echo e(route('care.module2.index')); ?>">Flux patient live</a>
            </div>
        <?php endif; ?>

        <nav class="clinical-tab-rail" aria-label="Navigation clinique">
            <button type="button" class="clinical-tab-link <?php echo e($currentTab === 'overview' ? 'active' : ''); ?>" data-care-tab-target="overview">Vue generale</button>
            <button type="button" class="clinical-tab-link <?php echo e($currentTab === 'clinical' ? 'active' : ''); ?>" data-care-tab-target="clinical">Examen clinique</button>
            <button type="button" class="clinical-tab-link <?php echo e($currentTab === 'care' ? 'active' : ''); ?>" data-care-tab-target="care">Saisie de soins</button>
            <button type="button" class="clinical-tab-link <?php echo e($currentTab === 'documents' ? 'active' : ''); ?>" data-care-tab-target="documents">Documents</button>
            <?php if(in_array($currentSpecialtyCode, ['REHAB', 'OMNI'])): ?>
            <button type="button" class="clinical-tab-link <?php echo e($currentTab === 'rehab' ? 'active' : ''); ?>" data-care-tab-target="rehab">Rééducation</button>
            <?php endif; ?>
            <?php if(in_array($currentSpecialtyCode, ['INTMED', 'OMNI'])): ?>
            <button type="button" class="clinical-tab-link <?php echo e($currentTab === 'internal-medicine' ? 'active' : ''); ?>" data-care-tab-target="internal-medicine">Médecine Interne</button>
            <?php endif; ?>
        </nav>
    </aside>

    <div class="clinical-main">
        <section class="card clinical-sticky-header">
            <div class="sticky-header-top">
                <div>
                    <div class="sidebar-kicker">Header contextuel</div>
                    <?php if($selectedPatient): ?>
                        <h2 class="sticky-patient-name"><?php echo e($selectedPatient->full_name); ?></h2>
                        <div class="sticky-patient-meta"><?php echo e($selectedPatient->age); ?> ans • <?php echo e($selectedPatient->medical_record_number); ?> • <?php echo e($selectedPatient->phone ?: 'Telephone non renseigne'); ?></div>
                    <?php else: ?>
                        <h2 class="sticky-patient-name">Dossier clinique</h2>
                        <div class="sticky-patient-meta">Aucun patient selectionne</div>
                    <?php endif; ?>
                </div>
                <div class="sticky-header-kpis">
                    <div class="sticky-kpi">
                        <span>Alertes</span>
                        <strong><?php echo e($patientAlerts->count()); ?></strong>
                    </div>
                    <div class="sticky-kpi sticky-kpi-saldo">
                        <span>Solde estime</span>
                        <strong><?php echo e(number_format($estimatedBalance, 2, ',', ' ')); ?> MAD</strong>
                    </div>
                </div>
            </div>
            <div class="sticky-alert-row">
                <?php $__empty_1 = true; $__currentLoopData = $patientAlerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <span class="alert-pill alert-pill-danger"><?php echo e($alert); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <span class="alert-pill alert-pill-success">Allergies et pathologies non signalees</span>
                <?php endif; ?>
            </div>
            <div class="clinical-quick-actions" aria-label="Actions rapides du dossier patient">
                <div class="clinical-quick-actions-desktop d-none d-md-flex" role="toolbar" aria-label="Actions rapides">
                    <button type="button" class="btn btn-outline-primary btn-sm clinical-quick-action" data-care-action="consultation" data-care-tab-target="care" data-care-scroll-target="consultation-entry">
                        <i class="ti ti-stethoscope"></i>
                        <span>+ Consultation</span>
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm clinical-quick-action" data-care-action="prescription" data-care-tab-target="documents" data-care-scroll-target="prescriptions">
                        <i class="ti ti-prescription"></i>
                        <span>+ Ordonnance</span>
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm clinical-quick-action" data-care-action="quote" data-care-tab-target="documents" data-care-scroll-target="advanced-quote">
                        <i class="ti ti-file-invoice"></i>
                        <span>+ Devis</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm clinical-quick-action" data-care-action="imaging" data-care-tab-target="documents" data-care-scroll-target="imaging">
                        <i class="ti ti-camera"></i>
                        <span>+ Radio/Photo</span>
                    </button>
                    <?php
                        $risEnabled = (bool) config('ris.enabled', false);
                        if (! $risEnabled && class_exists('Modules\\Queue\\Models\\AppSetting')) {
                            $risEnabled = filter_var((string) \Modules\Queue\Models\AppSetting::getValue('module.ris.enabled', false), FILTER_VALIDATE_BOOL) === true;
                        }
                    ?>
                    <?php if($risEnabled): ?>
                        <a href="<?php echo e(route('ris.exams.index')); ?>" class="btn btn-outline-dark btn-sm">
                            <i class="ti ti-activity-heartbeat"></i>
                            <span>RIS Examens</span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="clinical-quick-actions-mobile d-flex d-md-none">
                    <label class="visually-hidden" for="clinicalQuickActionsSelect">Actions rapides</label>
                    <select id="clinicalQuickActionsSelect" class="form-select form-select-sm">
                        <option value="">Actions rapides</option>
                        <option value="consultation">+ Consultation</option>
                        <option value="prescription">+ Ordonnance</option>
                        <option value="quote">+ Devis</option>
                        <option value="imaging">+ Radio/Photo</option>
                    </select>
                </div>
            </div>
        </section>

        <!--div class="clinical-tabbar card">
            <button type="button" class="clinical-tab-button <?php echo e($currentTab === 'overview' ? 'active' : ''); ?>" data-care-tab-target="overview">Vue gÃ©nÃ©rale</button>
            <button type="button" class="clinical-tab-button <?php echo e($currentTab === 'clinical' ? 'active' : ''); ?>" data-care-tab-target="clinical">Examen clinique</button>
            <button type="button" class="clinical-tab-button <?php echo e($currentTab === 'care' ? 'active' : ''); ?>" data-care-tab-target="care">Saisie de soins</button>
            <button type="button" class="clinical-tab-button <?php echo e($currentTab === 'documents' ? 'active' : ''); ?>" data-care-tab-target="documents">Documents</button>
        </div-->

        <section id="overview" class="card overview-card" data-care-tab-panel="overview">

            <?php if($selectedPatient): ?>
                <div id="module3-patient-context"
                     data-patient-id="<?php echo e($selectedPatient->id); ?>"
                     data-patient-name="<?php echo e($selectedPatient->full_name); ?>"
                     data-patient-mrn="<?php echo e($selectedPatient->medical_record_number); ?>"
                     data-patient-age="<?php echo e($selectedPatient->age); ?>"
                     data-patient-phone="<?php echo e($selectedPatient->phone ?: '-'); ?>"
                     class="d-none"
                     aria-hidden="true"></div>
                <div class="patient-header-tech">
                    <div class="patient-id">
                        <?php if($selectedPatient->patient_photo_path): ?>
                            <img src="<?php echo e(asset($selectedPatient->patient_photo_path)); ?>" class="avatar" alt="Photo patient">
                        <?php else: ?>
                            <div class="avatar"><?php echo e(strtoupper(substr($selectedPatient->first_name, 0, 1) . substr($selectedPatient->last_name, 0, 1))); ?></div>
                        <?php endif; ?>
                        <div>
                            <h2><?php echo e($selectedPatient->full_name); ?></h2>
                            <div class="muted">MRN: <?php echo e($selectedPatient->medical_record_number); ?> | Age: <?php echo e($selectedPatient->age); ?> ans | Sexe: <?php echo e(strtoupper((string) ($selectedPatient->gender ?: '-'))); ?> | Groupe: <?php echo e($selectedPatient->blood_group ?: '-'); ?> | Tel: <?php echo e($selectedPatient->phone ?: '-'); ?></div>
                            <div class="muted">Taille: <?php echo e($selectedPatient->height_cm ? number_format((float) $selectedPatient->height_cm, 1, ',', ' ') . ' cm' : '-'); ?> | Poids: <?php echo e($selectedPatient->weight_kg ? number_format((float) $selectedPatient->weight_kg, 1, ',', ' ') . ' kg' : '-'); ?> | IMC: <?php echo e($bmi !== null ? number_format((float) $bmi, 1, ',', ' ') : '-'); ?></div>
                        </div>
                    </div>
                    <div class="patient-widget">
                        <div class="widget-title">Prochain rendez-vous</div>
                        <?php if($nextAppointment): ?>
                            <div class="widget-value"><?php echo e(\Carbon\Carbon::parse($nextAppointment->appointment_date)->format('d/m/Y')); ?> <?php echo e(\Illuminate\Support\Str::of($nextAppointment->start_time)->substr(0,5)); ?></div>
                            <div class="widget-sub">Praticien ID: <?php echo e($nextAppointment->professional_id); ?></div>
                        <?php else: ?>
                            <div class="widget-value">Aucun RDV planifie</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="overview-priority-layout">
                    <aside class="overview-priority-side">
                        <div class="patient-history-mini card mt-3">
                            <div class="d-flex justify-content-between gap-2 align-items-center mb-2">
                                <div>
                                    <h3 class="h6 mb-0">AntÃ©cÃ©dents & risques</h3>
                                    <div class="muted small">Visualisation rapide du terrain clinique avant la saisie SOAP.</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-open-history-modal>
                                    <i class="ti ti-plus"></i> Ajouter
                                </button>
                            </div>
                            <div class="patient-history-groups">
                                <div class="patient-history-group">
                                    <div class="patient-history-label text-danger">Allergies critiques</div>
                                    <div class="patient-history-badges" id="historyAllergiesBadges">
                                        <?php $__empty_1 = true; $__currentLoopData = $patientAllergies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <span class="history-badge history-badge-danger"><?php echo e($item); ?></span>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <span class="history-empty">Aucune allergie</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="patient-history-group">
                                    <div class="patient-history-label text-warning">AntÃ©cÃ©dents</div>
                                    <div class="patient-history-badges" id="historyMedicalBadges">
                                        <?php $__empty_1 = true; $__currentLoopData = $patientMedicalHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <span class="history-badge history-badge-warning"><?php echo e($item); ?></span>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <span class="history-empty">Aucun antÃ©cÃ©dent renseignÃ©</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="patient-history-group">
                                    <div class="patient-history-label text-warning">Facteurs de risque</div>
                                    <div class="patient-history-badges" id="historyRiskBadges">
                                        <?php $__empty_1 = true; $__currentLoopData = $patientCriticalConditions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <span class="history-badge history-badge-warning"><?php echo e($item); ?></span>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <span class="history-empty">Aucun facteur de risque</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                    <div class="overview-priority-main">
                        <div class="allergy-bar <?php echo e($criticalBannerItems->isNotEmpty() ? 'has-alert' : ''); ?>">
                            <strong>Sante critique:</strong>
                            <?php if($criticalBannerItems->isNotEmpty()): ?>
                                <span><?php echo e($criticalBannerItems->implode(' | ')); ?></span>
                            <?php else: ?>
                                <span>Aucune allergie ou pathologie a risque signalee</span>
                            <?php endif; ?>
                        </div>

                        <?php if(!empty($selectedPatientNeedsPreventiveRecall)): ?>
                            <div class="preventive-recall-alert">
                                <strong>Patient Ã  rappeler pour dÃ©tartrage annuel.</strong>
                                <span>
                                    DerniÃ¨re visite: <?php echo e($selectedPatientLastVisitAt ?: 'Aucune visite enregistrÃ©e'); ?>

                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <?php if($selectedPatient): ?>
            <section class="card">
                <div class="section-head">
                    <h3>DPI Core - Identitovigilance et antecedents</h3>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 h-100">
                            <div class="fw-semibold mb-2">Identite patient</div>
                            <div class="small">MRN: <strong><?php echo e($selectedPatient->medical_record_number); ?></strong></div>
                            <div class="small">Nom: <?php echo e($selectedPatient->full_name); ?></div>
                            <div class="small">Date naissance: <?php echo e(optional($selectedPatient->date_of_birth)->format('d/m/Y')); ?></div>
                            <div class="small">Sexe: <?php echo e(strtoupper((string) ($selectedPatient->gender ?: '-'))); ?></div>
                            <div class="small">Groupe sanguin: <?php echo e($selectedPatient->blood_group ?: '-'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 h-100 inline-editable-card" data-history-type="personal">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <div class="fw-semibold">Antecedents personnels</div>
                                <button type="button" class="btn btn-sm btn-outline-primary inline-add-btn" data-history-type="personal" title="Ajouter un antÃ©cÃ©dent">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                            <?php if(collect($selectedPatient->personal_history ?? [])->isNotEmpty()): ?>
                                <div class="history-items-list" id="personal-history-items">
                                    <?php $__currentLoopData = ($selectedPatient->personal_history ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="history-item history-item-tag" data-item="<?php echo e($item); ?>" data-history-type="personal">
                                            <span><?php echo e($item); ?></span>
                                            <button type="button" class="btn-close-inline" data-history-type="personal" data-item="<?php echo e($item); ?>" title="Supprimer">âœ•</button>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php else: ?>
                                <div class="small text-secondary inline-placeholder" id="personal-history-placeholder">Non renseignÃ© â€¢ <a href="#" class="inline-edit-trigger" data-history-type="personal">Ajouter</a></div>
                            <?php endif; ?>
                            <div class="inline-edit-form d-none mt-2" id="personal-history-form" data-history-type="personal">
                                <input type="text" class="form-control form-control-sm inline-input" placeholder="Ex: DiabÃ¨te, HTA, Tabagisme..." autocomplete="off" data-suggestions="personal">
                                <div class="suggestions-list d-none"></div>
                                <small class="text-muted d-block mt-1">Appuyez sur EntrÃ©e pour ajouter</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 h-100 inline-editable-card" data-history-type="family">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <div class="fw-semibold">Antecedents familiaux</div>
                                <button type="button" class="btn btn-sm btn-outline-primary inline-add-btn" data-history-type="family" title="Ajouter un antÃ©cÃ©dent">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                            <?php if(collect($selectedPatient->family_history ?? [])->isNotEmpty()): ?>
                                <div class="history-items-list" id="family-history-items">
                                    <?php $__currentLoopData = ($selectedPatient->family_history ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="history-item history-item-tag" data-item="<?php echo e($item); ?>" data-history-type="family">
                                            <span><?php echo e($item); ?></span>
                                            <button type="button" class="btn-close-inline" data-history-type="family" data-item="<?php echo e($item); ?>" title="Supprimer">âœ•</button>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php else: ?>
                                <div class="small text-secondary inline-placeholder" id="family-history-placeholder">Non renseignÃ© â€¢ <a href="#" class="inline-edit-trigger" data-history-type="family">Ajouter</a></div>
                            <?php endif; ?>
                            <div class="inline-edit-form d-none mt-2" id="family-history-form" data-history-type="family">
                                <input type="text" class="form-control form-control-sm inline-input" placeholder="Ex: DiabÃ¨te familial, Cardiopathie..." autocomplete="off" data-suggestions="family">
                                <div class="suggestions-list d-none"></div>
                                <small class="text-muted d-block mt-1">Appuyez sur EntrÃ©e pour ajouter</small>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if($selectedPatient): ?>
        <section id="archives-antecedents" class="card archives-card">
            <div class="section-head d-flex justify-content-between align-items-center">
                <h3>Archives & AntÃ©rioritÃ©s</h3>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="archivesToggleBtn" data-bs-toggle="collapse" data-bs-target="#archivesContent" aria-expanded="false">
                    <i class="ti ti-chevron-down"></i>
                </button>
            </div>
            <div class="collapse" id="archivesContent">
                <div class="archives-body mt-3">
                    <div class="archives-section mb-4">
                        <h4 class="h6 mb-3">Historique Complet des Consultations</h4>
                        <div class="accordion" id="archiveHistoryAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#archiveVisitsBody">
                                        Voir toutes les consultations
                                    </button>
                                </h2>
                                <div id="archiveVisitsBody" class="accordion-collapse collapse show" data-bs-parent="#archiveHistoryAccordion">
                                    <div class="accordion-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead><tr><th>Date</th><th>Motif</th><th>Praticien</th><th>Actions</th></tr></thead>
                                                <tbody>
                                                <?php $__empty_1 = true; $__currentLoopData = $consultations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $consultation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                    <tr>
                                                        <td><?php echo e(optional($consultation->consultation_date)->format('d/m/Y')); ?></td>
                                                        <td><?php echo e($consultation->consultation_reason); ?></td>
                                                        <td><?php echo e($consultation->practitioner?->name ?: '-'); ?></td>
                                                        <td><button type="button" class="btn btn-sm btn-outline-primary">Voir</button></td>
                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                                    <tr><td colspan="4" class="text-secondary">Aucune consultation archivÃ©e.</td></tr>
                                                <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="archives-section">
                        <h4 class="h6 mb-3">Historique des Questionnaires</h4>
                        <div class="accordion" id="archiveQuestionnaireAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#archiveQuestBody">
                                        Voir tous les questionnaires
                                    </button>
                                </h2>
                                <div id="archiveQuestBody" class="accordion-collapse collapse" data-bs-parent="#archiveQuestionnaireAccordion">
                                    <div class="accordion-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead><tr><th>Date</th><th>Nom</th><th>SpÃ©cialitÃ©</th><th>Actions</th></tr></thead>
                                                <tbody>
                                                <?php $__empty_1 = true; $__currentLoopData = $questionnaireResponses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $response): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                    <tr>
                                                        <td><?php echo e(optional($response->answered_at)->format('d/m/Y')); ?></td>
                                                        <td><?php echo e($response->questionnaire?->name ?? 'Questionnaire'); ?></td>
                                                        <td><span class="badge bg-azure-lt"><?php echo e($response->questionnaire?->specialty?->name ?? '-'); ?></span></td>
                                                        <td><button type="button" class="btn btn-sm btn-outline-primary">Voir</button></td>
                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                                    <tr><td colspan="4" class="text-secondary">Aucun questionnaire archivÃ©.</td></tr>
                                                <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <div id="patientSlideover" class="patient-slideover" aria-hidden="true">
            <div class="patient-slideover-backdrop" id="patientSlideoverClose"></div>
            <aside class="patient-slideover-panel" role="dialog" aria-modal="true" aria-labelledby="newPatientTitle">
                <div class="slideover-header">
                    <div>
                        <h3 id="newPatientTitle">Nouveau Patient</h3>
                        <div class="muted small">Inscription rapide en 3 Ã©tapes</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="patientSlideoverCloseBtn">Fermer</button>
                </div>

                <div class="stepper" id="patientStepper">
                    <span class="step-chip active" data-step="1">1. IdentitÃ©</span>
                    <span class="step-chip" data-step="2">2. Contact & Mutuelle</span>
                    <span class="step-chip" data-step="3">3. AntÃ©cÃ©dents</span>
                </div>

                <form method="POST" action="<?php echo e(route('care.module3.patients.store')); ?>" id="newPatientForm" class="slideover-form" enctype="multipart/form-data" data-store-action="<?php echo e(route('care.module3.patients.store')); ?>" data-update-base="<?php echo e(url('/care/module-3/patients')); ?>" data-no-spa="true" novalidate>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" id="patientFormMethod" name="_method" value="PUT" disabled>
                    <input type="hidden" id="editingPatientId" value="">
                    <section class="form-step active" data-step="1">
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label">PrÃ©nom</label><input class="form-control" name="first_name" required></div>
                            <div class="col-md-6"><label class="form-label">Nom</label><input class="form-control" name="last_name" required></div>
                            <div class="col-md-6"><label class="form-label">CIN</label><input class="form-control" name="cin"></div>
                            <div class="col-md-6"><label class="form-label">Genre</label><select class="form-select" name="gender"><option value="">-</option><option value="male">Homme</option><option value="female">Femme</option></select></div>
                            <div class="col-md-6"><label class="form-label">Groupe sanguin</label><select class="form-select" name="blood_group"><option value="">-</option><option value="A+">A+</option><option value="A-">A-</option><option value="B+">B+</option><option value="B-">B-</option><option value="AB+">AB+</option><option value="AB-">AB-</option><option value="O+">O+</option><option value="O-">O-</option></select></div>
                            <div class="col-md-3"><label class="form-label">Taille (cm)</label><input class="form-control" type="number" step="0.1" min="30" max="300" name="height_cm"></div>
                            <div class="col-md-3"><label class="form-label">Poids (kg)</label><input class="form-control" type="number" step="0.1" min="1" max="500" name="weight_kg"></div>
                            <div class="col-md-6"><label class="form-label">Date de naissance</label><input class="form-control" type="date" id="npBirthDate" name="date_of_birth" required></div>
                            <div class="col-md-6"><label class="form-label">Ã‚ge</label><input class="form-control" id="npAge" placeholder="Calcul automatique" readonly></div>
                        </div>

                        <div class="photo-capture-box mt-2" id="patientPhotoDrop">
                            <input type="file" id="patientPhotoInput" name="patient_photo" class="d-none" accept="image/*" capture="environment">
                            <div class="photo-placeholder" id="patientPhotoPreview">Photo patient (Drag & Drop ou Webcam)</div>
                            <div class="photo-actions">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectPhoto">Choisir photo</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnStartWebcam">Webcam</button>
                                <button type="button" class="btn btn-sm btn-outline-success d-none" id="btnCaptureWebcam">Capturer</button>
                            </div>
                            <video id="patientWebcam" class="d-none" autoplay playsinline></video>
                        </div>
                    </section>

                    <section class="form-step" data-step="2">
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label">TÃ©lÃ©phone</label><input class="form-control" id="npPhone" name="phone"></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email"></div>
                            <div class="col-md-12">
                                <label class="form-label">Adresse</label>
                                <input class="form-control" name="address" id="npAddress" list="addressSuggestionsList" placeholder="Commencez Ã  saisir votre adresse...">
                                <datalist id="addressSuggestionsList">
                                    <option value="Casablanca"></option>
                                    <option value="Rabat"></option>
                                    <option value="Marrakech"></option>
                                    <option value="Tanger"></option>
                                    <option value="Agadir"></option>
                                </datalist>
                            </div>
                            <div class="col-md-6"><label class="form-label">Nom contact urgence</label><input class="form-control" name="emergency_contact_name"></div>
                            <div class="col-md-6"><label class="form-label">TÃ©l contact urgence</label><input class="form-control" name="emergency_contact_phone"></div>
                            <div class="col-md-12"><label class="form-label">Mutuelle (facultatif)</label><input class="form-control" id="npInsurance" placeholder="Nom mutuelle / numÃ©ro adhÃ©rent"></div>
                        </div>
                        <div id="patientDuplicateAlert" class="soft-alert d-none mt-2">
                            <div>Un patient avec ce numÃ©ro existe dÃ©jÃ .</div>
                            <a href="#" id="openExistingPatient" class="btn btn-sm btn-outline-primary mt-1">Ouvrir son dossier</a>
                        </div>
                    </section>

                    <section class="form-step" data-step="3">
                        <div class="row g-2">
                            <div class="col-md-12"><label class="form-label">Allergies (sÃ©parÃ©es par virgule)</label><textarea class="form-control" name="allergies" rows="2"></textarea></div>
                            <div class="col-md-12"><label class="form-label">AntÃ©cÃ©dents mÃ©dicaux</label><textarea class="form-control" name="medical_history" rows="3"></textarea></div>
                            <div class="col-md-12"><label class="form-label">Pathologies a risque / alertes critiques</label><textarea class="form-control" name="critical_conditions" rows="2" placeholder="Diabete, Anticoagulants, Immunodepression..."></textarea></div>
                            <div class="col-md-12"><label class="form-label">Antecedents personnels</label><textarea class="form-control" name="personal_history" rows="2" placeholder="HTA, chirurgie, tabagisme..."></textarea></div>
                            <div class="col-md-12"><label class="form-label">Antecedents familiaux</label><textarea class="form-control" name="family_history" rows="2" placeholder="Diabete familial, cardiopathie..."></textarea></div>
                            <div class="col-md-12"><label class="form-label">MÃ©dicaments en cours</label><textarea class="form-control" name="current_medications" rows="2"></textarea></div>
                            <div class="col-md-12"><label class="form-label">Motif de consultation</label><input class="form-control" name="consultation_reason" list="consultationMotifsList" placeholder="Douleur, dÃ©tartrage, urgence..."></div>
                            <div class="col-md-12">
                                <label class="form-label">Type initial</label>
                                <select class="form-select" name="consultation_type">
                                    <?php $__currentLoopData = $consultationTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($type['value']); ?>"><?php echo e($type['label']); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                    </section>

                    <div class="slideover-footer">
                        <button type="button" class="btn btn-outline-secondary" id="patientPrevStep">Precedent</button>
                        <button type="button" class="btn btn-primary" id="patientNextStep">Suivant</button>
                        <button type="submit" class="btn btn-success d-none" id="patientSubmitBtn">Enregistrer le patient</button>
                    </div>
                </form>
            </aside>
        </div>

        <?php if($selectedPatientId && $selectedPatient): ?>

            <?php if(in_array($currentSpecialtyCode, ['GYNECO', 'OMNI'])): ?>
                <?php echo $__env->make('gynecology::partials.obstetric-dashboard', ['dashboard' => $gynecologyDashboard, 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('gynecology::partials.prenatal-visits-table', ['dashboard' => $gynecologyDashboard, 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('gynecology::partials.fetal-biometry-chart', ['dashboard' => $gynecologyDashboard, 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('gynecology::partials.gynecological-history', ['history' => $gynecologyDashboard['gynecological_history'] ?? null, 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('gynecology::partials.pregnancy-form', ['pregnancy' => $gynecologyDashboard['active_pregnancy'] ?? null, 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('gynecology::partials.fetal-biometry-form', ['pregnancy' => $gynecologyDashboard['active_pregnancy'] ?? null, 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>

            <?php if(in_array($currentSpecialtyCode, ['PEDIA', 'OMNI'])): ?>
                <?php echo $__env->make('pediatrics::partials.neonatal-form', ['birthHistory' => $pediatricsDashboard['birth_history'] ?? null, 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('pediatrics::partials.growth-charts', ['growthRecords' => $pediatricsDashboard['growth_records'] ?? collect(), 'growthChartData' => $pediatricsDashboard['growth_chart_data'] ?? [], 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('pediatrics::partials.vaccination-grid', ['vaccinationSchedule' => $pediatricsVaccinationSchedule ?? [], 'vaccinationSummary' => $pediatricsVaccinationSummary ?? [], 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>

            <?php if(in_array($currentSpecialtyCode, ['BURNS', 'OMNI'])): ?>
                <?php echo $__env->make('burns::partials.body-mapping', ['admission' => $burnsDashboard['admission'] ?? null, 'latestAssessment' => $burnsDashboard['latest_assessment'] ?? null, 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('burns::partials.resuscitation-dashboard', ['admission' => $burnsDashboard['admission'] ?? null, 'fluidResuscitation' => $burnsDashboard['fluid_resuscitation'] ?? null, 'fluidStatus' => $burnsDashboard['fluid_status'] ?? [], 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('burns::partials.wound-gallery', ['admission' => $burnsDashboard['admission'] ?? null, 'woundEvolutions' => $burnsDashboard['wound_evolutions'] ?? collect(), 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>

            <?php if(in_array($currentSpecialtyCode, ['REHAB', 'OMNI'])): ?>
                <?php echo $__env->make('rehab::partials.doctor-dashboard', ['prescriptions' => $rehabDashboard['prescriptions'] ?? collect(), 'activePrescription' => $rehabDashboard['active_prescription'] ?? null, 'stats' => $rehabDashboard['stats'] ?? [], 'evaluations' => $rehabDashboard['evaluations'] ?? collect(), 'sessions' => $rehabDashboard['sessions'] ?? collect(), 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('rehab::partials.physio-session-tracker', ['activePrescription' => $rehabDashboard['active_prescription'] ?? null, 'stats' => $rehabDashboard['stats'] ?? [], 'sessions' => $rehabDashboard['sessions'] ?? collect(), 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>

            <?php if(in_array($currentSpecialtyCode, ['INTMED', 'OMNI'])): ?>
                <?php echo $__env->make('internal-medicine::partials.chronic-diseases', ['chronicConditions' => $internalMedicineDashboard['chronic_conditions'] ?? collect(), 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('internal-medicine::partials.biology-dashboard', ['labChartData' => $internalMedicineDashboard['lab_chart_data'] ?? [], 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('internal-medicine::partials.clinical-scores', ['scoresSummary' => $internalMedicineDashboard['scores_summary'] ?? [], 'clinicalScores' => $internalMedicineDashboard['clinical_scores'] ?? null, 'selectedPatientId' => $selectedPatientId], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>

            <?php if(in_array($currentSpecialtyCode, ['DENTAL', 'OMNI'])): ?>
            <section id="chart3d" class="card" data-care-tab-panel="clinical">
                <div class="section-head">
                    <h3 class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-blue-600"><path d="M12 2C8 2 4 6 4 10c0 3 2 6 4 8l4 4 4-4c2-2 4-5 4-8 0-4-4-8-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
                        Schéma Dentaire 3D Interactif
                    </h3>
                    <div class="toolbar-floating">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="reset3d">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                            Reset
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleRotate">Auto rotate</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleMulti">Multi-select</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-jaw="all">All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-jaw="maxillaire">Maxillaire</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-jaw="mandibule">Mandibule</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="annotateTooth">Annoter</button>
                    </div>
                </div>
                <div class="chart-grid-modern">
                    <div class="dental-canvas-wrapper">
                        <div id="dental3d" class="dental-canvas-modern"></div>
                        <div class="legend-filter-modern" id="legendFilter">
                            <button type="button" class="legend-chip-modern active" data-filter="all"><span class="legend-dot" style="background:#94a3b8"></span> Tout</button>
                            <button type="button" class="legend-chip-modern" data-filter="implant"><span class="legend-dot" style="background:#22c55e"></span> Implant</button>
                            <button type="button" class="legend-chip-modern" data-filter="extracted"><span class="legend-dot" style="background:#ef4444"></span> Extrait</button>
                            <button type="button" class="legend-chip-modern" data-filter="decay"><span class="legend-dot" style="background:#f59e0b"></span> Carie</button>
                            <button type="button" class="legend-chip-modern" data-filter="crown"><span class="legend-dot" style="background:#3b82f6"></span> Couronne</button>
                            <button type="button" class="legend-chip-modern" data-filter="root_canal"><span class="legend-dot" style="background:#8b5cf6"></span> Canal</button>
                        </div>
                    </div>
                    
                    <!-- Modern Side Panel -->
                    <div class="tooth-panel-modern" id="toothPanel">
                        <!-- Tooth Header -->
                        <div class="tooth-panel-header">
                            <div class="tooth-badge" id="toothBadge">
                                <span class="tooth-number" id="toothNumberDisplay">--</span>
                            </div>
                            <div class="tooth-info">
                                <h4 id="toothTitle" class="tooth-title">Sélectionnez une dent</h4>
                                <div id="toothState" class="tooth-status">Cliquez sur une dent pour commencer</div>
                            </div>
                            <button type="button" class="tooth-panel-close" id="closeToothPanel" title="Fermer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="tooth-quick-actions">
                            <button type="button" class="quick-action-btn" id="addProcedureBtn" disabled>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                Ajouter un acte
                            </button>
                            <button type="button" class="quick-action-btn secondary" id="viewHistoryBtn" disabled>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                                Historique
                            </button>
                        </div>
                        
                        <!-- Procedure Form (Hidden by default) -->
                        <div class="procedure-form-container" id="procedureFormContainer" style="display: none;">
                            <div class="procedure-form-header">
                                <h5 class="procedure-form-title">Nouvel acte dentaire</h5>
                                <button type="button" class="procedure-form-close" id="closeProcedureForm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                            
                            <form id="procedureForm" class="procedure-form">
                                <!-- Consultation Selector -->
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        Consultation <span class="required">*</span>
                                    </label>
                                    <div class="consultation-selector">
                                        <select id="procedureConsultation" class="form-select-modern" required>
                                            <option value="">Chargement...</option>
                                        </select>
                                        <button type="button" class="btn-add-consultation" id="addConsultationBtn" title="Créer une nouvelle consultation">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        </button>
                                    </div>
                                    <div class="form-hint" id="consultationHint">Sélectionnez une consultation existante ou créez-en une nouvelle</div>
                                </div>
                                
                                <!-- Procedure Autocomplete -->
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10,9 9,9 8,9"/></svg>
                                        Acte dentaire <span class="required">*</span>
                                    </label>
                                    <div class="autocomplete-wrapper">
                                        <input type="text" id="procedureAutocomplete" class="form-input-modern" placeholder="Rechercher un acte (ex: extraction, obturation...)" autocomplete="off">
                                        <div class="autocomplete-dropdown" id="procedureDropdown">
                                            <!-- Dynamic results -->
                                        </div>
                                    </div>
                                    <input type="hidden" id="procedureCode" name="procedure_code">
                                    <input type="hidden" id="procedureToothStatus" name="tooth_status">
                                </div>
                                
                                <!-- Status & Price Row -->
                                <div class="form-row-modern">
                                    <div class="form-group-modern flex-1">
                                        <label class="form-label-modern">Statut</label>
                                        <select id="procedureStatus" class="form-select-modern">
                                            <option value="completed">✓ Terminé</option>
                                            <option value="in_progress"> En cours</option>
                                            <option value="planned">📅 Planifié</option>
                                            <option value="cancelled"> Annulé</option>
                                        </select>
                                    </div>
                                    <div class="form-group-modern flex-1">
                                        <label class="form-label-modern">Prix (MAD)</label>
                                        <div class="price-input-wrapper">
                                            <input type="number" id="procedurePrice" class="form-input-modern price-input" min="0" step="0.01" placeholder="0.00">
                                            <span class="price-currency">MAD</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Notes -->
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                                        Notes (optionnel)
                                    </label>
                                    <textarea id="procedureNotes" class="form-textarea-modern" rows="2" placeholder="Notes complémentaires..."></textarea>
                                </div>
                                
                                <!-- Submit Button -->
                                <button type="submit" class="btn-submit-procedure" id="submitProcedureBtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/></svg>
                                    Enregistrer l'acte
                                </button>
                            </form>
                        </div>
                        
                        <!-- Tooth History -->
                        <div class="tooth-history-section" id="toothHistorySection">
                            <h5 class="section-title-modern">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                                Historique des actes
                            </h5>
                            <div id="toothProcedures" class="procedure-list-modern">
                                <div class="empty-state">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                                    <p>Aucun acte enregistré</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Multi-selection info -->
                        <div class="multi-selection-section" id="multiSelectionSection" style="display: none;">
                            <h5 class="section-title-modern">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                Sélection multiple
                            </h5>
                            <div id="multiSelection" class="multi-selection-chips">Aucune</div>
                        </div>
                        
                        <!-- Annotations -->
                        <div class="annotations-section" id="annotationsSection">
                            <h5 class="section-title-modern">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                Annotations
                            </h5>
                            <div id="annotationList" class="annotation-list-modern">
                                <div class="empty-state small">
                                    <p>Aucune annotation</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <section id="consultation-entry" class="card" data-care-tab-panel="care">
                <div class="section-head">
                    <h3>Saisie clinique SOAP</h3>
                    <div class="muted small">Structuration Subjectif, Objectif, Analyse, Plan pour une traçabilité clinique fiable.</div>
                    <button type="button" 
                            class="btn btn-primary" 
                            onclick="document.getElementById('consultationModal').classList.remove('hidden')"
                            data-bs-toggle="modal" 
                            data-bs-target="#consultationModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Nouvelle Consultation
                    </button>
                </div>

                <div class="soap-context-strip">
                    <div class="soap-context-rail">
                        <div class="soap-context-title">Schéma clinique compact</div>
                        <div class="soap-context-copy">Le schéma 3D complet reste dans l'onglet Examen clinique. Ici, la vue reste réduite pour garder l'oeil sur la rédaction SOAP.</div>
                        <div class="soap-context-links">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-care-tab-target="clinical">Ouvrir l'examen clinique</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-care-tab-target="documents">Voir les documents</button>
                        </div>
                        <div class="soap-context-mini-summary">
                            <?php $__currentLoopData = $teethSummary; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($data['count'] > 0): ?>
                                    <div class="soap-context-mini-item">
                                        <span><?php echo e($data['label']); ?></span>
                                        <strong><?php echo e($data['count']); ?></strong>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <div class="soap-context-note card">
                        <div class="soap-context-title">Traçabilité stérilisation</div>
                        <div class="muted small">Emplacement réservé au scan de lot avant validation d'un acte.</div>
                        <div class="soap-trace-slot">
                            <input class="form-control form-control-sm <?php $__errorArgs = ['sterilization_pouch_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="sterilization_pouch_code" value="<?php echo e(old('sterilization_pouch_code')); ?>" placeholder="Code sachet (ex: BATCH-2026-001-003)">
                            <?php $__errorArgs = ['sterilization_pouch_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            <input class="form-control form-control-sm <?php $__errorArgs = ['sterilization_notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="sterilization_notes" value="<?php echo e(old('sterilization_notes')); ?>" placeholder="Notes traçabilité (cycle, opérateur)">
                            <?php $__errorArgs = ['sterilization_notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <?php if($selectedPatient): ?>
                    <div class="consultation-flash">
                        <div><strong>Allergies</strong>: <?php echo e($allergies->implode(', ') ?: 'Aucune'); ?></div>
                        <div><strong>Antécédents</strong>: <?php echo e(collect($selectedPatient->medical_history ?? [])->implode(', ') ?: 'Aucun'); ?></div>
                        <div><strong>Médicaments</strong>: <?php echo e(collect($selectedPatient->current_medications ?? [])->implode(', ') ?: 'Aucun'); ?></div>
                    </div>
                <?php endif; ?>

                <?php if($errors->any() && old('_form_context') === 'consultation'): ?>
                    <div class="alert alert-danger mb-3" role="alert">
                        <div class="fw-bold mb-1">Des champs doivent être corrigés :</div>
                        <ul class="mb-0 ps-3">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Empty state when no consultation form is visible -->
                <div class="text-center py-5 text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-3 opacity-50"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <h5 class="mb-2">Aucune consultation active</h5>
                    <p class="mb-3">Cliquez sur le bouton ci-dessus pour créer une nouvelle consultation</p>
                </div>
            </section>

            <!-- Consultation Modal -->
            <div class="modal fade" id="consultationModal" tabindex="-1" aria-labelledby="consultationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header bg-gradient-to-r from-blue-600 to-blue-700 text-white border-0">
                            <h5 class="modal-title d-flex align-items-center gap-2" id="consultationModalLabel">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                Nouvelle Consultation
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        
                        <div class="modal-body p-4">
                            <form method="POST" action="<?php echo e(route('care.module3.consultations.store', ['patientId' => $selectedPatientId])); ?>" class="row g-3" enctype="multipart/form-data" id="consultationForm">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="_form_context" value="consultation">
                                
                                <!-- Section: Informations Générales -->
                                <div class="col-12">
                                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                        Informations Générales
                                    </h6>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Date consultation <span class="text-danger">*</span></label>
                                    <input class="form-control <?php $__errorArgs = ['consultation_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="date" name="consultation_date" value="<?php echo e(old('consultation_date', now()->toDateString())); ?>" required>
                                    <?php $__errorArgs = ['consultation_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Type de consultation <span class="text-danger">*</span></label>
                                    <select class="form-select <?php $__errorArgs = ['consultation_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="consultation_type" required>
                                        <?php $__currentLoopData = $consultationTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($type['value']); ?>" <?php if(old('consultation_type') === $type['value']): echo 'selected'; endif; ?>><?php echo e($type['label']); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <?php $__errorArgs = ['consultation_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Statut <span class="text-danger">*</span></label>
                                    <select class="form-select <?php $__errorArgs = ['consultation_status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="consultation_status" required>
                                        <option value="attendu" <?php if(old('consultation_status', 'attendu') === 'attendu'): echo 'selected'; endif; ?>>Attendu</option>
                                        <option value="en_soin" <?php if(old('consultation_status') === 'en_soin'): echo 'selected'; endif; ?>>En soin</option>
                                        <option value="termine" <?php if(old('consultation_status') === 'termine'): echo 'selected'; endif; ?>>Terminé</option>
                                    </select>
                                    <?php $__errorArgs = ['consultation_status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Praticien <span class="text-danger">*</span></label>
                                    <select class="form-select <?php $__errorArgs = ['practitioner_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="practitioner_id" required>
                                        <option value="">Sélectionner</option>
                                        <?php $__currentLoopData = $practitioners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $practitioner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($practitioner->id); ?>" <?php if((string) old('practitioner_id', auth()->id()) === (string) $practitioner->id): echo 'selected'; endif; ?>><?php echo e($practitioner->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <?php $__errorArgs = ['practitioner_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Motif de consultation <span class="text-danger">*</span></label>
                                    <input class="form-control <?php $__errorArgs = ['consultation_reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="consultation_reason" list="consultationMotifsList" value="<?php echo e(old('consultation_reason')); ?>" placeholder="Douleur, détartrage, urgence..." required>
                                    <datalist id="consultationMotifsList">
                                        <?php $__currentLoopData = collect($consultationMotifs ?? [])->merge(['Douleur', 'Détartrage', 'Urgence', 'Contrôle', 'Bilan initial'])->unique(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $motif): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($motif); ?>"></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </datalist>
                                    <?php $__errorArgs = ['consultation_reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Rattacher à un RDV</label>
                                    <select class="form-select <?php $__errorArgs = ['appointment_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="appointment_id">
                                        <option value="">Aucun</option>
                                        <?php $__currentLoopData = $appointmentsForPatient; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($appointment->id); ?>" <?php if((string) old('appointment_id') === (string) $appointment->id): echo 'selected'; endif; ?>><?php echo e(optional($appointment->appointment_date)->format('d/m/Y')); ?> <?php echo e(substr($appointment->start_time, 0, 5)); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <?php $__errorArgs = ['appointment_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <!-- Section: SOAP - Subjectif -->
                                <div class="col-12 mt-4">
                                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                        <span class="badge bg-primary me-2">S</span>
                                        Subjectif
                                    </h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Plainte principale</label>
                                    <textarea class="form-control <?php $__errorArgs = ['chief_complaint'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="chief_complaint" rows="2" placeholder="Motif exprimé par le patient..."><?php echo e(old('chief_complaint')); ?></textarea>
                                    <?php $__errorArgs = ['chief_complaint'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Anamnèse</label>
                                    <textarea class="form-control <?php $__errorArgs = ['anamnesis'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="anamnesis" rows="2" placeholder="Historique, contexte, évolution..."><?php echo e(old('anamnesis')); ?></textarea>
                                    <?php $__errorArgs = ['anamnesis'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <!-- Section: SOAP - Objectif -->
                                <div class="col-12 mt-4">
                                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                        <span class="badge bg-success me-2">O</span>
                                        Objectif
                                    </h6>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Examen clinique</label>
                                    <textarea class="form-control <?php $__errorArgs = ['clinical_exam'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="clinical_exam" rows="3" placeholder="Constats objectifs, signes cliniques, résultats observés..."><?php echo e(old('clinical_exam')); ?></textarea>
                                    <?php $__errorArgs = ['clinical_exam'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Constantes vitales</label>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <input class="form-control <?php $__errorArgs = ['vital_bp'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="vital_bp" value="<?php echo e(old('vital_bp')); ?>" placeholder="Tension (ex: 120/80)">
                                            <?php $__errorArgs = ['vital_bp'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        </div>
                                        <div class="col-12">
                                            <input class="form-control <?php $__errorArgs = ['vital_pulse'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="number" name="vital_pulse" value="<?php echo e(old('vital_pulse')); ?>" min="20" max="250" placeholder="Pouls (bpm)">
                                            <?php $__errorArgs = ['vital_pulse'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        </div>
                                        <div class="col-12">
                                            <input class="form-control <?php $__errorArgs = ['vital_weight'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="number" step="0.1" name="vital_weight" value="<?php echo e(old('vital_weight')); ?>" min="1" max="500" placeholder="Poids (kg)">
                                            <?php $__errorArgs = ['vital_weight'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section: SOAP - Analyse -->
                                <div class="col-12 mt-4">
                                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                        <span class="badge bg-warning text-dark me-2">A</span>
                                        Analyse
                                    </h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Diagnostic principal</label>
                                    <input class="form-control <?php $__errorArgs = ['diagnosis_label'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="diagnosis_label" id="diagnosisLabelInput" list="diagnosisCatalogList" value="<?php echo e(old('diagnosis_label')); ?>" placeholder="Commencez à taper un diagnostic...">
                                    <datalist id="diagnosisCatalogList">
                                        <?php $__currentLoopData = $diagnosisCatalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $diagnosis): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($diagnosis['label']); ?>" label="<?php echo e($diagnosis['code']); ?>"></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </datalist>
                                    <input type="hidden" name="diagnosis_code" id="diagnosisCodeInput" value="<?php echo e(old('diagnosis_code')); ?>">
                                    <div class="small text-secondary mt-1">Autocomplete dentaire par nomenclature clinique.</div>
                                    <div class="mt-2 d-none" id="autoRxBlock">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="generateAutoRxBtn">Generer Ordonnance type (Antalgique/Antibiotique)</button>
                                        <div class="small text-secondary mt-1">Proposition automatique pour Pulpite / Extraction.</div>
                                    </div>
                                    <?php $__errorArgs = ['diagnosis_label'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <?php $__errorArgs = ['diagnosis_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Observations cliniques structurées</label>
                                    <div class="rich-editor-toolbar mb-2">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-editor-action="bold">Gras</button>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-editor-action="italic">Italique</button>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-editor-action="insertUnorderedList">Liste</button>
                                    </div>
                                    <div id="consultationObservationsEditor" class="rich-editor <?php $__errorArgs = ['observations'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" contenteditable="true"></div>
                                    <textarea class="d-none" name="observations" id="consultationObservationsInput"><?php echo e(old('observations')); ?></textarea>
                                    <?php $__errorArgs = ['observations'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <!-- Section: SOAP - Plan -->
                                <div class="col-12 mt-4">
                                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                        <span class="badge bg-info me-2">P</span>
                                        Plan
                                    </h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Recommandations</label>
                                    <textarea class="form-control <?php $__errorArgs = ['recommendations'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="recommendations" rows="3" placeholder="Conseils, suivi, réévaluation..."><?php echo e(old('recommendations')); ?></textarea>
                                    <?php $__errorArgs = ['recommendations'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Prescription</label>
                                    <textarea class="form-control <?php $__errorArgs = ['prescription'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="prescription" rows="3" placeholder="Traitement prescrit..."><?php echo e(old('prescription')); ?></textarea>
                                    <?php $__errorArgs = ['prescription'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Pièces jointes</label>
                                    <input class="form-control <?php $__errorArgs = ['attachments'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php $__errorArgs = ['attachments.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="file" name="attachments[]" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple>
                                    <div class="small text-secondary mt-1">Jusqu'à 10 fichiers, 10 Mo max chacun.</div>
                                    <?php $__errorArgs = ['attachments'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <?php $__errorArgs = ['attachments.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Notes supplémentaires</label>
                                    <textarea class="form-control <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="notes" rows="3" placeholder="Éléments complémentaires, consignes, suivi..."><?php echo e(old('notes')); ?></textarea>
                                    <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Constantes additionnelles (optionnel)</label>
                                    <textarea class="form-control <?php $__errorArgs = ['vital_signs'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="vital_signs" rows="2" placeholder="Ex: Température: 37.2&#10;SpO2: 98%"><?php echo e(old('vital_signs')); ?></textarea>
                                    <?php $__errorArgs = ['vital_signs'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input <?php $__errorArgs = ['consent_obtained'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="checkbox" name="consent_obtained" value="1" <?php if(old('consent_obtained')): echo 'checked'; endif; ?> required>
                                        <label class="form-check-label fw-semibold">Consentement éclairé obtenu <span class="text-danger">*</span></label>
                                    </div>
                                    <?php $__errorArgs = ['consent_obtained'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </form>
                        </div>
                        
                        <div class="modal-footer bg-light border-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                Annuler
                            </button>
                            <button type="submit" form="consultationForm" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/></svg>
                                Enregistrer la consultation
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <section id="medical-history-complete" class="card" data-care-tab-panel="clinical">
                <div class="section-head d-flex justify-content-between align-items-center gap-2">
                    <h3>Historique MÃ©dical Complet</h3>
                    <span class="badge bg-blue-lt">Chronologie globale</span>
                </div>
                <div class="accordion" id="medicalHistoryAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="historyVisitsHead">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#historyVisitsBody">Consultations</button>
                        </h2>
                        <div id="historyVisitsBody" class="accordion-collapse collapse" data-bs-parent="#medicalHistoryAccordion">
                            <div class="accordion-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>Date</th><th>Motif</th><th>Praticien</th><th>Actions</th></tr></thead>
                                        <tbody>
                                        <?php $__empty_1 = true; $__currentLoopData = $consultations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $consultation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <tr>
                                                <td><?php echo e(optional($consultation->consultation_date)->format('d/m/Y')); ?></td>
                                                <td><?php echo e($consultation->consultation_reason); ?></td>
                                                <td><?php echo e($consultation->practitioner?->name ?: '-'); ?></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-primary">Voir</button> <button type="button" class="btn btn-sm btn-outline-secondary">Imprimer</button></td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr><td colspan="4" class="text-secondary">Aucune consultation.</td></tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="historyRxHead">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#historyRxBody">Ordonnances</button>
                        </h2>
                        <div id="historyRxBody" class="accordion-collapse collapse" data-bs-parent="#medicalHistoryAccordion">
                            <div class="accordion-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>Date</th><th>Numero</th><th>Praticien</th><th>Actions</th></tr></thead>
                                        <tbody>
                                        <?php $__empty_1 = true; $__currentLoopData = $prescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <tr>
                                                <td><?php echo e(optional($rx->issued_at)->format('d/m/Y H:i')); ?></td>
                                                <td><?php echo e($rx->prescription_number); ?></td>
                                                <td><?php echo e($rx->practitioner?->name ?: '-'); ?></td>
                                                <td><a class="btn btn-sm btn-outline-primary" target="_blank" href="<?php echo e(route('care.module3.prescriptions.pdf', ['prescription' => $rx->id])); ?>">PDF</a></td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr><td colspan="4" class="text-secondary">Aucune ordonnance.</td></tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="historyQuestionnaireHead">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#historyQuestionnaireBody">Questionnaires</button>
                        </h2>
                        <div id="historyQuestionnaireBody" class="accordion-collapse collapse" data-bs-parent="#medicalHistoryAccordion">
                            <div class="accordion-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>Date</th><th>Nom</th><th>Praticien</th><th>Actions</th></tr></thead>
                                        <tbody>
                                        <?php $__empty_1 = true; $__currentLoopData = $questionnaireResponses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $response): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <tr>
                                                <td><?php echo e(optional($response->answered_at)->format('d/m/Y')); ?></td>
                                                <td><?php echo e($response->questionnaire?->name ?? 'Questionnaire'); ?></td>
                                                <td><?php echo e($response->practitioner?->name ?: '-'); ?></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-primary">Voir</button></td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr><td colspan="4" class="text-secondary">Aucun questionnaire.</td></tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="timeline" class="card" data-care-tab-panel="clinical">
                <div class="section-head"><h3>Timeline Clinique</h3></div>
                <form method="GET" class="row g-2 align-items-end mb-3" id="timelineFilterForm">
                    <input type="hidden" name="patient_id" value="<?php echo e($selectedPatientId); ?>">
                    <div class="col-md-3">
                        <label class="form-label">Du</label>
                        <input type="date" class="form-control" name="timeline_from" value="<?php echo e($timelineFilters['timeline_from'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Au</label>
                        <input type="date" class="form-control" name="timeline_to" value="<?php echo e($timelineFilters['timeline_to'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type de soin</label>
                        <select class="form-select" name="timeline_type">
                            <option value="all" <?php if(($timelineFilters['timeline_type'] ?? 'all') === 'all'): echo 'selected'; endif; ?>>Tous</option>
                            <option value="consultation" <?php if(($timelineFilters['timeline_type'] ?? '') === 'consultation'): echo 'selected'; endif; ?>>Consultations</option>
                            <option value="procedure" <?php if(($timelineFilters['timeline_type'] ?? '') === 'procedure'): echo 'selected'; endif; ?>>Actes</option>
                            <option value="treatment_plan" <?php if(($timelineFilters['timeline_type'] ?? '') === 'treatment_plan'): echo 'selected'; endif; ?>>Plans</option>
                            <option value="imaging" <?php if(($timelineFilters['timeline_type'] ?? '') === 'imaging'): echo 'selected'; endif; ?>>Imagerie</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Recherche texte SOAP</label>
                        <input type="search" class="form-control" id="timelineSearchInput" placeholder="Motif, diagnostic, observations, praticien...">
                    </div>
                </form>
                <div class="timeline-vertical">
                    <?php $__empty_1 = true; $__currentLoopData = ($timeline['events'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <article class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content" data-timeline-row data-searchable="<?php echo e(\Illuminate\Support\Str::lower(($event['searchable'] ?? collect([$event['label'] ?? '', $event['diagnosis'] ?? '', $event['practitioner'] ?? '', $event['consultation'] ?? '', $event['notes'] ?? ''])->filter()->implode(' | ')))); ?>">
                                <header>
                                    <strong><?php echo e($event['label']); ?></strong>
                                    <span class="badge bg-indigo-lt"><?php echo e($event['type']); ?></span>
                                </header>
                                <div class="meta d-flex flex-wrap gap-2 align-items-center"><?php echo e($event['date'] ?? '-'); ?> | Motif: <?php echo e($event['consultation_reason'] ?? $event['label'] ?? '-'); ?> | Praticien: <?php echo e($event['practitioner'] ?? '-'); ?> | Statut: <?php echo e($event['status'] ?? '-'); ?></div>
                                <?php if($event['type'] === 'consultation'): ?>
                                    <div class="meta">Type: <?php echo e($event['consultation_type'] ?? '-'); ?> | Paiement: <?php echo e($event['payment_status'] ?? '-'); ?></div>
                                    <div class="meta">Diagnostic: <?php echo e($event['diagnosis'] ?? '-'); ?></div>
                                    <?php if(!empty($event['invoice_number'])): ?><div class="meta">Facture: <?php echo e($event['invoice_number']); ?></div><?php endif; ?>
                                <?php endif; ?>
                                <?php if(!empty($event['tooth_number'])): ?><div class="meta">Dent: <?php echo e($event['tooth_number']); ?></div><?php endif; ?>
                                <?php if(!empty($event['specialty'])): ?><div class="meta">Specialite: <?php echo e($event['specialty']); ?></div><?php endif; ?>
                                <?php if($event['type'] === 'treatment_plan'): ?>
                                    <div class="meta">Budget estime: <?php echo e(number_format((float) ($event['estimated_cost'] ?? 0), 2, ',', ' ')); ?> MAD</div>
                                <?php endif; ?>
                                <div class="timeline-actions mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary">Voir</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary">Imprimer</button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-secondary">Aucun evenement clinique.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section id="procedures-history" class="card" data-care-tab-panel="care">
                <div class="section-head">
                    <h3 class="d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        Historique des actes par consultation
                    </h3>
                    <div class="muted small">Suivi chronologique des actes dentaires rÃ©alisÃ©s Ã  chaque consultation</div>
                </div>

                <div class="consultation-timeline">
                    <?php $__empty_1 = true; $__currentLoopData = $consultations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $consultation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $consProcedures = $consultation->procedures;
                            $isToday = $consultation->consultation_date?->isToday();
                        ?>
                        <details class="consultation-group" <?php echo e($loop->first ? 'open' : ''); ?>>
                            <summary class="consultation-group-header">
                                <div class="consultation-group-meta">
                                    <span class="consultation-date <?php echo e($isToday ? 'text-primary fw-bold' : ''); ?>">
                                        <?php echo e($consultation->consultation_date?->format('d/m/Y') ?? 'N/A'); ?>

                                        <?php if($isToday): ?>
                                            <span class="badge bg-primary-lt ms-1">Aujourd'hui</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="consultation-type badge bg-secondary-lt"><?php echo e($consultation->consultation_type ?? 'Consultation'); ?></span>
                                    <span class="consultation-practitioner text-muted small">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        <?php echo e($consultation->practitioner?->name ?? 'N/A'); ?>

                                    </span>
                                    <span class="badge bg-info-lt ms-auto">
                                        <?php echo e($consProcedures->count()); ?> acte(s)
                                    </span>
                                </div>
                                <?php if($consultation->consultation_reason): ?>
                                    <div class="consultation-reason small text-muted mt-1">
                                        <strong>Motif:</strong> <?php echo e($consultation->consultation_reason); ?>

                                    </div>
                                <?php endif; ?>
                            </summary>

                            <div class="consultation-procedures">
                                <?php $__empty_2 = true; $__currentLoopData = $consProcedures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $procedure): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                    <div class="procedure-card">
                                        <div class="procedure-card-head">
                                            <strong><?php echo e($procedure->name); ?></strong>
                                            <span class="badge <?php echo e(match($procedure->status) {
                                                'completed' => 'bg-success',
                                                'in_progress' => 'bg-warning',
                                                'planned' => 'bg-info',
                                                'cancelled' => 'bg-danger',
                                                default => 'bg-secondary'
                                            }); ?>"><?php echo e($procedure->status); ?></span>
                                        </div>
                                        <div class="procedure-card-body">
                                            <div class="procedure-detail">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                                                <span>Dent <strong><?php echo e($procedure->tooth_number); ?></strong></span>
                                            </div>
                                            <?php if($procedure->tooth_surfaces): ?>
                                                <div class="procedure-detail">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                                    <span>Surfaces: <?php echo e(is_array($procedure->tooth_surfaces) ? implode(', ', $procedure->tooth_surfaces) : $procedure->tooth_surfaces); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if($procedure->specialty): ?>
                                                <div class="procedure-detail">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                                    <span><?php echo e($procedure->specialty->name); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if($procedure->practitioner): ?>
                                                <div class="procedure-detail">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                                    <span><?php echo e($procedure->practitioner->name); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if($procedure->price > 0): ?>
                                                <div class="procedure-detail">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                                    <span><?php echo e(number_format((float) $procedure->price, 2, ',', ' ')); ?> MAD</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if($procedure->notes): ?>
                                                <div class="procedure-notes mt-1 small text-muted">
                                                    <em><?php echo e($procedure->notes); ?></em>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                    <div class="text-muted small p-2">Aucun acte enregistrÃ© pour cette consultation.</div>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-secondary text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-2"><path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/><polyline points="9 11 12 14 22 4"/></svg>
                            <div>Aucune consultation trouvÃ©e pour ce patient.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section id="imaging" class="card" data-care-tab-panel="documents">
                <div class="section-head d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="mb-0">Examens Imagerie</h3>
                    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#radiologyRequestCollapse" aria-expanded="false" aria-controls="radiologyRequestCollapse">+ Demande Imagerie</button>
                </div>

                <div class="collapse" id="radiologyRequestCollapse">
                    <div class="border rounded p-3 mb-3 bg-light">
                        <h4 class="h6 mb-3">Prescription Radiologique (DICOM MWL)</h4>
                        <form method="POST" action="<?php echo e(route('care.module3.radiology-requests.store', ['patientId' => $selectedPatientId])); ?>" class="row g-2">
                            <?php echo csrf_field(); ?>
                            <div class="col-md-4">
                                <label class="form-label">Type d'examen</label>
                                <select class="form-select" name="exam_type" required>
                                    <option value="">-- Nomenclature --</option>
                                    <option value="CT cranio-facial">CT cranio-facial</option>
                                    <option value="Echographie cervicale">Echographie cervicale</option>
                                    <option value="Cone Beam (CBCT)">Cone Beam (CBCT)</option>
                                    <option value="Radiographie panoramique">Radiographie panoramique</option>
                                    <option value="Radiographie periapicale">Radiographie periapicale</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Region anatomique</label>
                                <input class="form-control" name="anatomical_region" placeholder="Mandibule, sinus, ATM..." required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Priorite</label>
                                <select class="form-select" name="priority" required>
                                    <option value="routine">Routine</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Modalite cible</label>
                                <select class="form-select" name="target_modality" required>
                                    <option value="CT">CT (Scanner)</option>
                                    <option value="US">US (Echographe)</option>
                                    <option value="CR">CR (Radiographie)</option>
                                    <option value="DX">DX (Radiographie numerique)</option>
                                    <option value="MR">MR (IRM)</option>
                                    <option value="OT">OT (Autre)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Station AE Title</label>
                                <input class="form-control" name="scheduled_station_ae_title" value="MODALITY_AE" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Medecin prescripteur</label>
                                <select class="form-select" name="prescribing_physician_id" required>
                                    <option value="">-- Selectionner --</option>
                                    <?php $__currentLoopData = $practitioners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $practitioner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($practitioner->id); ?>" <?php if(auth()->id() === $practitioner->id): echo 'selected'; endif; ?>><?php echo e($practitioner->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Motif clinique</label>
                                <textarea class="form-control" name="clinical_reason" rows="2" placeholder="Suspicion fracture, douleur persistante, bilan pre-implantaire..." required></textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button class="btn btn-success" type="submit">Generer Demande + Worklist Orthanc</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Accession</th>
                                <th>Examen</th>
                                <th>Priorite</th>
                                <th>Statut Workflow</th>
                                <th>Station AE</th>
                                <th>Prescripteur</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $radiologyRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $request): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $statusLabel = match($request->workflow_status) {
                                        'requested' => 'Demande',
                                        'in_progress' => 'En cours',
                                        'received' => 'Recu',
                                        'completed' => 'Termine',
                                        default => $request->workflow_status,
                                    };
                                    $statusTone = match($request->workflow_status) {
                                        'requested' => 'bg-azure-lt',
                                        'in_progress' => 'bg-blue-lt',
                                        'received' => 'bg-indigo-lt',
                                        'completed' => 'bg-green-lt',
                                        default => 'bg-secondary-lt',
                                    };
                                    $payload = $request->orthanc_payload;
                                    $viewerStudyId = data_get($payload, 'study_uid')
                                        ?? data_get($payload, 'orthanc_study_id')
                                        ?? $request->study_instance_uid;
                                    $orthancViewerBaseUrl = rtrim((string) config('ris.orthanc.viewer_base_url', config('ris.orthanc.base_url', 'http://127.0.0.1:8042')), '/');
                                    $viewerUrl = $viewerStudyId ? $orthancViewerBaseUrl.'/stone-webviewer/index.html?study='.urlencode((string) $viewerStudyId) : null;
                                    $isReady = in_array($request->workflow_status, ['received', 'completed']);
                                ?>
                                <tr>
                                    <td>
                                        <div><strong><?php echo e($request->accession_number); ?></strong></div>
                                        <div class="small text-secondary">UID: <?php echo e($request->study_instance_uid); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo e($request->exam_type); ?></div>
                                        <div class="small text-secondary"><?php echo e($request->anatomical_region); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo e($request->priority === 'urgent' ? 'bg-red-lt' : 'bg-yellow-lt'); ?>"><?php echo e(strtoupper($request->priority)); ?></span>
                                    </td>
                                    <td><span class="badge <?php echo e($statusTone); ?>"><?php echo e($statusLabel); ?></span></td>
                                    <td><?php echo e($request->scheduled_station_ae_title); ?> / <?php echo e($request->target_modality); ?></td>
                                    <td><?php echo e($request->prescribingPhysician?->name ?: '-'); ?></td>
                                    <td>
                                        <?php if($isReady && $viewerUrl): ?>
                                            <a href="<?php echo e($viewerUrl); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Voir</a>
                                        <?php else: ?>
                                            <span class="text-secondary small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="7" class="text-secondary">Aucune demande radiologie.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="section-head"><h3>Manifest Imagerie et Upload</h3></div>
                <form method="POST" action="<?php echo e(route('care.module3.imaging.store', ['patientId' => $selectedPatientId])); ?>" enctype="multipart/form-data" class="upload-box" id="dropZoneForm">
                    <?php echo csrf_field(); ?>
                    <div class="upload-dropzone" id="dropZone">
                        <input type="file" name="study_file" id="studyFileInput" class="d-none" accept=".dcm,.dicom,.stl,.jpg,.jpeg,.png,.webp,.tif,.tiff">
                        <div class="upload-icon">+</div>
                        <div><strong>Glissez-deposez</strong> vos fichiers DICOM / radios ici</div>
                        <div class="muted">ou cliquez pour selectionner un fichier</div>
                        <div id="dropFileName" class="mt-2 small"></div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-md-3"><label class="form-label">Modalite</label><select class="form-select" name="modality" required><option value="xray">X-Ray</option><option value="cbct">CBCT</option><option value="stl">STL</option><option value="dicom">DICOM</option></select></div>
                        <div class="col-md-3"><label class="form-label">Mime type</label><input class="form-control" name="mime_type" placeholder="application/dicom"></div>
                        <div class="col-md-3"><label class="form-label">Study UID</label><input class="form-control" name="study_uid"></div>
                        <div class="col-md-3"><label class="form-label">Capture le</label><input class="form-control" type="datetime-local" name="captured_at"></div>
                        <div class="col-12"><label class="form-label">Chemin manuel (optionnel)</label><input class="form-control" name="file_path" placeholder="storage/imaging/patient_1/cbct_001.dcm"></div>
                        <div class="col-12"><button class="btn btn-primary">Uploader / Enregistrer</button></div>
                    </div>
                </form>

                <div class="imaging-cards mt-3">
                    <?php $__empty_1 = true; $__currentLoopData = ($manifest['items'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $isImagePreview = in_array(strtolower((string) ($item['mime_type'] ?? '')), ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/tiff'], true)
                                || preg_match('/\.(png|jpe?g|webp|gif|bmp|tiff?)$/i', (string) ($item['file_path'] ?? ''));
                        ?>
                        <article class="imaging-card">
                            <div>
                                <div><strong><?php echo e(strtoupper($item['modality'])); ?></strong> <span class="badge bg-blue-lt">#<?php echo e($item['id']); ?></span></div>
                                <div class="muted small"><?php echo e($item['study_uid'] ?: 'Sans Study UID'); ?></div>
                                <div class="muted small"><?php echo e(\Illuminate\Support\Str::limit($item['file_path'], 70)); ?></div>
                            </div>
                            <div class="imaging-actions">
                                <?php if($isImagePreview): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-preview" data-src="<?php echo e(asset($item['file_path'])); ?>">Previsualiser</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Apercu indisponible</button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-secondary">Aucune etude imagerie.</div>
                    <?php endif; ?>
                </div>
            </section>

            <?php if($risEnabled && ($risOrders ?? collect())->isNotEmpty()): ?>
            <section id="ris-examens" class="card" data-care-tab-panel="documents">
                <div class="section-head d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="mb-0">RIS Examens (Orthanc)</h3>
                    <a href="<?php echo e(route('ris.exams.index')); ?>" class="btn btn-sm btn-outline-dark">
                        <i class="ti ti-activity-heartbeat"></i> RIS Complet
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Accession</th>
                                <th>Examen</th>
                                <th>Modalite</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $risOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $payload = $order->orthanc_payload;
                                    $viewerStudyId = data_get($payload, 'study_uid')
                                        ?? data_get($payload, 'orthanc_study_id');
                                    $orthancViewerBaseUrl = rtrim((string) config('ris.orthanc.viewer_base_url', config('ris.orthanc.base_url', 'http://127.0.0.1:8042')), '/');
                                    $viewerUrl = $viewerStudyId ? $orthancViewerBaseUrl.'/stone-webviewer/index.html?study='.urlencode((string) $viewerStudyId) : null;

                                    $statusLabel = match($order->status) {
                                        'images_recues' => 'Images recues',
                                        'termine' => 'Termine',
                                        'en_attente' => 'En attente',
                                        'ordonne' => 'Ordonne',
                                        'annule' => 'Annule',
                                        default => $order->status,
                                    };
                                    $isReady = in_array($order->status, ['images_recues', 'termine']);
                                    $statusBadge = $isReady ? 'bg-green-lt' : ($order->status === 'annule' ? 'bg-red-lt' : 'bg-blue-lt');
                                ?>
                                <tr>
                                    <td><code><?php echo e($order->accession_number); ?></code></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($order->procedure?->label ?: $order->requested_procedure_description); ?></div>
                                        <div class="small text-secondary"><?php echo e(Str::limit($order->clinical_indication, 60)); ?></div>
                                    </td>
                                    <td><?php echo e($order->modality?->name ?: '-'); ?></td>
                                    <td><span class="badge <?php echo e($statusBadge); ?>"><?php echo e($statusLabel); ?></span></td>
                                    <td class="small"><?php echo e($order->requested_at?->format('d/m/Y H:i')); ?></td>
                                    <td>
                                        <?php if($isReady && $viewerUrl): ?>
                                            <a href="<?php echo e($viewerUrl); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Viewer</a>
                                        <?php elseif($isReady): ?>
                                            <a href="<?php echo e(route('ris.exams.show', $order)); ?>" class="btn btn-sm btn-outline-secondary">Ouvrir</a>
                                        <?php else: ?>
                                            <span class="text-secondary small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <section id="treatment" class="card" data-care-tab-panel="documents">
                <div class="section-head"><h3>Plan de Traitement et Avancement Financier</h3></div>
                <div class="treatment-grid">
                    <?php $__empty_1 = true; $__currentLoopData = $treatmentPlans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $progress = (float) $plan->total_estimated_cost > 0 ? min(100, round(((float) $plan->paid_amount / (float) $plan->total_estimated_cost) * 100, 1)) : 0;
                            $remain = max(0, (float) $plan->total_estimated_cost - (float) $plan->paid_amount);
                        ?>
                        <article class="treatment-card">
                            <header>
                                <strong><?php echo e($plan->name); ?></strong>
                                <span class="badge bg-indigo-lt"><?php echo e($plan->status); ?></span>
                            </header>
                            <div class="muted small">Praticien: <?php echo e($plan->practitioner?->name ?: '-'); ?></div>
                            <div class="money-row">
                                <span>Total: <?php echo e(number_format((float) $plan->total_estimated_cost, 2, ',', ' ')); ?> MAD</span>
                                <span>Paye: <?php echo e(number_format((float) $plan->paid_amount, 2, ',', ' ')); ?> MAD</span>
                                <span>Reste: <?php echo e(number_format($remain, 2, ',', ' ')); ?> MAD</span>
                            </div>
                            <div class="progress mt-2" style="height:8px">
                                <div class="progress-bar" style="width: <?php echo e($progress); ?>%"></div>
                            </div>
                            <div class="small text-secondary mt-1"><?php echo e($progress); ?>%</div>
                            <ul class="phase-list">
                                <?php $__currentLoopData = ($plan->phases ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $phase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <span><?php echo e($phase['name'] ?? '-'); ?></span>
                                        <span class="badge bg-azure-lt"><?php echo e($phase['status'] ?? 'planned'); ?></span>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-secondary">Aucun plan de traitement.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section id="advanced-quote" class="card" data-care-tab-panel="documents">
                <div class="section-head"><h3>Devis Conventionne, Signature et Consentement</h3></div>
                <div class="row g-3">
                    <div class="col-lg-6">
                        <h4 class="mb-2">Generer devis a partir d un plan</h4>
                        <form method="POST" action="<?php echo e(route('care.module3.quote.create', ['plan' => $treatmentPlans->first()->id ?? 0])); ?>" id="quoteCreateForm">
                            <?php echo csrf_field(); ?>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Taux assurance (%)</label>
                                    <input class="form-control" type="number" name="insurance_rate" min="0" max="100" step="0.01" value="70">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Taux mutuelle (%)</label>
                                    <input class="form-control" type="number" name="mutual_rate" min="0" max="100" step="0.01" value="0">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Plan</label>
                                    <select class="form-select" name="plan_selector" onchange="this.form.action='<?php echo e(url('/care/module-3/treatment-plan')); ?>/'+this.value+'/quote';">
                                        <?php $__currentLoopData = $treatmentPlans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($p->id); ?>"><?php echo e($p->name); ?> - <?php echo e(number_format((float) $p->total_estimated_cost, 2, ',', ' ')); ?> MAD</option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                                <div class="col-12"><button class="btn btn-primary">Creer devis PDF</button></div>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <h4 class="mb-2">Signer consentement sur tablette</h4>
                        <?php if(($treatmentQuotes->first())): ?>
                            <?php
                                $latestQuote = $treatmentQuotes->first();
                            ?>
                            <form method="POST" action="<?php echo e(route('care.module3.quote.sign', ['quote' => $latestQuote->id])); ?>">
                                <?php echo csrf_field(); ?>
                                <div class="mb-2 small">Devis cible: <strong><?php echo e($latestQuote->quote_number); ?></strong></div>
                                <input class="form-control mb-2" name="patient_name" placeholder="Nom patient signataire" required>
                                <input type="hidden" name="signature_data" id="signatureDataInput">
                                <canvas id="signaturePad" style="border:1px dashed #94a3b8;border-radius:8px;width:100%;height:160px;"></canvas>
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-outline-secondary" id="clearSignature">Effacer</button>
                                    <button class="btn btn-success" type="submit" id="signSubmitBtn">Valider signature</button>
                                    <a class="btn btn-outline-primary" href="<?php echo e(route('care.module3.quote.pdf', ['quote' => $latestQuote->id])); ?>" target="_blank">Voir PDF</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="text-secondary">Aucun devis disponible. Generez un devis pour activer la signature.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-3">
                    <h4 class="mb-2">Historique devis</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Numero</th><th>Date</th><th>Total</th><th>Part mutuelle</th><th>Reste patient</th><th>Statut</th><th></th></tr></thead>
                            <tbody id="quoteHistoryBody">
                            <?php $__empty_1 = true; $__currentLoopData = $treatmentQuotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($q->quote_number); ?></td>
                                    <td><?php echo e(optional($q->quote_date)->format('d/m/Y')); ?></td>
                                    <td><?php echo e(number_format((float) $q->subtotal, 2, ',', ' ')); ?> MAD</td>
                                    <td><?php echo e(number_format((float) $q->insurance_amount + (float) $q->mutual_amount, 2, ',', ' ')); ?> MAD</td>
                                    <td><?php echo e(number_format((float) $q->patient_amount, 2, ',', ' ')); ?> MAD</td>
                                    <td><span class="badge bg-indigo-lt"><?php echo e($q->status); ?></span></td>
                                    <td><a class="btn btn-sm btn-outline-primary" href="<?php echo e(route('care.module3.quote.pdf', ['quote' => $q->id])); ?>" target="_blank">PDF</a></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="7" class="text-secondary">Aucun devis.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="prescriptions" class="card mb-4" data-care-tab-panel="documents">
    <div class="section-head d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="mb-0">Ordonnance Dentaires</h3>
            <div class="small text-secondary">Split-view ultra fluide avec recherche intelligente, apercu papier et visionneuse PDF.js.</div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-success-lt rx-head-badge">
                <i class="ti ti-shield-check me-1"></i><?php echo e($hasMajorRisk ? 'Securite active' : 'Securise'); ?>

            </span>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="rxClearAllBtn">Effacer tout</button>
            <button type="button" class="btn btn-primary btn-sm" id="applyRxTemplate">Appliquer modele favori</button>
        </div>
    </div>

    <div class="rx-split-view">
        <form method="POST" action="<?php echo e(route('care.module3.prescriptions.store', ['patientId' => $selectedPatientId])); ?>" id="rxForm" class="rx-editor-panel">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="items_json" id="rxItemsJson">
            <input type="hidden" name="signature_data" id="rxSignatureData">
            <input type="hidden" name="prescription_template_id" id="rxSelectedTemplateId" value="">

            <div class="rx-security-banner <?php echo e($hasMajorRisk ? 'is-risk' : 'is-safe'); ?>">
                <div class="rx-security-icon">
                    <i class="ti <?php echo e($hasMajorRisk ? 'ti-alert-triangle' : 'ti-shield-check'); ?>"></i>
                </div>
                <div>
                    <div class="fw-bold"><?php echo e($hasMajorRisk ? 'Controle de securite requis' : 'Controle de securite valide'); ?></div>
                    <?php if($hasMajorRisk): ?>
                        <div class="small"><?php echo e($allergies->isNotEmpty() ? 'Allergies: '.$allergies->implode(', ') : 'Aucune allergie declaree'); ?></div>
                        <div class="small"><?php echo e($riskTags->isNotEmpty() ? 'Risques: '.$riskTags->implode(', ') : 'Aucun risque majeur'); ?></div>
                    <?php else: ?>
                        <div class="small">Les champs sensibles restent masques dans l impression par defaut.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rx-card-block">
                <div class="rx-block-head">
                    <div>
                        <div class="rx-block-kicker">Smart Search</div>
                        <h4>Ajouter un medicament</h4>
                    </div>
                    <div class="small text-secondary">Recherche instantanee dans le catalogue local et les modeles favoris.</div>
                </div>
                <div class="rx-search-wrap position-relative">
                    <span class="rx-search-icon"><i class="ti ti-search"></i></span>
                    <input class="form-control rx-search-input" id="rxMedicationSearch" placeholder="Rechercher par nom, classe ou dosage..." autocomplete="off">
                    <div id="rxSearchResults" class="rx-search-results d-none"></div>
                </div>
            </div>

            <div class="rx-card-block">
                <div class="rx-block-head">
                    <div>
                        <div class="rx-block-kicker">Modeles favoris</div>
                        <h4>Appliquer un protocole</h4>
                    </div>
                    <div class="small text-secondary">Le bouton applique le premier modele actif si aucun choix n est fait.</div>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-md-7">
                        <label class="form-label">Protocole favori</label>
                        <select class="form-select" id="rxTemplateSelect">
                            <option value="">-- Libre dynamique --</option>
                            <?php $__currentLoopData = $prescriptionTemplates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tpl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($tpl->id); ?>"><?php echo e($tpl->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <div class="rx-template-note" id="rxTemplateNote">Selectionnez un modele puis appuyez sur appliquer.</div>
                    </div>
                </div>
            </div>

            <div class="rx-card-block">
                <div class="rx-block-head">
                    <div>
                        <div class="rx-block-kicker">Medications</div>
                        <h4>Ordre et saisie rapide</h4>
                    </div>
                    <div class="small text-secondary">Glissez les medicaments pour reordonner la prescription.</div>
                </div>
                <div id="rxItemsContainer" class="rx-items-list">
                    <div class="rx-empty-state">
                        <div class="rx-empty-icon"><i class="ti ti-pill"></i></div>
                        <div>Recherchez un medicament pour construire l ordonnance.</div>
                    </div>
                </div>
            </div>

            <div class="alert alert-danger d-none mt-3" id="rxBlockingAlertWrap">
                <div class="d-flex gap-2 align-items-start">
                    <i class="ti ti-alert-circle fs-4"></i>
                    <div>
                        <div class="fw-bold">Blocage securite</div>
                        <div id="rxBlockingAlert"></div>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning d-none mt-3" id="rxWarningAlertWrap">
                <div class="d-flex gap-2 align-items-start">
                    <i class="ti ti-alert-triangle fs-4"></i>
                    <div>
                        <div class="fw-bold">Avertissement</div>
                        <div id="rxWarningAlert"></div>
                    </div>
                </div>
            </div>

            <div class="rx-signature-grid">
                <div class="rx-card-block">
                    <div class="rx-block-head">
                        <div>
                            <div class="rx-block-kicker">Signature</div>
                            <h4>Validation numerique</h4>
                        </div>
                    </div>
                    <div class="rx-signature-shell">
                        <canvas id="rxSignaturePad"></canvas>
                    </div>
                </div>
                <div class="rx-card-block">
                    <div class="rx-block-head">
                        <div>
                            <div class="rx-block-kicker">Contenu imprime</div>
                            <h4>Notes conservees hors impression</h4>
                        </div>
                    </div>
                    <label class="form-label">Notes internes</label>
                    <textarea class="form-control" name="notes" id="rxNotesInput" rows="6" placeholder="Recommandations internes, suivi, rappels..."></textarea>
                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-outline-secondary flex-fill" id="clearRxSignature">Effacer signature</button>
                        <button type="submit" class="btn btn-success flex-fill">
                            <i class="ti ti-check me-1"></i>Generer ordonnance
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <aside class="rx-preview-panel">
            <div class="rx-preview-toolbar">
                <div>
                    <div class="rx-block-kicker">Apercu papier</div>
                    <h4 class="mb-0">Format A5 pret a imprimer</h4>
                </div>
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="rxRenderPdfBtn">Rafraichir PDF</button>
                    <button type="button" class="btn btn-primary btn-sm" id="rxPrintBtn">Imprimer A5</button>
                </div>
            </div>

            <div class="rx-paper-sheet" id="rxPaperSheet">
                <div class="rx-paper-header">
                    <div class="rx-logo-mark">MO</div>
                    <div>
                        <div class="rx-paper-cabinet">Cabinet Dentaire MediOffice</div>
                        <div class="rx-paper-sub">Ordonnance medicale optimise A5</div>
                    </div>
                    <div class="rx-paper-meta">
                        <div><strong>MRN</strong> <span id="rxPaperMrn"><?php echo e($selectedPatient?->medical_record_number ?? '-'); ?></span></div>
                        <div><strong>Date</strong> <span id="rxPaperDate"><?php echo e(now()->format('d/m/Y H:i')); ?></span></div>
                    </div>
                </div>

                <div class="rx-paper-patient">
                    <div>
                        <span class="rx-paper-label">Patient</span>
                        <div class="rx-paper-name" id="rxPaperPatientName"><?php echo e($selectedPatient?->full_name ?? 'Patient non selectionne'); ?></div>
                    </div>
                    <div class="rx-paper-patient-meta">
                        <span id="rxPaperPatientAge"><?php echo e($selectedPatient?->age ?? '-'); ?> ans</span>
                        <span id="rxPaperPatientInitials"><?php echo e($selectedPatient ? strtoupper(substr($selectedPatient->first_name, 0, 1) . substr($selectedPatient->last_name, 0, 1)) : '--'); ?></span>
                    </div>
                </div>

                <div class="rx-paper-section">
                    <div class="rx-paper-section-title">Prescription</div>
                    <div id="rxPaperItems" class="rx-paper-items">
                        <div class="rx-empty-state rx-empty-state-compact">
                            <div class="rx-empty-icon"><i class="ti ti-file-text"></i></div>
                            <div>L ordonnance se met a jour en temps reel.</div>
                        </div>
                    </div>
                </div>

                <div class="rx-paper-footer">
                    <div class="rx-paper-footer-copy">
                        Adresse, notes internes et antecedents ne sont pas inclus dans la vue d impression par defaut.
                    </div>
                    <div class="rx-paper-signature">
                        <div class="rx-paper-signature-line"></div>
                        <span>Signature praticien</span>
                    </div>
                </div>
            </div>

            <div class="rx-pdf-viewer-shell">
                <div class="rx-pdf-viewer-head">
                    <div>
                        <div class="rx-block-kicker">Visionneuse PDF.js</div>
                        <h4 class="mb-0">Apercu final avant impression</h4>
                    </div>
                    <span class="small text-secondary" id="rxPdfStatus">Aucun document genere pour le moment.</span>
                </div>
                <canvas id="rxPdfCanvas" class="rx-pdf-canvas"></canvas>
            </div>

            <div class="rx-history-shell">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold text-uppercase small">Historique</div>
                        <div class="small text-secondary">Documents deja emis</div>
                    </div>
                    <span class="badge bg-azure-lt"><?php echo e(count($prescriptions)); ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle rx-history-table">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th class="text-center">Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $prescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo e($rx->prescription_number); ?></div>
                                        <div class="small text-secondary"><?php echo e(optional($rx->issued_at)->format('d/m/Y H:i')); ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo e($rx->status === 'issued' ? 'bg-success-lt text-success' : 'bg-azure-lt text-azure'); ?>"><?php echo e($rx->status); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-list justify-content-end flex-nowrap">
                                            <a href="<?php echo e(route('care.module3.prescriptions.pdf', ['prescription' => $rx->id])); ?>" target="_blank" class="btn btn-icon btn-outline-primary btn-sm" title="PDF">
                                                <i class="ti ti-file-text"></i>
                                            </a>
                                            <a href="<?php echo e(route('care.module3.prescriptions.verify', ['token' => $rx->qr_token])); ?>" target="_blank" class="btn btn-icon btn-outline-secondary btn-sm" title="Verifier">
                                                <i class="ti ti-qrcode"></i>
                                            </a>
                                            <form method="POST" action="<?php echo e(route('care.module3.prescriptions.send-email', ['prescription' => $rx->id])); ?>" class="d-inline" title="Envoyer par email">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="email" value="<?php echo e($selectedPatient?->email); ?>">
                                                <button type="submit" class="btn btn-icon btn-outline-success btn-sm">
                                                    <i class="ti ti-mail"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-secondary">
                                        <div class="rx-empty-icon mb-2"><i class="ti ti-file-off"></i></div>
                                        Historique vide.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </aside>
    </div>
</section>
<?php if(in_array($currentSpecialtyCode, ['DENTAL', 'OMNI'])): ?>
<section id="parodontal" class="card paro-card" data-care-tab-panel="clinical">
                <div class="section-head">
                    <h3>Parodontogramme Dynamique</h3>
                    <div class="paro-voice">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="paroToggleSide">Vestibulaire</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="paroMicroBtn">Microphone</button>
                        <button type="button" class="btn btn-sm btn-success" id="paroSaveBtn">Sauvegarder</button>
                    </div>
                </div>

                <div class="paro-layout">
                    <div class="paro-shell">
                        <div class="paro-toolbar">
                            <span class="paro-chip active" data-paro-presets="3">1-3 mm normal</span>
                            <span class="paro-chip" data-paro-presets="4">4 mm alerte</span>
                            <span class="paro-chip" data-paro-presets="5">5+ mm poche profonde</span>
                            <span class="paro-hint">Molette ou boutons +/- sur un point sÃ©lectionnÃ©</span>
                        </div>

                        <div class="paro-chart-wrap">
                            <svg id="paroChart" class="paro-chart" viewBox="0 0 980 310" aria-label="Parodontogramme SVG"></svg>
                        </div>

                        <div class="paro-grid" id="paroTeethGrid"></div>
                    </div>

                    <aside class="paro-panel">
                        <h4>Focus dent / point</h4>
                        <div class="paro-focus">
                            <div><strong id="paroFocusedTooth">Dent 11</strong></div>
                            <div class="paro-hint" id="paroFocusedPoint">Point central</div>
                            <div class="paro-floating">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="paroMinus">-</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="paroPlus">+</button>
                                <button type="button" class="btn btn-outline-dark btn-sm" id="paroNext">Suivant</button>
                            </div>
                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                <button type="button" class="paro-badge" data-flag="bleeding" title="Saignement">S</button>
                                <button type="button" class="paro-badge" data-flag="mobility" title="MobilitÃ©">M</button>
                                <button type="button" class="paro-badge" data-flag="plaque" title="Plaque">P</button>
                            </div>
                            <div class="mt-2">
                                <label class="form-label mb-1">MobilitÃ©</label>
                                <div class="paro-step-buttons" id="paroMobilityButtons">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-mobility="0">0</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-mobility="1">1</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-mobility="2">2</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-mobility="3">3</button>
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="form-label mb-1">Niveau osseux</label>
                                <div class="paro-step-buttons">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="paroBoneMinus">-</button>
                                    <span class="paro-badge on" id="paroBoneValue">0</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="paroBonePlus">+</button>
                                </div>
                            </div>
                        </div>

                        <div class="paro-summary" id="paroSummaryText">Synthese automatique en attente.</div>
                        <div class="paro-hint mt-2" id="paroVoiceFeedback">Aucune commande vocale.</div>
                    </aside>
                </div>

                <input type="hidden" id="paroRecordedOn" value="<?php echo e(now()->toDateString()); ?>">
                <div class="mt-3">
                    <h4 class="mb-2">Historique rÃ©cent</h4>
                    <div class="paro-history" id="paroHistoryList">
                        <?php $__empty_1 = true; $__currentLoopData = $periodontalHistorySeed; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chart): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <article class="paro-history-item">
                                <strong><?php echo e($chart['recorded_on']); ?></strong>
                                <div class="muted small"><?php echo e($chart['summary'] ?: 'Sans synthese'); ?></div>
                            </article>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="text-secondary">Aucun charting parodontal.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <section id="ortho-photos" class="card" data-care-tab-panel="documents">
                <div class="section-head"><h3>Galerie Avant / Apres (Ortho/Esthetique)</h3></div>
                <form method="POST" action="<?php echo e(route('care.module3.ortho-photos.store', ['patientId' => $selectedPatientId])); ?>" enctype="multipart/form-data" class="row g-2">
                    <?php echo csrf_field(); ?>
                    <div class="col-md-4"><label class="form-label">Label cas</label><input class="form-control" name="label" required></div>
                    <div class="col-md-4"><label class="form-label">Photo avant</label><input class="form-control" type="file" name="before_image" accept=".jpg,.jpeg,.png,.webp"></div>
                    <div class="col-md-4"><label class="form-label">Photo apres</label><input class="form-control" type="file" name="after_image" accept=".jpg,.jpeg,.png,.webp"></div>
                    <div class="col-12"><button class="btn btn-primary">Ajouter comparaison</button></div>
                </form>
                <div class="imaging-cards mt-3">
                    <?php $__empty_1 = true; $__currentLoopData = $orthoPhotoSets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $set): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <article class="imaging-card">
                            <div><strong><?php echo e($set->label); ?></strong><div class="muted small"><?php echo e(optional($set->captured_on)->format('d/m/Y')); ?></div></div>
                            <div class="imaging-actions d-flex gap-1">
                                <?php if($set->before_image_path): ?><button type="button" class="btn btn-sm btn-outline-primary btn-preview" data-src="<?php echo e(asset($set->before_image_path)); ?>">Avant</button><?php endif; ?>
                                <?php if($set->after_image_path): ?><button type="button" class="btn btn-sm btn-outline-success btn-preview" data-src="<?php echo e(asset($set->after_image_path)); ?>">Apres</button><?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-secondary">Aucune comparaison photo.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section id="legal-docs" class="card" data-care-tab-panel="documents">
                <div class="section-head"><h3>Coffre-fort Consentements & Questionnaires</h3></div>
                <div class="row g-3">
                    <div class="col-lg-6">
                        <form method="POST" action="<?php echo e(route('care.module3.legal.store', ['patientId' => $selectedPatientId])); ?>" enctype="multipart/form-data" class="row g-2">
                            <?php echo csrf_field(); ?>
                            <div class="col-md-6"><label class="form-label">Type</label><select class="form-select" name="document_type"><option value="consent">Consentement</option><option value="questionnaire">Questionnaire</option><option value="legal">Legal</option></select></div>
                            <div class="col-md-6"><label class="form-label">Titre</label><input class="form-control" name="title" required></div>
                            <div class="col-md-6"><label class="form-label">Date signature</label><input type="date" class="form-control" name="signed_on"></div>
                            <div class="col-md-6"><label class="form-label">Fichier</label><input class="form-control" type="file" name="file"></div>
                            <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" value="1" name="risk_flag"><span class="form-check-label">Pathologie a risque</span></label></div>
                            <div class="col-12"><label class="form-label">Resume risque</label><textarea class="form-control" name="risk_summary" rows="2"></textarea></div>
                            <div class="col-12"><button class="btn btn-primary">Archiver document</button></div>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <form method="POST" action="<?php echo e(route('care.module3.questionnaire.store', ['patientId' => $selectedPatientId])); ?>" class="row g-2">
                            <?php echo csrf_field(); ?>
                            <div class="col-12"><label class="form-label">Questionnaire (question:reponse, 1 ligne)</label><textarea class="form-control" name="answers" rows="4" placeholder="Diabete:oui&#10;Anticoagulants:non" required></textarea></div>
                            <div class="col-12"><label class="form-label">Tags risque</label><input class="form-control" name="risk_tags" placeholder="diabete, anticoagulants"></div>
                            <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" value="1" name="has_critical_risk"><span class="form-check-label">Risque critique detecte</span></label></div>
                            <div class="col-12"><label class="form-label">Notes critiques</label><textarea class="form-control" name="critical_notes" rows="2"></textarea></div>
                            <div class="col-12"><button class="btn btn-warning">Enregistrer questionnaire</button></div>
                        </form>
                    </div>
                </div>
                <div class="mt-3">
                    <?php $__currentLoopData = $healthQuestionnaires; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $qst): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="mb-2 p-2 border rounded <?php echo e($qst->has_critical_risk ? 'border-danger' : 'border-secondary'); ?>">
                            <strong><?php echo e(optional($qst->filled_on)->format('d/m/Y')); ?></strong>
                            <?php if($qst->has_critical_risk): ?><span class="badge bg-red-lt">Risque critique</span><?php endif; ?>
                            <div class="muted small"><?php echo e(implode(', ', $qst->risk_tags ?? [])); ?></div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </section>

            <section id="questionnaires-dynamiques" class="card">
                <div class="section-head">
                    <h3>Questionnaires dynamiques</h3>
                </div>
                <div class="questionnaire-launch-shell">
                    <div class="questionnaire-launch-banner">
                        <div>
                            <strong>Questionnaire patient instantane</strong>
                            <span>Choisissez un modele actif puis ouvrez le formulaire dans une fenetre rapide.</span>
                        </div>
                        <div class="questionnaire-launch-actions">
                            <select class="form-select form-select-sm questionnaire-launch-select" id="questionnaireQuickTemplateSelect">
                                <?php $__empty_1 = true; $__currentLoopData = $questionnaires; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <option value="<?php echo e($template->id); ?>"><?php echo e($template->name); ?> (<?php echo e(count($template->field_schema ?? [])); ?> champs)</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <option value="">Aucun template actif</option>
                                <?php endif; ?>
                            </select>
                            <button type="button" class="btn btn-primary btn-sm" onclick="openQuestionnaireSheet()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                                Ouvrir Questionnaire
                            </button>
                        </div>
                    </div>
                    <div class="small text-secondary">
                        La configuration des templates se fait dans les parametres questionnaires. Ici, vous utilisez uniquement les templates publies pour repondre au dossier du patient.
                    </div>
                </div>
            </section>

            <section id="questionnaires-historique" class="card">
                <div class="section-head"><h3>Historique des reponses questionnaire</h3></div>
                <div class="questionnaire-history-grid">
                    <?php $__empty_1 = true; $__currentLoopData = $questionnaireResponses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $response): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <article class="questionnaire-history-card">
                            <div>
                                <div class="d-flex justify-content-between gap-2 flex-wrap">
                                    <strong><?php echo e($response->questionnaire?->name ?? 'Questionnaire'); ?></strong>
                                    <span class="meta"><?php echo e(optional($response->answered_at)->format('d/m/Y H:i')); ?></span>
                                </div>
                                <div class="meta">Consultation: <?php echo e($response->consultation?->consultation_reason ?: '-'); ?> | Praticien: <?php echo e($response->practitioner?->name ?: '-'); ?></div>
                                <div class="small mt-1"><?php echo e(collect($response->answers ?? [])->take(3)->map(fn ($value, $key) => $key.': '.(is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? 'Oui' : 'Non') : $value)))->implode(' | ')); ?></div>
                                <?php if($response->notes): ?>
                                    <div class="small text-secondary mt-1">Notes: <?php echo e($response->notes); ?></div>
                                <?php endif; ?>
                                <?php if(collect($response->answers ?? [])->count() > 3): ?>
                                    <details class="small mt-1">
                                        <summary>Voir toutes les reponses</summary>
                                        <div class="mt-1"><?php echo e(collect($response->answers ?? [])->map(fn ($value, $key) => $key.': '.(is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? 'Oui' : 'Non') : $value)))->implode(' | ')); ?></div>
                                    </details>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-secondary">Aucune reponse enregistree.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section id="ai-diagnostic" class="card" data-care-tab-panel="clinical">
                <div class="section-head"><h3>IA Aide au Diagnostic (Orthanc-ready)</h3></div>
                <form method="POST" action="<?php echo e(route('care.module3.ai-analysis.store', ['patientId' => $selectedPatientId])); ?>" class="row g-2">
                    <?php echo csrf_field(); ?>
                    <div class="col-md-4"><label class="form-label">Type analyse</label><select class="form-select" name="analysis_type"><option value="caries_detection">Detection caries</option><option value="bone_loss">Perte osseuse</option></select></div>
                    <div class="col-md-4"><label class="form-label">Imaging study ID</label><input class="form-control" name="imaging_study_id" type="number"></div>
                    <div class="col-md-4"><label class="form-label">Provider API</label><input class="form-control" name="provider" value="orthanc_api"></div>
                    <div class="col-12"><button class="btn btn-primary">Lancer analyse IA</button></div>
                </form>
                <form method="POST" action="<?php echo e(route('care.module3.recalls.generate', ['patientId' => $selectedPatientId])); ?>" class="mt-2">
                    <?php echo csrf_field(); ?>
                    <button class="btn btn-outline-success" type="submit">Generer rappels prevention</button>
                </form>
                <div class="table-responsive mt-3">
                    <table class="table table-sm">
                        <thead><tr><th>Date</th><th>Type</th><th>Provider</th><th>Confiance</th><th>Statut</th></tr></thead>
                        <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $aiAnalyses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $analysis): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e(optional($analysis->requested_at)->format('d/m H:i')); ?></td>
                                <td><?php echo e($analysis->analysis_type); ?></td>
                                <td><?php echo e($analysis->provider); ?></td>
                                <td><?php echo e($analysis->confidence !== null ? $analysis->confidence.'%' : '-'); ?></td>
                                <td><span class="badge bg-azure-lt"><?php echo e($analysis->status); ?></span></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="5" class="text-secondary">Aucune analyse IA.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm">
                        <thead><tr><th>Raison recall</th><th>Echeance</th><th>Statut</th><th>Derniere notif</th></tr></thead>
                        <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $patientRecalls; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recall): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($recall->reason); ?></td>
                                <td><?php echo e(optional($recall->due_date)->format('d/m/Y')); ?></td>
                                <td><span class="badge bg-azure-lt"><?php echo e($recall->status); ?></span></td>
                                <td><?php echo e(optional($recall->last_notified_at)->format('d/m/Y') ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="4" class="text-secondary">Aucun rappel prevention genere.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="questionnaireLauncherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Lancer un questionnaire</h5>
                    <div class="small text-secondary">Remplissez uniquement les reponses du template selectionne.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="small text-secondary" id="questionnaireLauncherTemplateName"></div>
                    </div>
                    <div class="col-12">
                        <div id="questionnaireDynamicForm" class="questionnaire-dynamic-form"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div class="small text-secondary" id="questionnaireLauncherMessage"></div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" id="questionnaireSubmitBtn">Enregistrer les reponses</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="lightbox" id="lightbox">
    <button type="button" class="lightbox-close" id="lightboxClose">x</button>
    <img id="lightboxImage" alt="Preview imagerie">
</div>

<div class="modal fade medical-history-modal" id="patientHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Ajouter un antÃ©cÃ©dent / risque</h5>
                    <div class="small text-secondary">Mise Ã  jour instantanÃ©e des badges du dossier.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="patientHistoryForm">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="group" required>
                            <option value="allergies">Allergie</option>
                            <option value="medical_history">AntÃ©cÃ©dent</option>
                            <option value="critical_conditions">Facteur de risque</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">LibellÃ©</label>
                        <input class="form-control" type="text" name="item" placeholder="Ex: Tabac, DiabÃ¨te, Grossesse, PÃ©nicilline" required>
                    </div>
                    <div class="small text-secondary">Les allergies critiques restent en rouge, les risques en orange.</div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div class="small text-secondary" id="patientHistoryModalMessage"></div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="clinicalToastZone" class="clinical-toast-zone" aria-live="polite" aria-atomic="true"></div>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
    const tabTargets = Array.from(document.querySelectorAll('[data-care-tab-target]'));
    const tabPanels = Array.from(document.querySelectorAll('[data-care-tab-panel]'));
    const main = document.querySelector('.clinical-main');
    if (!tabTargets.length || !tabPanels.length || !main) return;

    const storageKey = 'care.module3.activeTab';

    function getTabFromUrl() {
        try {
            return new URL(window.location.href).searchParams.get('tab');
        } catch (_) {
            return null;
        }
    }

    function setActiveTab(tabName, persist = true) {
        const normalizedTab = tabName || 'overview';
        main.classList.add('care-tab-initialized');

        tabTargets.forEach((button) => {
            const isActive = button.dataset.careTabTarget === normalizedTab;
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        tabPanels.forEach((panel) => {
            const isActive = panel.dataset.careTabPanel === normalizedTab;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = ! isActive;
        });

        if (normalizedTab === 'clinical') {
            window.requestAnimationFrame(() => {
                window.dispatchEvent(new Event('resize'));
                window.requestAnimationFrame(() => window.dispatchEvent(new Event('resize')));
            });
        }

        if (persist) {
            window.localStorage.setItem(storageKey, normalizedTab);
            const url = new URL(window.location.href);
            url.searchParams.set('tab', normalizedTab);
            window.history.replaceState({}, '', url);
        }
    }

    const initialTab = getTabFromUrl() || window.localStorage.getItem(storageKey) || '<?php echo e($currentTab); ?>' || 'overview';
    setActiveTab(initialTab, false);

    tabTargets.forEach((button) => {
        button.addEventListener('click', () => setActiveTab(button.dataset.careTabTarget));
    });

    const quickActionButtons = Array.from(document.querySelectorAll('[data-care-action]'));
    const quickActionSelect = document.getElementById('clinicalQuickActionsSelect');
    const actionMap = {
        consultation: { tab: 'care', target: 'consultation-entry' },
        prescription: { tab: 'documents', target: 'prescriptions' },
        quote: { tab: 'documents', target: 'advanced-quote' },
        imaging: { tab: 'documents', target: 'imaging' },
    };

    const scrollToTarget = (targetId) => {
        const target = document.getElementById(targetId);
        if (!target) return;

        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        window.requestAnimationFrame(() => target.classList.add('clinical-section-flash'));
        window.setTimeout(() => target.classList.remove('clinical-section-flash'), 700);
    };

    const runQuickAction = (action) => {
        const config = actionMap[action];
        if (!config) return;

        const tabButton = document.querySelector(`[data-care-tab-target="${config.tab}"]`);
        tabButton?.click();
        window.setTimeout(() => scrollToTarget(config.target), 80);
    };

    quickActionButtons.forEach((button) => {
        button.addEventListener('click', () => runQuickAction(button.dataset.careAction));
    });

    quickActionSelect?.addEventListener('change', () => {
        if (!quickActionSelect.value) return;
        runQuickAction(quickActionSelect.value);
        quickActionSelect.value = '';
    });
})();
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('head'); ?>
<style>
.clinical-shell{display:grid;grid-template-columns:280px 1fr;gap:14px}
.clinical-sidebar{position:sticky;top:12px;height:fit-content;padding:12px}
.clinical-context-card{display:grid;gap:14px}
.sidebar-kicker{font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#64748b}
.sidebar-context-metrics{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
.context-metric{padding:10px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;display:grid;gap:2px}
.context-metric span{font-size:.74rem;color:#64748b;text-transform:uppercase;letter-spacing:.08em}
.context-metric strong{font-size:1rem;color:#0f172a}
.sidebar-alert-list{display:flex;flex-wrap:wrap;gap:6px}
.clinical-tab-rail{display:grid;gap:8px}
.clinical-tab-link{width:100%;border:1px solid #dbeafe;background:#fff;border-radius:12px;padding:10px 12px;text-align:left;font-weight:700;color:#0f172a;transition:all .18s ease}
.clinical-tab-link:hover{border-color:#93c5fd;background:#eff6ff}
.clinical-tab-link.active{border-color:#2563eb;background:linear-gradient(135deg,#dbeafe 0%,#eff6ff 100%);color:#1d4ed8;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
.clinical-main{display:grid;gap:14px}
.clinical-main.care-tab-initialized [data-care-tab-panel]{display:none}
.clinical-main.care-tab-initialized [data-care-tab-panel].is-active{display:block}
.clinical-sticky-header{position:sticky;top:12px;z-index:15;padding:14px;border:1px solid #dbeafe;box-shadow:0 12px 30px rgba(15,23,42,.08);backdrop-filter:blur(8px);background:rgba(255,255,255,.92)}
.sticky-header-top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}
.sticky-patient-name{margin:0;font-size:1.35rem;line-height:1.15}
.sticky-patient-meta{margin-top:4px;color:#64748b;font-size:.92rem}
.sticky-header-kpis{display:flex;gap:10px;flex-wrap:wrap;align-items:stretch}
.sticky-kpi{min-width:132px;padding:10px 12px;border-radius:14px;border:1px solid #dbeafe;background:#f8fbff;display:grid;gap:2px}
.sticky-kpi span{font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b}
.sticky-kpi strong{font-size:.98rem;color:#0f172a}
.sticky-kpi-saldo{background:linear-gradient(180deg,#ecfeff 0%,#f0f9ff 100%)}
.sticky-alert-row{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
.clinical-quick-actions{margin-top:10px;padding-top:10px;border-top:1px solid #dbeafe}
.clinical-quick-actions-desktop{gap:8px;flex-wrap:wrap;align-items:center}
.clinical-quick-action{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;font-weight:700;line-height:1;box-shadow:none}
.clinical-quick-action i{font-size:1rem}
.clinical-quick-actions-mobile select{min-height:40px;border-radius:999px;border-color:#cbd5e1;background:#fff}
.clinical-tabbar{display:flex;gap:8px;flex-wrap:wrap;padding:10px 12px;border:1px solid #dbeafe;background:linear-gradient(180deg,#f8fbff 0%,#eef5ff 100%);position:sticky;top:168px;z-index:14}
.clinical-tab-button{border:1px solid #cbd5e1;background:#fff;border-radius:999px;padding:10px 14px;font-weight:700;color:#334155;transition:all .16s ease}
.clinical-tab-button:hover{border-color:#93c5fd;background:#eff6ff}
.clinical-tab-button.active{border-color:#2563eb;background:#2563eb;color:#fff;box-shadow:0 10px 20px rgba(37,99,235,.18)}
.alert-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:999px;font-size:.8rem;font-weight:700;border:1px solid transparent}
.alert-pill-danger{background:#fef2f2;color:#991b1b;border-color:#fecaca}
.alert-pill-success{background:#ecfdf5;color:#166534;border-color:#bbf7d0}
.soap-context-strip{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:12px;margin:12px 0 14px}
.soap-context-rail,.soap-context-note{padding:12px;border:1px solid #dbeafe;border-radius:14px;background:linear-gradient(180deg,#fbfdff 0%,#f5f9ff 100%)}
.soap-context-title{font-weight:800;color:#0f172a}
.soap-context-copy{margin-top:6px;font-size:.88rem;color:#64748b}
.soap-context-links{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.soap-context-mini-summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:12px}
.soap-context-mini-item{padding:8px 10px;border-radius:12px;border:1px solid #dbeafe;background:#fff;display:grid;gap:2px}
.soap-context-mini-item span{font-size:.74rem;color:#64748b}
.soap-context-mini-item strong{font-size:1rem;color:#0f172a}
.soap-trace-slot{display:grid;gap:10px;margin-top:10px}
.soap-trace-placeholder{min-height:72px;border:1px dashed #93c5fd;border-radius:12px;display:grid;place-items:center;text-align:center;padding:12px;background:#eff6ff;color:#1d4ed8;font-weight:700}
.sidebar-head h3{margin:0;font-size:1rem}
.sidebar-head p{margin:0;color:#64748b;font-size:.82rem}
.sidebar-links{display:grid;gap:6px;margin-top:12px}
.sidebar-links a{padding:8px 10px;border-radius:10px;text-decoration:none;background:#f8fafc;color:#0f172a;font-weight:600}
.sidebar-links a:hover{background:#e2e8f0}
.overview-card{padding:12px}
.patient-header-tech{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-top:10px}
.patient-id{display:flex;gap:10px;align-items:center}
.avatar{width:52px;height:52px;border-radius:999px;background:linear-gradient(135deg,#0ea5e9,#2563eb);color:#fff;display:grid;place-items:center;font-weight:800}
.patient-widget{background:#0f172a;color:#fff;border-radius:12px;padding:10px;min-width:250px}
.widget-title{font-size:.78rem;opacity:.8}
.widget-value{font-size:1rem;font-weight:800}
.widget-sub{font-size:.8rem;opacity:.8}
.allergy-bar{margin-top:10px;padding:10px;border-radius:10px;background:#ecfeff;border:1px solid #a5f3fc}
.allergy-bar.has-alert{background:#fef2f2;border-color:#fca5a5;color:#991b1b}
.overview-priority-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,360px);gap:12px;align-items:start;margin-top:10px}
.overview-priority-main{display:grid;gap:10px}
.overview-priority-side .patient-history-mini{margin-top:0}
.patient-history-mini{margin-top:10px;padding:12px;border:1px solid #dbeafe;background:linear-gradient(180deg,#f8fbff 0%,#f5f9ff 100%);border-radius:14px}
.patient-history-groups{display:grid;gap:10px}
.patient-history-group{display:grid;gap:6px}
.patient-history-label{font-size:.75rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.patient-history-badges{display:flex;flex-wrap:wrap;gap:6px}
.history-badge{display:inline-flex;align-items:center;padding:5px 10px;border-radius:999px;font-size:.82rem;font-weight:700;border:1px solid transparent}
.history-badge-danger{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.history-badge-warning{background:#fffbeb;color:#b45309;border-color:#fcd34d}
.history-badge-pending{opacity:.68;border-style:dashed;animation:historyPulse .85s ease-in-out infinite alternate}
@keyframes historyPulse{from{transform:translateY(0)}to{transform:translateY(-1px)}}
.history-empty{font-size:.86rem;color:#64748b}
.preventive-recall-alert{margin-top:10px;padding:10px 12px;border-radius:12px;border:1px solid #fdba74;background:linear-gradient(180deg,#fff7ed 0%,#ffedd5 100%);color:#9a3412;display:grid;gap:2px}
.archives-card{background:linear-gradient(180deg,#f5f9ff 0%,#eef5ff 100%);border:1px solid #bfdbfe;padding:14px}
.archives-card .section-head{margin-bottom:10px}
.archives-body{display:grid;gap:20px}
.archives-section h4{color:#1d4ed8;font-weight:700;border-bottom:1px solid #dbeafe;padding-bottom:10px}
#archivesToggleBtn i{transition:transform .28s ease}
#archivesContent.show #archivesToggleBtn i,
.collapse.show ~ .archives-card #archivesToggleBtn i{transform:rotate(180deg)}
.patient-nav-footer .directory-head h3::before{content:'ðŸ”„ '}
.patient-directory-card{padding:14px;background:linear-gradient(180deg,#fbfdff 0%,#f5f9ff 100%);border:1px solid #dbeafe}
.directory-head{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:10px}
.omnibar-wrap{display:flex;justify-content:center;margin-bottom:10px}
.omnibar-input{width:min(860px,100%);border:1px solid #bfdbfe;background:#fff;border-radius:14px;padding:12px 16px;outline:none;box-shadow:0 6px 16px rgba(37,99,235,.08);font-size:.95rem}
.omnibar-input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.16)}
.patient-rows{display:grid;gap:8px}
.patient-row{position:relative;display:grid;grid-template-columns:56px 1fr auto;gap:10px;align-items:center;padding:10px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
.patient-row:hover{transform:translateY(-1px);border-color:#93c5fd;box-shadow:0 10px 24px rgba(15,23,42,.08)}
.patient-row.is-selected{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.18)}
.patient-row.patient-pop{animation:patient-pop .28s ease}
@keyframes patient-pop{0%{transform:scale(.97)}100%{transform:scale(1)}}
.patient-avatar{width:44px;height:44px;border-radius:999px;background:linear-gradient(135deg,#0284c7,#1d4ed8);display:grid;place-items:center;color:#fff;font-weight:700}
.patient-main-top{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.patient-name{font-weight:700;color:#0f172a}
.patient-mini{font-size:.8rem;color:#64748b}
.patient-meta{font-size:.8rem;color:#64748b;display:flex;gap:10px;flex-wrap:wrap}
.patient-status{padding:2px 9px;border-radius:999px;font-size:.72rem;font-weight:700}
.status-waiting{background:#dcfce7;color:#166534}
.status-recall{background:#ffedd5;color:#9a3412}
.status-incomplete{background:#fee2e2;color:#991b1b}
.status-normal{background:#e2e8f0;color:#334155}
.patient-quick-actions{display:flex;gap:6px;opacity:0;transform:translateY(3px);transition:opacity .14s ease,transform .14s ease}
.patient-row:hover .patient-quick-actions{opacity:1;transform:translateY(0)}
.patient-skeleton-list{display:grid;gap:8px}
.patient-skeleton-row{height:72px;border-radius:12px;background:linear-gradient(90deg,#e5e7eb 25%,#f3f4f6 37%,#e5e7eb 63%);background-size:400% 100%;animation:skeleton-shimmer 1.1s infinite}
@keyframes skeleton-shimmer{0%{background-position:100% 0}100%{background-position:0 0}}

.patient-slideover{position:fixed;inset:0;display:none;z-index:1300}
.patient-slideover.show{display:block}
.patient-slideover-backdrop{position:absolute;inset:0;background:rgba(2,6,23,.32);backdrop-filter:blur(4px)}
.patient-slideover-panel{position:absolute;top:0;right:0;height:100%;width:min(680px,94vw);background:#fff;border-left:1px solid #dbeafe;box-shadow:-12px 0 26px rgba(15,23,42,.15);padding:14px;overflow:auto;transform:translateX(102%);transition:transform .25s ease}
.patient-slideover.show .patient-slideover-panel{transform:translateX(0)}
.slideover-header{display:flex;justify-content:space-between;align-items:center;gap:8px}
.stepper{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0}
.step-chip{padding:6px 10px;border-radius:999px;border:1px solid #cbd5e1;background:#f8fafc;font-size:.78rem}
.step-chip.active{border-color:#2563eb;background:#dbeafe;color:#1d4ed8;font-weight:700}
.slideover-form{display:grid;gap:10px}
.form-step{display:none}
.form-step.active{display:block;animation:fade-in .18s ease}
@keyframes fade-in{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
.slideover-footer{display:flex;gap:8px;justify-content:flex-end;position:sticky;bottom:0;background:linear-gradient(180deg,rgba(255,255,255,.0),#fff 40%);padding-top:8px}
.photo-capture-box{border:1px dashed #93c5fd;border-radius:12px;padding:10px;background:#f8fbff}
.photo-placeholder{height:140px;border-radius:10px;background:#eff6ff;color:#334155;display:grid;place-items:center;text-align:center;padding:10px;background-size:cover;background-position:center}
.photo-actions{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
#patientWebcam{width:100%;margin-top:8px;border-radius:8px;border:1px solid #cbd5e1}
.soft-alert{border:1px solid #fdba74;background:#fff7ed;color:#9a3412;border-radius:10px;padding:8px}
.section-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}
.toolbar-floating{display:flex;gap:6px;flex-wrap:wrap}

/* Modern Dental Chart Grid */
.chart-grid-modern{display:grid;grid-template-columns:1fr 380px;gap:16px}
.dental-canvas-wrapper{position:relative}
.dental-canvas-modern{height:560px;border-radius:16px;background:radial-gradient(circle at 30% 30%,#1e293b,#020617);box-shadow:inset 0 2px 10px rgba(0,0,0,0.3)}

/* Modern Legend Filter */
.legend-filter-modern{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.legend-chip-modern{border:1px solid #e2e8f0;background:#fff;border-radius:999px;padding:6px 12px;font-size:.82rem;display:inline-flex;gap:8px;align-items:center;transition:all .2s ease;cursor:pointer}
.legend-chip-modern:hover{border-color:#93c5fd;background:#eff6ff}
.legend-chip-modern .legend-dot{width:10px;height:10px;border-radius:999px;display:inline-block}
.legend-chip-modern.active{border-color:#2563eb;background:#eff6ff;box-shadow:0 0 0 3px rgba(37,99,235,.15)}

/* Modern Tooth Panel - Side Drawer Style */
.tooth-panel-modern{
    background:#ffffff;
    border:1px solid #e2e8f0;
    border-radius:16px;
    padding:0;
    box-shadow:0 4px 20px rgba(0,0,0,0.06);
    display:flex;
    flex-direction:column;
    max-height:560px;
    overflow-y:auto;
    overflow-x:hidden;
    scrollbar-gutter:stable;
    transition:all .3s ease;
}
.tooth-panel-modern::-webkit-scrollbar{width:6px}
.tooth-panel-modern::-webkit-scrollbar-track{background:#f1f5f9;border-radius:3px}
.tooth-panel-modern::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px}
.tooth-panel-modern::-webkit-scrollbar-thumb:hover{background:#94a3b8}

/* Tooth Panel Header */
.tooth-panel-header{
    display:flex;
    align-items:center;
    gap:12px;
    padding:16px;
    background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);
    border-bottom:1px solid #e2e8f0;
}
.tooth-badge{
    width:48px;
    height:48px;
    border-radius:12px;
    background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.25rem;
    font-weight:800;
    box-shadow:0 4px 12px rgba(37,99,235,.25);
}
.tooth-info{flex:1;min-width:0}
.tooth-title{margin:0;font-size:1rem;font-weight:700;color:#0f172a}
.tooth-status{margin-top:2px;font-size:.82rem;color:#64748b}
.tooth-panel-close{
    width:32px;
    height:32px;
    border:none;
    background:#fff;
    border-radius:8px;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#64748b;
    transition:all .2s ease;
}
.tooth-panel-close:hover{background:#fee2e2;color:#dc2626}

/* Quick Actions */
.tooth-quick-actions{
    display:flex;
    gap:8px;
    padding:12px 16px;
    border-bottom:1px solid #e2e8f0;
    background:#fff;
}
.quick-action-btn{
    flex:1;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    padding:10px 12px;
    border:1px solid #e2e8f0;
    background:#fff;
    border-radius:10px;
    font-size:.85rem;
    font-weight:600;
    color:#0f172a;
    cursor:pointer;
    transition:all .2s ease;
}
.quick-action-btn:hover:not(:disabled){border-color:#3b82f6;background:#eff6ff;color:#2563eb}
.quick-action-btn:disabled{opacity:.5;cursor:not-allowed}
.quick-action-btn.secondary{background:#f8fafc}

/* Procedure Form Container */
.procedure-form-container{
    padding:16px;
    background:#fff;
    border-bottom:1px solid #e2e8f0;
    animation:slideDown .3s ease;
}
@keyframes slideDown{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
.procedure-form-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.procedure-form-title{margin:0;font-size:.95rem;font-weight:700;color:#0f172a}
.procedure-form-close{
    width:28px;
    height:28px;
    border:none;
    background:#f1f5f9;
    border-radius:6px;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#64748b;
    transition:all .2s ease;
}
.procedure-form-close:hover{background:#fee2e2;color:#dc2626}

/* Modern Form Styles */
.procedure-form{display:flex;flex-direction:column;gap:12px}
.form-group-modern{display:flex;flex-direction:column;gap:4px}
.form-row-modern{display:flex;gap:12px}
.form-row-modern .flex-1{flex:1}
.form-label-modern{
    display:flex;
    align-items:center;
    gap:6px;
    font-size:.82rem;
    font-weight:600;
    color:#475569;
}
.form-label-modern .required{color:#dc2626}
.form-select-modern,.form-input-modern,.form-textarea-modern{
    width:100%;
    padding:10px 12px;
    border:1px solid #e2e8f0;
    border-radius:10px;
    font-size:.9rem;
    color:#0f172a;
    background:#fff;
    transition:all .2s ease;
}
.form-select-modern:focus,.form-input-modern:focus,.form-textarea-modern:focus{
    outline:none;
    border-color:#3b82f6;
    box-shadow:0 0 0 3px rgba(59,130,246,.15);
}
.form-textarea-modern{resize:vertical;min-height:60px}
.form-hint{font-size:.75rem;color:#94a3b8;margin-top:2px}

/* Consultation Selector */
.consultation-selector{display:flex;gap:8px;align-items:center}
.consultation-selector .form-select-modern{flex:1}
.btn-add-consultation{
    width:40px;
    height:40px;
    border:1px solid #e2e8f0;
    background:#fff;
    border-radius:10px;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#2563eb;
    transition:all .2s ease;
}
.btn-add-consultation:hover{background:#eff6ff;border-color:#3b82f6}

/* Autocomplete Wrapper */
.autocomplete-wrapper{position:relative}
.autocomplete-dropdown{
    position:absolute;
    top:100%;
    left:0;
    right:0;
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:10px;
    box-shadow:0 10px 25px rgba(0,0,0,.1);
    max-height:200px;
    overflow-y:auto;
    z-index:100;
    display:none;
    margin-top:4px;
}
.autocomplete-dropdown.show{display:block}
.autocomplete-item{
    padding:10px 12px;
    cursor:pointer;
    transition:background .15s ease;
    border-bottom:1px solid #f1f5f9;
}
.autocomplete-item:last-child{border-bottom:none}
.autocomplete-item:hover,.autocomplete-item.selected{background:#eff6ff}
.autocomplete-item-code{font-size:.75rem;font-weight:700;color:#2563eb}
.autocomplete-item-name{font-size:.88rem;color:#0f172a}
.autocomplete-item-category{font-size:.75rem;color:#64748b}

/* Price Input */
.price-input-wrapper{position:relative;display:flex;align-items:center}
.price-input{padding-right:50px}
.price-currency{
    position:absolute;
    right:12px;
    font-size:.82rem;
    font-weight:600;
    color:#64748b;
}

/* Submit Button */
.btn-submit-procedure{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:12px;
    background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);
    color:#fff;
    border:none;
    border-radius:10px;
    font-size:.9rem;
    font-weight:700;
    cursor:pointer;
    transition:all .2s ease;
    box-shadow:0 4px 12px rgba(37,99,235,.25);
}
.btn-submit-procedure:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(37,99,235,.3)}
.btn-submit-procedure:active{transform:translateY(0)}
.btn-submit-procedure:disabled{opacity:.6;cursor:not-allowed;transform:none}

/* Tooth History Section */
.tooth-history-section{padding:16px;flex:1;overflow-y:auto}
.section-title-modern{
    display:flex;
    align-items:center;
    gap:6px;
    margin:0 0 12px 0;
    font-size:.85rem;
    font-weight:700;
    color:#475569;
}
.procedure-list-modern{display:flex;flex-direction:column;gap:8px}
.procedure-item-modern{
    padding:10px 12px;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:10px;
    transition:all .2s ease;
}
.procedure-item-modern:hover{border-color:#93c5fd;background:#eff6ff}
.procedure-item-header{display:flex;justify-content:space-between;align-items:center;gap:8px}
.procedure-item-name{font-size:.88rem;font-weight:600;color:#0f172a}
.procedure-item-badge{
    padding:2px 8px;
    border-radius:999px;
    font-size:.72rem;
    font-weight:600;
}
.procedure-item-badge.completed{background:#dcfce7;color:#166534}
.procedure-item-badge.in_progress{background:#fef3c7;color:#92400e}
.procedure-item-badge.planned{background:#dbeafe;color:#1e40af}
.procedure-item-badge.cancelled{background:#fee2e2;color:#991b1b}
.procedure-item-meta{display:flex;gap:12px;margin-top:6px;font-size:.78rem;color:#64748b}
.procedure-item-meta span{display:flex;align-items:center;gap:4px}

/* Empty State */
.empty-state{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:24px;
    text-align:center;
    color:#94a3b8;
}
.empty-state svg{margin-bottom:8px;opacity:.5}
.empty-state p{margin:0;font-size:.85rem}
.empty-state.small{padding:12px}
.empty-state.small p{font-size:.8rem}

/* Multi Selection Section */
.multi-selection-section{padding:16px;border-top:1px solid #e2e8f0}
.multi-selection-chips{display:flex;flex-wrap:wrap;gap:6px}
.multi-chip{
    padding:4px 10px;
    background:#eff6ff;
    border:1px solid #bfdbfe;
    border-radius:999px;
    font-size:.8rem;
    font-weight:600;
    color:#2563eb;
}

/* Annotations Section */
.annotations-section{padding:16px;border-top:1px solid #e2e8f0}
.annotation-list-modern{display:flex;flex-direction:column;gap:6px}
.annotation-item-modern{
    padding:8px 10px;
    background:#fffbeb;
    border:1px solid #fde68a;
    border-radius:8px;
    font-size:.82rem;
    color:#92400e;
}

/* Responsive */
@media (max-width: 1400px){
    .chart-grid-modern{grid-template-columns:1fr 340px}
}
@media (max-width: 1200px){
    .clinical-shell{grid-template-columns:1fr}
    .clinical-sidebar{position:relative;top:0}
    .chart-grid-modern{grid-template-columns:1fr}
    .tooth-panel-modern{max-height:calc(100vh - 180px)}
    .patient-header-tech{flex-direction:column;align-items:flex-start}
    .patient-widget{width:100%}
}
@media (max-width: 768px){
    .chart-grid-modern{grid-template-columns:1fr}
    .dental-canvas-modern{height:360px}
    .tooth-panel-modern{
        max-height:none;
        overflow:visible;
        order:-1;
        margin-bottom:16px;
    }
    .tooth-quick-actions{flex-direction:column}
    .quick-action-btn{width:100%}
    .procedure-form-container{max-height:none}
    .tooth-history-section,
    .annotations-section,
    .multi-selection-section{padding:0 16px 16px}
}
.consultation-flash{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;padding:10px;border-radius:12px;background:#fff7ed;border:1px solid #fdba74;margin:10px 0}
.rich-editor-toolbar{display:flex;gap:8px;flex-wrap:wrap}
.rich-editor{min-height:150px;border:1px solid #cbd5e1;border-radius:12px;background:#fff;padding:12px;line-height:1.55}
.rich-editor:empty:before{content:'Saisir les observations cliniques...';color:#94a3b8}
.legend-filter{display:flex;gap:6px;flex-wrap:wrap}
.legend-chip{border:1px solid #cbd5e1;background:#fff;border-radius:999px;padding:5px 10px;font-size:.8rem;display:inline-flex;gap:6px;align-items:center}
.legend-chip span{width:10px;height:10px;border-radius:999px;display:inline-block}
.legend-chip.active{border-color:#2563eb;box-shadow:0 0 0 2px #bfdbfe}
.annotation-list,.procedure-mini-list{display:grid;gap:6px;max-height:200px;overflow:auto}
.annotation-item,.procedure-mini-item{padding:8px;border:1px solid #e2e8f0;border-radius:8px;background:#fff}
.timeline-vertical{position:relative;display:grid;gap:8px}
.timeline-item{display:grid;grid-template-columns:14px 1fr;gap:8px}
.timeline-dot{width:10px;height:10px;border-radius:999px;background:#2563eb;margin-top:8px}
.timeline-content{border-left:2px solid #e2e8f0;padding-left:10px}
.timeline-content .meta{font-size:.83rem;color:#64748b}
.upload-box{padding:10px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc}
.upload-dropzone{border:2px dashed #93c5fd;border-radius:12px;padding:20px;text-align:center;cursor:pointer;background:#eff6ff}
.upload-dropzone.dragover{border-color:#2563eb;background:#dbeafe}
.upload-icon{font-size:1.8rem;font-weight:800}
.imaging-cards{display:grid;gap:8px}
.imaging-card{display:flex;justify-content:space-between;gap:8px;align-items:center;padding:10px;border:1px solid #e2e8f0;border-radius:10px}
.treatment-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:10px}
.treatment-card{padding:10px;border:1px solid #e2e8f0;border-radius:10px;background:#fff}
.treatment-card header{display:flex;justify-content:space-between;gap:8px;align-items:center}
.money-row{display:grid;gap:3px;margin-top:8px;font-size:.86rem}
.phase-list{margin:8px 0 0 0;padding:0;list-style:none;display:grid;gap:6px}
.phase-list li{display:flex;justify-content:space-between;border:1px dashed #cbd5e1;border-radius:8px;padding:6px}
.lightbox{position:fixed;inset:0;background:rgba(2,6,23,.86);display:none;align-items:center;justify-content:center;z-index:1100}
.lightbox.show{display:flex}
.lightbox img{max-width:90vw;max-height:86vh;border-radius:12px}
.lightbox-close{position:absolute;top:16px;right:16px;border:0;background:#fff;border-radius:999px;width:34px;height:34px;font-weight:700}
.medical-history-modal .modal-content{border-radius:18px;border:1px solid #dbeafe}
.medical-history-modal .modal-header{background:linear-gradient(180deg,#f8fbff 0%,#eef5ff 100%)}

/* Consultation Modal Styles */
#consultationModal .modal-content{
    border-radius:20px;
    border:none;
    box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);
    overflow:hidden;
}
#consultationModal .modal-header{
    background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);
    padding:20px 24px;
}
#consultationModal .modal-body{
    padding:0;
    max-height:calc(100vh - 200px);
    overflow-y:auto;
}
#consultationModal .modal-body::-webkit-scrollbar{
    width:8px;
}
#consultationModal .modal-body::-webkit-scrollbar-track{
    background:#f1f5f9;
}
#consultationModal .modal-body::-webkit-scrollbar-thumb{
    background:#cbd5e1;
    border-radius:4px;
}
#consultationModal .modal-body::-webkit-scrollbar-thumb:hover{
    background:#94a3b8;
}
#consultationModal .modal-body .p-4{
    padding:24px !important;
}
#consultationModal .modal-footer{
    background:linear-gradient(180deg,#f8fafc 0%,#f1f5f9 100%);
    padding:16px 24px;
    border-top:1px solid #e2e8f0;
}
#consultationModal .form-label{
    font-size:0.875rem;
    margin-bottom:0.375rem;
}
#consultationModal .form-control,
#consultationModal .form-select{
    border-radius:10px;
    border:1px solid #e2e8f0;
    padding:0.625rem 0.875rem;
    font-size:0.9rem;
    transition:all 0.2s ease;
}
#consultationModal .form-control:focus,
#consultationModal .form-select:focus{
    border-color:#3b82f6;
    box-shadow:0 0 0 3px rgba(59,130,246,0.1);
}
#consultationModal .btn-primary{
    background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);
    border:none;
    border-radius:10px;
    padding:0.625rem 1.5rem;
    font-weight:600;
    transition:all 0.2s ease;
}
#consultationModal .btn-primary:hover{
    transform:translateY(-1px);
    box-shadow:0 4px 12px rgba(37,99,235,0.3);
}
#consultationModal .btn-outline-secondary{
    border-radius:10px;
    padding:0.625rem 1.5rem;
    font-weight:600;
}

/* Modal Backdrop Blur */
#consultationModal.show .modal-backdrop{
    backdrop-filter:blur(4px);
    -webkit-backdrop-filter:blur(4px);
}
.modal-backdrop{
    background-color:rgba(0,0,0,0.5);
}
.timeline-actions{display:flex;gap:6px;flex-wrap:wrap}
#medical-history-complete{background:linear-gradient(180deg,#fbfdff 0%,#f8fafc 100%);border:1px solid #dbeafe}
#medical-history-complete .accordion-button{font-weight:700}
#medical-history-complete .accordion-body{background:#fff}
.history-refresh-flash{animation:historyFlash .65s ease}
@keyframes historyFlash{0%{background:#fff7ed}100%{background:transparent}}

/* Section colors to improve visual distinction */
#chart3d{background:linear-gradient(180deg,#f8fbff 0%,#eef5ff 100%);border:1px solid #bfdbfe}
#timeline{background:linear-gradient(180deg,#f8fffb 0%,#eefcf5 100%);border:1px solid #86efac}
#imaging{background:linear-gradient(180deg,#fffcf5 0%,#fff7e7 100%);border:1px solid #fcd34d}
#treatment{background:linear-gradient(180deg,#fff8fb 0%,#ffeef4 100%);border:1px solid #f9a8d4}
#advanced-quote{background:linear-gradient(180deg,#f7f9ff 0%,#eef2ff 100%);border:1px solid #c7d2fe}
#prescriptions{background:linear-gradient(180deg,#f5fffd 0%,#e6f6ff 100%);border:1px solid #bfdbfe}
.rx-head-badge{font-size:.78rem;padding:.45rem .75rem}
.rx-split-view{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(420px,.95fr);gap:16px;align-items:start}
.rx-editor-panel,.rx-preview-panel{display:grid;gap:12px}
.rx-editor-panel{padding:16px;border:1px solid #dbeafe;border-radius:20px;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%)}
.rx-preview-panel{padding:16px;border:1px solid #dbeafe;border-radius:20px;background:#fff;position:sticky;top:12px}
.rx-security-banner{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:16px;border:1px solid #dbeafe}
.rx-security-banner.is-safe{background:#effaf5;border-color:#bbf7d0;color:#166534}
.rx-security-banner.is-risk{background:#fff5f5;border-color:#fecaca;color:#991b1b}
.rx-security-icon{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;font-size:1.15rem;flex:0 0 auto;background:rgba(255,255,255,.8)}
.rx-card-block{padding:16px;border:1px solid #dbeafe;border-radius:18px;background:#fff;box-shadow:0 12px 28px rgba(15,23,42,.04)}
.rx-block-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:12px}
.rx-block-head h4{margin:0;font-size:1rem;font-weight:800;color:#0f172a}
.rx-block-kicker{font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#3b82f6}
.rx-search-wrap{position:relative}
.rx-search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none}
.rx-search-input{padding-left:38px;border-radius:14px;border-color:#bfdbfe}
.rx-search-results{position:absolute;left:0;right:0;top:calc(100% + 8px);background:#fff;border:1px solid #dbeafe;border-radius:14px;box-shadow:0 20px 34px rgba(15,23,42,.16);overflow:hidden;z-index:30;max-height:340px;overflow-y:auto}
.rx-search-item{width:100%;display:flex;flex-direction:column;align-items:flex-start;gap:2px;padding:12px 14px;border:0;border-bottom:1px solid #eef2f7;background:#fff;text-align:left}
.rx-search-item:last-child{border-bottom:0}
.rx-search-item:hover,.rx-search-item:focus{background:#eff6ff}
.rx-search-item-title{font-size:.92rem;font-weight:700;color:#0f172a}
.rx-search-item-meta{font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;color:#64748b}
.rx-template-note{padding:12px 14px;border-radius:14px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:.88rem}
.rx-items-list{display:grid;gap:12px;min-height:110px}
.rx-item-card{padding:14px;border:1px solid #dbeafe;border-radius:18px;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);box-shadow:0 10px 22px rgba(15,23,42,.05)}
.rx-item-header{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;margin-bottom:12px}
.rx-item-title{display:grid;gap:4px}
.rx-item-title strong{font-size:1rem}
.rx-item-meta{font-size:.78rem;color:#64748b}
.rx-drag-handle,.rx-remove-btn{width:32px;height:32px;border-radius:10px;display:grid;place-items:center}
.rx-item-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;align-items:end}
.rx-mini-label{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#64748b}
.rx-item-grid .form-control,.rx-item-grid .form-select{border-radius:12px}
.rx-item-secondary{margin-top:10px;display:grid;grid-template-columns:1fr 180px;gap:10px;align-items:end}
.rx-signature-grid{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,.8fr);gap:12px}
.rx-signature-shell{padding:10px;border:1px dashed #93c5fd;border-radius:16px;background:#f8fafc}
#rxSignaturePad{display:block;width:100%;height:160px}
.rx-preview-toolbar,.rx-pdf-viewer-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}
.rx-paper-sheet{padding:18px;border-radius:22px;border:1px solid #dbeafe;background:#fff;box-shadow:0 18px 40px rgba(15,23,42,.08)}
.rx-paper-header{display:grid;grid-template-columns:auto 1fr auto;gap:14px;align-items:center;padding-bottom:14px;border-bottom:1px solid #e2e8f0}
.rx-logo-mark{width:52px;height:52px;border-radius:18px;display:grid;place-items:center;background:linear-gradient(135deg,#2563eb,#60a5fa);color:#fff;font-weight:900;letter-spacing:.08em;box-shadow:0 14px 24px rgba(37,99,235,.24)}
.rx-paper-cabinet{font-weight:900;font-size:1rem;color:#0f172a}
.rx-paper-sub{font-size:.82rem;color:#64748b}
.rx-paper-meta{display:grid;gap:4px;font-size:.8rem;color:#334155;text-align:right}
.rx-paper-patient{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:14px 0;border-bottom:1px dashed #dbeafe}
.rx-paper-label{display:block;font-size:.7rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#3b82f6}
.rx-paper-name{font-size:1.15rem;font-weight:900;color:#0f172a}
.rx-paper-patient-meta{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.rx-paper-patient-meta span{padding:7px 10px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-size:.8rem;font-weight:800}
.rx-paper-section{padding-top:14px}
.rx-paper-section-title{font-size:.78rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:#64748b;margin-bottom:10px}
.rx-paper-items{display:grid;gap:10px}
.rx-paper-item{padding:12px 14px;border:1px solid #dbeafe;border-radius:16px;background:#f8fbff;display:grid;gap:6px}
.rx-paper-item-head{display:flex;justify-content:space-between;gap:8px;align-items:flex-start}
.rx-paper-item-name{font-weight:900;color:#0f172a}
.rx-paper-item-badges{display:flex;gap:6px;flex-wrap:wrap}
.rx-paper-badge{padding:4px 8px;border-radius:999px;background:#dbeafe;color:#1d4ed8;font-size:.72rem;font-weight:800}
.rx-paper-item-line{font-size:.88rem;color:#334155}
.rx-paper-footer{display:flex;justify-content:space-between;gap:12px;align-items:flex-end;margin-top:14px;padding-top:14px;border-top:1px solid #e2e8f0}
.rx-paper-footer-copy{font-size:.8rem;color:#64748b;max-width:62%}
.rx-paper-signature{display:grid;gap:4px;min-width:180px;text-align:right}
.rx-paper-signature-line{height:36px;border-bottom:1px dashed #94a3b8}
.rx-pdf-viewer-shell{padding:14px;border:1px solid #dbeafe;border-radius:18px;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%)}
.rx-pdf-canvas{display:block;width:100%;min-height:280px;border-radius:14px;background:#fff;border:1px solid #e2e8f0}
.rx-history-shell{padding:16px;border:1px solid #dbeafe;border-radius:18px;background:#fff}
.rx-history-table thead th{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#64748b;border-bottom-color:#e2e8f0}
.rx-empty-state{padding:28px 16px;border:1px dashed #cbd5e1;border-radius:14px;background:#f8fafc;text-align:center;color:#64748b}
.rx-empty-state-compact{padding:18px 14px}
.rx-empty-icon{width:42px;height:42px;margin:0 auto 10px;border-radius:999px;background:#fff;border:1px solid #e2e8f0;display:grid;place-items:center;font-size:1.1rem;color:#94a3b8}
.rx-item-actions{display:flex;justify-content:flex-end;gap:8px}
@media (max-width: 1200px){.rx-split-view{grid-template-columns:1fr}.rx-preview-panel{position:static}.rx-signature-grid{grid-template-columns:1fr}.rx-item-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.rx-item-secondary{grid-template-columns:1fr}}
@media (max-width: 768px){.rx-paper-header{grid-template-columns:1fr}.rx-paper-meta{text-align:left}.rx-paper-patient{flex-direction:column;align-items:flex-start}.rx-paper-footer{flex-direction:column;align-items:flex-start}.rx-paper-footer-copy{max-width:none}.rx-item-grid{grid-template-columns:1fr}}
#parodontal{background:linear-gradient(180deg,#f7fffb 0%,#ecfff6 100%);border:1px solid #6ee7b7}
#ortho-photos{background:linear-gradient(180deg,#fffaf6 0%,#fff2e8 100%);border:1px solid #fdba74}
#legal-docs{background:linear-gradient(180deg,#fff7f7 0%,#ffefef 100%);border:1px solid #fca5a5}
#ai-diagnostic{background:linear-gradient(180deg,#f6faff 0%,#eaf3ff 100%);border:1px solid #93c5fd}

#chart3d .section-head h3{color:#1d4ed8}
#timeline .section-head h3{color:#15803d}
#imaging .section-head h3{color:#b45309}
#treatment .section-head h3{color:#be185d}
#advanced-quote .section-head h3{color:#3730a3}
#prescriptions .section-head h3{color:#0f766e}
#parodontal .section-head h3{color:#047857}
#ortho-photos .section-head h3{color:#c2410c}
#legal-docs .section-head h3{color:#b91c1c}
#ai-diagnostic .section-head h3{color:#1e40af}
.paro-card{padding:14px;background:linear-gradient(180deg,#f8fffb 0%,#eefdf6 100%);border:1px solid #86efac}
.paro-layout{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:12px;align-items:start}
.paro-shell{display:grid;gap:10px;min-height:400px}
.paro-toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.paro-chip{padding:6px 10px;border-radius:999px;border:1px solid #cbd5e1;background:#fff;font-size:.78rem;font-weight:700;cursor:pointer}
.paro-chip.active{border-color:#16a34a;background:#dcfce7;color:#166534}
.paro-chart-wrap{overflow:auto;border:1px solid #dbeafe;border-radius:12px;background:#fff}
.paro-chart{min-width:980px;width:100%;height:310px;display:block;background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%)}
.paro-grid{display:grid;grid-template-columns:repeat(8,minmax(110px,1fr));gap:8px}
.paro-tooth{border:1px solid #dbeafe;border-radius:12px;background:#fff;padding:8px;box-shadow:0 6px 16px rgba(15,23,42,.05);transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease}
.paro-tooth:hover{transform:translateY(-1px);border-color:#86efac;box-shadow:0 10px 18px rgba(15,23,42,.08)}
.paro-tooth.is-active{border-color:#16a34a;box-shadow:0 0 0 3px rgba(34,197,94,.18)}
.paro-tooth.has-procedure{border-color:#f59e0b;box-shadow:0 10px 24px rgba(245,158,11,.08)}
.paro-tooth .paro-procedure-dot{display:inline-block;width:12px;height:12px;border-radius:50%;background:#f59e0b;margin-left:6px;box-shadow:0 1px 4px rgba(0,0,0,.12)}
.paro-badge-danger{background:#dc2626;color:#fff}
.paro-tooth-num{display:flex;justify-content:space-between;align-items:center;font-weight:800;font-size:.9rem;margin-bottom:6px}
.paro-tooth-badges{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:6px}
.paro-badge{width:22px;height:22px;border-radius:999px;display:grid;place-items:center;font-size:.68rem;font-weight:800;background:#e2e8f0;color:#334155;cursor:pointer;user-select:none}
.paro-badge.on{background:#ef4444;color:#fff}
.paro-badge.mobility-1,.paro-badge.mobility-2,.paro-badge.mobility-3{background:#f59e0b;color:#fff}
.tooth-flag-row{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
.tooth-flag-chip{display:inline-flex;align-items:center;justify-content:center;padding:3px 8px;border-radius:999px;font-size:.68rem;font-weight:700;line-height:1.2}
.tooth-flag-chip-procedure{background:#fef3c7;color:#92400e}
.tooth-flag-chip-danger{background:#fee2e2;color:#991b1b}
.tooth-flag-chip-warning{background:#ffedd5;color:#9a3412}
.paro-points{display:grid;grid-template-columns:repeat(3,1fr);gap:4px}
.paro-point{padding:6px 0;border-radius:10px;border:1px solid #cbd5e1;background:#f8fafc;font-size:.78rem;text-align:center;cursor:pointer;position:relative}
.paro-point.active{border-color:#2563eb;background:#dbeafe}
.paro-point .value{display:block;font-size:1rem;font-weight:800;line-height:1.1}
.paro-point .label{display:block;font-size:.65rem;color:#64748b}
.paro-panel{border:1px solid #dbeafe;background:#f8fbff;border-radius:12px;padding:12px;position:sticky;top:12px}
.paro-panel h4{margin:0 0 8px 0}
.paro-focus{padding:10px;border-radius:10px;background:#fff;border:1px solid #dbeafe}
.paro-step-buttons{display:flex;gap:8px;flex-wrap:wrap}
.paro-floating{display:flex;gap:8px;align-items:center;margin-top:8px}
.paro-floating .btn{min-width:42px}
.paro-summary{margin-top:10px;padding:10px;border-radius:10px;border:1px solid #86efac;background:#ecfdf5;color:#14532d;font-size:.9rem}
.paro-history{display:grid;gap:8px}
.paro-history-item{padding:10px;border-radius:10px;border:1px solid #dbeafe;background:#fff}
.paro-history-item strong{display:block;margin-bottom:2px}
.paro-hint{font-size:.8rem;color:#64748b}
.paro-voice{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.paro-voice .recording{background:#fee2e2;color:#991b1b;border-color:#fca5a5}
.questionnaire-builder{display:grid;gap:10px}
.questionnaire-field{border:1px solid #dbeafe;border-radius:12px;background:#f8fbff;padding:10px}
.questionnaire-field-grid{display:grid;grid-template-columns:1.3fr 1fr 1fr auto;gap:8px;align-items:end}
.questionnaire-field-options{display:grid;gap:6px;margin-top:8px}
.questionnaire-option-row{display:grid;grid-template-columns:1fr auto;gap:6px}
.questionnaire-template-item{padding:10px;border-radius:12px;border:1px solid #e2e8f0;background:#fff}
.questionnaire-dynamic-form{display:grid;gap:12px;padding:10px;border:1px solid #dbeafe;border-radius:12px;background:linear-gradient(180deg,#f8fbff 0%,#eef5ff 100%)}
.questionnaire-dynamic-field{padding:10px;border:1px solid #e2e8f0;border-radius:12px;background:#fff}
.questionnaire-dynamic-field .field-label{display:flex;justify-content:space-between;gap:8px;align-items:center;font-weight:700;margin-bottom:6px}
.questionnaire-help{font-size:.8rem;color:#64748b}
.questionnaire-choice-group{display:grid;gap:8px}
.questionnaire-choice{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #dbeafe;border-radius:10px;background:#f8fbff}
.questionnaire-choice .form-check-input{margin-top:0}
.questionnaire-yesno-group{grid-template-columns:repeat(2,minmax(0,1fr))}
.questionnaire-launch-shell{display:grid;gap:14px}
.questionnaire-launch-banner{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:12px 14px;border:1px solid #c7d2fe;border-radius:14px;background:linear-gradient(135deg,#eff6ff 0%,#eef2ff 100%)}
.questionnaire-launch-banner strong{display:block;font-size:1rem;color:#1e3a8a}
.questionnaire-launch-banner span{font-size:.85rem;color:#475569}
.questionnaire-launch-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.questionnaire-launch-select{min-width:280px;border-radius:10px}
.questionnaire-history-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:10px}
.questionnaire-history-card{border:1px solid #e2e8f0;border-radius:12px;background:#fff;padding:10px}
@media (max-width: 768px){.questionnaire-yesno-group{grid-template-columns:1fr}.questionnaire-launch-banner{align-items:flex-start;flex-direction:column}.questionnaire-launch-actions,.questionnaire-launch-select{width:100%}}
.clinical-toast-zone{position:fixed;right:16px;bottom:16px;display:grid;gap:8px;z-index:2200;max-width:min(92vw,380px)}
.clinical-toast{background:#0f172a;color:#f8fafc;border:1px solid rgba(148,163,184,.45);border-radius:10px;padding:10px 12px;box-shadow:0 12px 30px rgba(2,6,23,.28);opacity:0;transform:translateY(10px);transition:opacity .2s ease,transform .2s ease;font-size:.86rem}
.clinical-toast.show{opacity:1;transform:translateY(0)}
.clinical-toast.success{border-color:rgba(34,197,94,.42)}
.clinical-toast.error{border-color:rgba(239,68,68,.52)}
.clinical-section-flash{animation:careSectionFlash .7s ease}
@keyframes careSectionFlash{0%{box-shadow:0 0 0 0 rgba(37,99,235,.0);background-color:inherit}35%{box-shadow:0 0 0 4px rgba(37,99,235,.12);background-color:#eff6ff}100%{box-shadow:0 0 0 0 rgba(37,99,235,.0);background-color:inherit}}
@media (max-width: 1200px){.clinical-shell{grid-template-columns:1fr}.clinical-sidebar{position:relative;top:0}.chart-grid{grid-template-columns:1fr}.patient-header-tech{flex-direction:column;align-items:flex-start}.patient-widget{width:100%}}
@media (max-width: 1200px){.overview-priority-layout{grid-template-columns:1fr}.overview-priority-side{order:-1}.overview-priority-side .patient-history-mini{margin-top:0}}
@media (max-width: 767.98px){.clinical-tabbar{top:132px}.clinical-sticky-header{top:8px}.clinical-quick-actions{padding-top:12px}.clinical-quick-actions-mobile select{width:100%}}
.inline-editable-card{transition:all .18s ease}
.inline-editable-card:hover{border-color:#93c5fd;background:rgba(219,245,254,.4)}
.inline-add-btn{margin:0;opacity:.6;transition:opacity .18s ease}
.inline-editable-card:hover .inline-add-btn{opacity:1}
.inline-placeholder{cursor:pointer;display:flex;align-items:center;gap:4px}
.inline-placeholder a{font-weight:600;color:#2563eb;text-decoration:none}
.inline-placeholder a:hover{text-decoration:underline}
.inline-edit-trigger{cursor:pointer;color:#2563eb;font-weight:600;text-decoration:none}
.inline-edit-trigger:hover{text-decoration:underline}
.history-items-list{display:flex;flex-wrap:wrap;gap:8px}
.history-item{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:16px;background:#f0f9ff;border:1px solid #bfdbfe;font-size:.85rem;animation:slideInRight .2s ease}
@keyframes slideInRight{from{opacity:0;transform:translateX(-10px)}to{opacity:1;transform:translateX(0)}}
.history-item-tag .btn-close-inline{background:none;border:none;cursor:pointer;padding:0;font-size:.7rem;opacity:.6;transition:opacity .18s ease}
.history-item-tag .btn-close-inline:hover{opacity:1;color:#dc2626}
.inline-input{width:100%;border:1px solid #93c5fd;box-shadow:0 0 0 2px rgba(37,99,235,.1)}
.inline-input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.2);outline:none}
.suggestions-list{position:absolute;z-index:50;background:#fff;border:1px solid #dbeafe;border-radius:8px;max-height:200px;overflow-y:auto;box-shadow:0 10px 20px rgba(15,23,42,.1);min-width:280px}
.suggestions-list .suggestion-item{padding:8px 12px;cursor:pointer;font-size:.85rem}
.suggestions-list .suggestion-item:hover{background:#eff6ff;color:#2563eb}
.suggestion-item-freq{font-size:.72rem;color:#94a3b8;margin-left:auto}
.inline-input.success{border-color:#16a34a;background-color:#dcfce7}
.inline-input.error{border-color:#dc2626;background-color:#fee2e2}
.consultation-timeline{display:grid;gap:8px}
.consultation-group{border:1px solid #dbeafe;border-radius:12px;background:#fff;overflow:hidden}
.consultation-group[open]{background:#f8fbff}
.consultation-group-header{padding:12px;cursor:pointer;border-radius:12px;transition:background .15s;display:grid;gap:4px}
.consultation-group-header:hover{background:#eff6ff}
.consultation-group-meta{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.consultation-date{font-weight:700;color:#0f172a;font-size:.95rem}
.consultation-type{font-size:.75rem}
.consultation-practitioner{display:inline-flex;align-items:center;gap:4px}
.consultation-reason{margin-left:4px}
.consultation-procedures{border-top:1px solid #dbeafe;padding:10px 12px;display:grid;gap:8px}
.procedure-card{border:1px solid #e2e8f0;border-radius:10px;padding:10px;background:#fff;transition:border-color .15s;display:grid;gap:6px}
.procedure-card:hover{border-color:#93c5fd}
.procedure-card-head{display:flex;justify-content:space-between;align-items:center;gap:8px}
.procedure-card-body{display:grid;gap:4px}
.procedure-detail{display:inline-flex;align-items:center;gap:6px;font-size:.85rem;color:#475569}
.procedure-notes{padding:6px 8px;border-radius:6px;background:#f8fafc;border:1px solid #e2e8f0;font-size:.82rem;color:#64748b}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
    const editor = document.getElementById('consultationObservationsEditor');
    const output = document.getElementById('consultationObservationsInput');
    const diagnosisInput = document.getElementById('diagnosisLabelInput');
    const diagnosisCodeInput = document.getElementById('diagnosisCodeInput');
    const consultationForm = editor?.closest('form');
    const stepButtons = document.querySelectorAll('[data-editor-action]');
    const autoRxBlock = document.getElementById('autoRxBlock');
    const autoRxButton = document.getElementById('generateAutoRxBtn');
    const reasonInput = consultationForm?.querySelector('input[name="consultation_reason"]');
    const consentInput = consultationForm?.querySelector('input[name="consent_obtained"]');

    if (editor && output) {
        if (output.value && !editor.innerHTML.trim()) {
            editor.innerHTML = output.value;
        }

        const sync = () => {
            output.value = editor.innerHTML.trim();
        };

        editor.addEventListener('input', sync);
        editor.addEventListener('blur', sync);
        consultationForm?.addEventListener('submit', sync);
        sync();

        stepButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const action = button.dataset.editorAction;
                editor.focus();
                document.execCommand(action, false, null);
                sync();
            });
        });
    }

    if (diagnosisInput && diagnosisCodeInput) {
        if (diagnosisInput.value) {
            const initial = Array.from(document.getElementById('diagnosisCatalogList')?.options || []).find((option) => option.value === diagnosisInput.value);
            diagnosisCodeInput.value = diagnosisCodeInput.value || initial?.label || '';
        }

        const syncAutoProtocolState = () => {
            const text = `${diagnosisInput.value || ''} ${reasonInput?.value || ''}`.toLowerCase();
            const shouldSuggest = /pulpite|extraction/.test(text);
            autoRxBlock?.classList.toggle('d-none', !shouldSuggest);
        };

        diagnosisInput.addEventListener('input', () => {
            const value = diagnosisInput.value || '';
            const selected = Array.from(document.getElementById('diagnosisCatalogList')?.options || []).find((option) => option.value === value);
            diagnosisCodeInput.value = selected?.label || '';
            syncAutoProtocolState();
        });

        reasonInput?.addEventListener('input', syncAutoProtocolState);
        syncAutoProtocolState();

        autoRxButton?.addEventListener('click', () => {
            const triggerText = `${diagnosisInput.value || ''} ${reasonInput?.value || ''}`.trim();
            window.dispatchEvent(new CustomEvent('care:auto-rx-protocol', {
                detail: {
                    trigger: triggerText,
                },
            }));
            document.querySelector('[data-care-tab-target="documents"]')?.click();
            setTimeout(() => {
                document.getElementById('prescriptions')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 180);
        });
    }

    consultationForm?.addEventListener('submit', (event) => {
        if (consentInput && !consentInput.checked) {
            event.preventDefault();
            window.__careShowToast?.('Le consentement Ã©clairÃ© est obligatoire avant enregistrement.', 'error');
            consentInput.focus();
        }
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
    const zone = document.getElementById('clinicalToastZone');
    if (!zone) return;

    window.__careShowToast = (message, type = 'success') => {
        const toast = document.createElement('div');
        toast.className = `clinical-toast ${type}`;
        toast.textContent = message;
        zone.appendChild(toast);
        window.requestAnimationFrame(() => toast.classList.add('show'));
        window.setTimeout(() => {
            toast.classList.remove('show');
            window.setTimeout(() => toast.remove(), 220);
        }, 2600);
    };

    const flashSuccess = <?php echo json_encode(session('success'), 15, 512) ?>;
    if (flashSuccess) {
        window.__careShowToast(flashSuccess, 'success');
    }
})();
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
    const form = document.getElementById('quoteCreateForm');
    if (!form) return;

    const submitButton = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submitButton) submitButton.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = data.message || 'Erreur pendant la gÃ©nÃ©ration du devis.';
                window.__careShowToast?.(message, 'error');
                return;
            }

            window.__careShowToast?.(data.message || 'Devis gÃ©nÃ©rÃ©.', 'success');
            if (data?.quote?.pdf_url) {
                window.open(data.quote.pdf_url, '_blank', 'noopener');
            }
        } catch (_error) {
            window.__careShowToast?.('Erreur rÃ©seau pendant la gÃ©nÃ©ration du devis.', 'error');
        } finally {
            if (submitButton) submitButton.disabled = false;
        }
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
    const dataNode = document.getElementById('patientDirectoryJson');
    const listNode = document.getElementById('patientRows');
    const skeletonNode = document.getElementById('patientSkeletonList');
    const searchNode = document.getElementById('patientOmniSearch');
    const metaNode = document.getElementById('patientDirectoryMeta');
    if (!dataNode || !listNode || !searchNode || !metaNode) return;

    const patients = JSON.parse(dataNode.textContent || '[]');
    const selectedPatientId = Number(metaNode.dataset.selectedPatientId || 0);
    const isNewPatient = metaNode.dataset.newPatient === '1';
    const patientViewBase = metaNode.dataset.patientViewBase || '';
    const clinicalFileBase = metaNode.dataset.clinicalFileBase || '';
    const module2Base = metaNode.dataset.module2Base || '';

    const mojibakeFixMap = [
        ['Ã©', 'e'], ['Ã¨', 'e'], ['Ãª', 'e'], ['Ã«', 'e'], ['Ã‰', 'E'], ['Ãˆ', 'E'],
        ['Ã ', 'a'], ['Ã¢', 'a'], ['Ã¤', 'a'], ['Ã€', 'A'], ['Ã‚', 'A'],
        ['Ã¹', 'u'], ['Ã»', 'u'], ['Ã¼', 'u'],
        ['Ã´', 'o'], ['Ã¶', 'o'],
        ['Ã®', 'i'], ['Ã¯', 'i'],
        ['Ã§', 'c'], ['Ã‡', 'C'],
        ['â€¢', ' - '], ['âœ•', 'x'], ['â€™', "'"],
    ];

    const fixMojibakeText = (value) => {
        let output = String(value ?? '');
        mojibakeFixMap.forEach(([bad, good]) => {
            output = output.split(bad).join(good);
        });
        return output;
    };

    const normalizeMojibakeInNode = (rootNode) => {
        if (!rootNode) return;

        const walker = document.createTreeWalker(rootNode, NodeFilter.SHOW_TEXT);
        const textNodes = [];
        while (walker.nextNode()) textNodes.push(walker.currentNode);
        textNodes.forEach((node) => {
            const fixed = fixMojibakeText(node.nodeValue);
            if (fixed !== node.nodeValue) node.nodeValue = fixed;
        });

        rootNode.querySelectorAll?.('[placeholder],[title],[aria-label]').forEach((el) => {
            ['placeholder', 'title', 'aria-label'].forEach((attr) => {
                const current = el.getAttribute(attr);
                if (!current) return;
                const fixed = fixMojibakeText(current);
                if (fixed !== current) el.setAttribute(attr, fixed);
            });
        });
    };

    normalizeMojibakeInNode(document.body);

    const statusLabel = {
        waiting: 'En salle d attente',
        recall: 'A rappeler',
        incomplete: 'Dossier incomplet',
        normal: 'A jour',
    };

    const statusClass = {
        waiting: 'status-waiting',
        recall: 'status-recall',
        incomplete: 'status-incomplete',
        normal: 'status-normal',
    };

    const normalized = (value) => String(value || '').toLowerCase().trim();
    const initials = (firstName, lastName) => `${String(firstName || '').charAt(0)}${String(lastName || '').charAt(0)}`.toUpperCase();

    function rowTemplate(patient) {
        const query = encodeURIComponent(patient.id);
        const clinicUrl = `${clinicalFileBase}/${patient.id}`;
        const cardUrl = `${patientViewBase}?patient_id=${query}`;
        const rdvUrl = `${module2Base}?patient_id=${query}`;
        const callUrl = patient.phone ? `tel:${patient.phone}` : '#';
        const selectedClass = Number(patient.id) === selectedPatientId ? 'is-selected' : '';
        const popClass = isNewPatient && Number(patient.id) === selectedPatientId ? 'patient-pop' : '';

        return `
            <article class="patient-row ${selectedClass} ${popClass}">
                <div class="patient-avatar">${initials(patient.first_name, patient.last_name)}</div>
                <div>
                    <div class="patient-main-top">
                        <span class="patient-name">${patient.full_name}</span>
                        <span class="patient-status ${statusClass[patient.status] || statusClass.normal}">${statusLabel[patient.status] || statusLabel.normal}</span>
                        <span class="patient-mini">MRN: ${patient.medical_record_number || '-'}</span>
                    </div>
                    <div class="patient-meta">
                        <span>Tel: ${patient.phone || '-'}</span>
                        <span>Age: ${patient.age || '-'}</span>
                        <span>Dernier acte: ${patient.last_act || 'Aucun'}</span>
                        <span>${patient.last_act_at || ''}</span>
                    </div>
                </div>
                <div class="patient-quick-actions">
                    <a class="btn btn-sm btn-outline-primary" href="${clinicUrl}">Ouvrir Dossier Clinique</a>
                    <a class="btn btn-sm btn-outline-secondary" href="${rdvUrl}">Prendre RDV</a>
                    <a class="btn btn-sm btn-outline-success ${patient.phone ? '' : 'disabled'}" href="${callUrl}">Appeler</a>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-edit-patient-id="${patient.id}">Modifier</button>
                    <a class="btn btn-sm btn-primary" href="${cardUrl}">Selectionner</a>
                </div>
            </article>`;
    }

    function render(filteredPatients) {
        listNode.innerHTML = filteredPatients.length
            ? filteredPatients.map(rowTemplate).join('')
            : '<div class="text-secondary">Aucun patient ne correspond a la recherche.</div>';
    }

    function applyFilter() {
        const term = normalized(searchNode.value);
        if (!term) {
            render(patients);
            return;
        }

        const filtered = patients.filter((patient) => {
            const haystack = [
                patient.first_name,
                patient.last_name,
                patient.phone,
                patient.medical_record_number,
            ].map(normalized).join(' ');
            return haystack.includes(term);
        });
        render(filtered);
    }

    window.setTimeout(() => {
        skeletonNode.classList.add('d-none');
        listNode.classList.remove('d-none');
        render(patients);
    }, 520);

    searchNode.addEventListener('input', applyFilter);

    const slideover = document.getElementById('patientSlideover');
    const openBtn = document.getElementById('openPatientSlideover');
    const closeBackdrop = document.getElementById('patientSlideoverClose');
    const closeBtn = document.getElementById('patientSlideoverCloseBtn');
    const form = document.getElementById('newPatientForm');
    if (!slideover || !openBtn || !closeBackdrop || !closeBtn || !form) return;
    const storeAction = form.dataset.storeAction || form.action;
    const updateBase = form.dataset.updateBase || '';
    const methodOverride = document.getElementById('patientFormMethod');
    const editingPatientIdInput = document.getElementById('editingPatientId');
    const titleNode = document.getElementById('newPatientTitle');
    const subtitleNode = slideover.querySelector('.slideover-header .muted.small');

    const stepChips = Array.from(document.querySelectorAll('#patientStepper .step-chip'));
    const steps = Array.from(form.querySelectorAll('.form-step'));
    const nextBtn = document.getElementById('patientNextStep');
    const prevBtn = document.getElementById('patientPrevStep');
    const submitBtn = document.getElementById('patientSubmitBtn');
    let currentStep = 1;
    let patientSubmitPending = false;

    const toCsv = (value) => Array.isArray(value) ? value.join(', ') : '';
    const setSelectValue = (name, value) => {
        const select = form.querySelector(`[name="${name}"]`);
        if (!select) return;
        const target = value ?? '';
        const hasOption = Array.from(select.options).some((option) => option.value === target);
        select.value = hasOption ? target : '';
    };

    const applyCreateMode = () => {
        form.action = storeAction;
        if (methodOverride) methodOverride.disabled = true;
        if (editingPatientIdInput) editingPatientIdInput.value = '';
        if (titleNode) titleNode.textContent = 'Nouveau Patient';
        if (subtitleNode) subtitleNode.textContent = 'Inscription rapide en 3 Ã©tapes';
        if (submitBtn) submitBtn.textContent = 'Enregistrer le patient';
    };

    const applyEditMode = (patient) => {
        if (!patient || !patient.id) return;
        form.action = `${updateBase}/${patient.id}`;
        if (methodOverride) methodOverride.disabled = false;
        if (editingPatientIdInput) editingPatientIdInput.value = String(patient.id);
        if (titleNode) titleNode.textContent = 'Modifier Patient';
        if (subtitleNode) subtitleNode.textContent = `${patient.full_name || ''} - ${patient.medical_record_number || ''}`.trim();
        if (submitBtn) submitBtn.textContent = 'Enregistrer les modifications';

        form.querySelector('[name="first_name"]').value = patient.first_name || '';
        form.querySelector('[name="last_name"]').value = patient.last_name || '';
        form.querySelector('[name="cin"]').value = patient.cin || '';
        form.querySelector('[name="date_of_birth"]').value = patient.date_of_birth || '';
        setSelectValue('gender', patient.gender || '');
        setSelectValue('blood_group', patient.blood_group || '');
        form.querySelector('[name="height_cm"]').value = patient.height_cm ?? '';
        form.querySelector('[name="weight_kg"]').value = patient.weight_kg ?? '';
        form.querySelector('[name="phone"]').value = patient.phone || '';
        form.querySelector('[name="email"]').value = patient.email || '';
        form.querySelector('[name="address"]').value = patient.address || '';
        form.querySelector('[name="emergency_contact_name"]').value = patient.emergency_contact_name || '';
        form.querySelector('[name="emergency_contact_phone"]').value = patient.emergency_contact_phone || '';
        form.querySelector('[name="allergies"]').value = toCsv(patient.allergies);
        form.querySelector('[name="medical_history"]').value = toCsv(patient.medical_history);
        form.querySelector('[name="critical_conditions"]').value = toCsv(patient.critical_conditions);
        form.querySelector('[name="personal_history"]').value = toCsv(patient.personal_history);
        form.querySelector('[name="family_history"]').value = toCsv(patient.family_history);
        form.querySelector('[name="current_medications"]').value = toCsv(patient.current_medications);
        form.querySelector('[name="consultation_reason"]').value = '';
        setSelectValue('consultation_type', 'bilan');
    };

    const openSlideover = () => {
        slideover.classList.add('show');
        slideover.setAttribute('aria-hidden', 'false');
    };

    const closeSlideover = () => {
        slideover.classList.remove('show');
        slideover.setAttribute('aria-hidden', 'true');
        form.querySelector('#patientDuplicateAlert')?.classList.add('d-none');
    };

    function syncStep() {
        steps.forEach((step) => step.classList.toggle('active', Number(step.dataset.step) === currentStep));
        stepChips.forEach((chip) => chip.classList.toggle('active', Number(chip.dataset.step) === currentStep));
        prevBtn.classList.toggle('d-none', currentStep === 1);
        nextBtn.classList.toggle('d-none', currentStep === 3);
        submitBtn.classList.toggle('d-none', currentStep !== 3);
    }

    const validateStep = (stepNumber) => {
        const stepNode = steps.find((step) => Number(step.dataset.step) === Number(stepNumber));
        if (!stepNode) return true;

        const requiredFields = stepNode.querySelectorAll('input[required], select[required], textarea[required]');
        for (const field of requiredFields) {
            if (field.disabled) continue;
            if (!field.checkValidity()) {
                currentStep = Number(stepNumber);
                syncStep();
                field.reportValidity();
                return false;
            }
        }

        return true;
    };

    openBtn.addEventListener('click', () => {
        form.reset();
        currentStep = 1;
        applyCreateMode();
        syncStep();
        openSlideover();
    });
    closeBackdrop.addEventListener('click', closeSlideover);
    closeBtn.addEventListener('click', closeSlideover);
    listNode.addEventListener('click', (event) => {
        const editButton = event.target.closest('[data-edit-patient-id]');
        if (!editButton) return;
        event.preventDefault();

        const targetId = Number(editButton.dataset.editPatientId || 0);
        const patient = patients.find((row) => Number(row.id) === targetId);
        if (!patient) return;

        form.reset();
        currentStep = 1;
        applyEditMode(patient);
        syncStep();
        dobInput?.dispatchEvent(new Event('input'));
        openSlideover();
    });
    nextBtn?.addEventListener('click', () => {
        if (!validateStep(currentStep)) return;
        currentStep = Math.min(3, currentStep + 1);
        syncStep();
    });
    prevBtn?.addEventListener('click', () => { currentStep = Math.max(1, currentStep - 1); syncStep(); });
    applyCreateMode();
    syncStep();

    const clearPatientErrors = () => {
        form.querySelectorAll('.is-invalid').forEach((node) => node.classList.remove('is-invalid'));
        form.querySelectorAll('.patient-inline-error').forEach((node) => node.remove());
    };

    const setPatientFieldError = (fieldName, message) => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;
        field.classList.add('is-invalid');

        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block patient-inline-error';
        feedback.textContent = message;
        field.insertAdjacentElement('afterend', feedback);
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (patientSubmitPending) return;
        clearPatientErrors();

        if (!validateStep(1) || !validateStep(2) || !validateStep(3)) {
            return;
        }

        const activeSubmitButton = form.querySelector('button[type="submit"]:not(.d-none)');
        const originalLabel = activeSubmitButton?.textContent || 'Enregistrer';
        const formData = new FormData(form);

        if (methodOverride?.disabled) {
            formData.delete('_method');
        }

        if (activeSubmitButton) {
            patientSubmitPending = true;
            activeSubmitButton.disabled = true;
            activeSubmitButton.textContent = 'Enregistrement...';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: formData,
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                Object.entries(data.errors || {}).forEach(([field, messages]) => {
                    setPatientFieldError(field, Array.isArray(messages) ? messages[0] : String(messages));
                });
                window.__careShowToast?.(data.message || 'Impossible d enregistrer le patient.', 'error');
                return;
            }

            window.__careShowToast?.(data.message || 'Patient enregistre.', 'success');
            closeSlideover();
            window.location.assign(data.redirect_url || response.url || '<?php echo e(route('care.module3.index')); ?>');
        } catch (_error) {
            window.__careShowToast?.('Erreur reseau pendant l enregistrement du patient.', 'error');
        } finally {
            patientSubmitPending = false;
            if (activeSubmitButton) {
                activeSubmitButton.disabled = false;
                activeSubmitButton.textContent = originalLabel;
            }
        }
    });

    const dobInput = document.getElementById('npBirthDate');
    const ageInput = document.getElementById('npAge');
    dobInput?.addEventListener('input', () => {
        const value = dobInput.value;
        if (!value) {
            ageInput.value = '';
            return;
        }
        const birth = new Date(`${value}T00:00:00`);
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const m = today.getMonth() - birth.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age -= 1;
        ageInput.value = Number.isFinite(age) ? `${age} ans` : '';
    });

    const addressInput = document.getElementById('npAddress');
    const addressList = document.getElementById('addressSuggestionsList');
    const addressMemoryKey = 'care.addressSuggestions';
    const existingAddress = JSON.parse(window.localStorage.getItem(addressMemoryKey) || '[]');
    existingAddress.slice(0, 5).forEach((address) => {
        const option = document.createElement('option');
        option.value = address;
        addressList.appendChild(option);
    });

    addressInput?.addEventListener('blur', () => {
        const value = String(addressInput.value || '').trim();
        if (!value) return;
        const merged = [value, ...existingAddress.filter((it) => it !== value)].slice(0, 10);
        window.localStorage.setItem(addressMemoryKey, JSON.stringify(merged));
    });

    const phoneInput = document.getElementById('npPhone');
    const duplicateAlert = document.getElementById('patientDuplicateAlert');
    const openExisting = document.getElementById('openExistingPatient');
    const findByPhone = (phone) => patients.find((p) => normalized(p.phone).replace(/\s+/g, '') === normalized(phone).replace(/\s+/g, ''));
    phoneInput?.addEventListener('input', () => {
        const match = findByPhone(phoneInput.value);
        if (!match) {
            duplicateAlert.classList.add('d-none');
            openExisting.setAttribute('href', '#');
            return;
        }
        duplicateAlert.classList.remove('d-none');
        openExisting.setAttribute('href', `${patientViewBase}?patient_id=${match.id}`);
    });

    const photoInput = document.getElementById('patientPhotoInput');
    const photoDrop = document.getElementById('patientPhotoDrop');
    const photoPreview = document.getElementById('patientPhotoPreview');
    const selectPhotoBtn = document.getElementById('btnSelectPhoto');
    const startWebcamBtn = document.getElementById('btnStartWebcam');
    const captureWebcamBtn = document.getElementById('btnCaptureWebcam');
    const webcam = document.getElementById('patientWebcam');
    let webcamStream = null;

    const setPreviewFromFile = (file) => {
        const url = URL.createObjectURL(file);
        photoPreview.style.backgroundImage = `url('${url}')`;
        photoPreview.textContent = '';
    };

    selectPhotoBtn?.addEventListener('click', () => photoInput?.click());
    photoInput?.addEventListener('change', () => {
        const file = photoInput.files?.[0];
        if (file) setPreviewFromFile(file);
    });

    photoDrop?.addEventListener('dragover', (event) => {
        event.preventDefault();
        photoDrop.classList.add('dragover');
    });
    photoDrop?.addEventListener('dragleave', () => photoDrop.classList.remove('dragover'));
    photoDrop?.addEventListener('drop', (event) => {
        event.preventDefault();
        photoDrop.classList.remove('dragover');
        const file = event.dataTransfer?.files?.[0];
        if (!file) return;
        setPreviewFromFile(file);
    });

    startWebcamBtn?.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia) return;
        webcamStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
        webcam.srcObject = webcamStream;
        webcam.classList.remove('d-none');
        captureWebcamBtn.classList.remove('d-none');
    });

    captureWebcamBtn?.addEventListener('click', () => {
        if (!webcam) return;
        const canvas = document.createElement('canvas');
        canvas.width = webcam.videoWidth || 640;
        canvas.height = webcam.videoHeight || 480;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(webcam, 0, 0, canvas.width, canvas.height);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.88);
        photoPreview.style.backgroundImage = `url('${dataUrl}')`;
        photoPreview.textContent = '';
        if (webcamStream) {
            webcamStream.getTracks().forEach((track) => track.stop());
            webcamStream = null;
        }
        webcam.classList.add('d-none');
        captureWebcamBtn.classList.add('d-none');
    });

    if (selectedPatientId > 0) {
        const historyEndpoint = <?php echo json_encode(url('/care/module-3/patients/'.$selectedPatientId.'/history'), 15, 512) ?>;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const inlineConfigs = {
            personal: {
                form: document.getElementById('personal-history-form'),
                list: document.getElementById('personal-history-items'),
                placeholder: document.getElementById('personal-history-placeholder'),
            },
            family: {
                form: document.getElementById('family-history-form'),
                list: document.getElementById('family-history-items'),
                placeholder: document.getElementById('family-history-placeholder'),
            },
        };

        const ensureHistoryList = (type) => {
            const config = inlineConfigs[type];
            if (!config?.form) return null;
            if (!config.list) {
                config.list = document.createElement('div');
                config.list.className = 'history-items-list';
                config.list.id = `${type}-history-items`;
                config.form.insertAdjacentElement('beforebegin', config.list);
            }

            return config.list;
        };

        const createHistoryTag = (type, value) => {
            const item = document.createElement('div');
            item.className = 'history-item history-item-tag';
            item.dataset.item = value;
            item.dataset.historyType = type;
            item.innerHTML = `<span>${escapeHtml(value)}</span><button type="button" class="btn-close-inline" data-history-type="${type}" data-item="${escapeHtml(value)}" title="Supprimer">âœ•</button>`;

            return item;
        };

        const hasHistoryValue = (type, value) => {
            const config = inlineConfigs[type];
            const normalizedValue = normalized(value);

            return Array.from(config?.list?.querySelectorAll('.history-item') || []).some((node) => normalized(node.dataset.item) === normalizedValue);
        };

        const showInlineForm = (type) => {
            const config = inlineConfigs[type];
            const input = config?.form?.querySelector('.inline-input');
            if (!config || !input) return;

            config.form.classList.remove('d-none');
            config.placeholder?.classList.add('d-none');
            window.setTimeout(() => input.focus(), 20);
        };

        const hideInlineForm = (type, clearValue = false) => {
            const config = inlineConfigs[type];
            const input = config?.form?.querySelector('.inline-input');
            if (!config || !input) return;

            if (clearValue) {
                input.value = '';
                input.classList.remove('success', 'error');
            }

            config.form.classList.add('d-none');
            if (!config.list?.children.length) {
                config.placeholder?.classList.remove('d-none');
            }
        };

        const addHistoryChip = (type, value) => {
            const config = inlineConfigs[type];
            const list = ensureHistoryList(type);
            if (!config || !list || hasHistoryValue(type, value)) return;

            config.placeholder?.classList.add('d-none');
            list.appendChild(createHistoryTag(type, value));
        };

        const removeHistoryChip = (type, value) => {
            const config = inlineConfigs[type];
            if (!config?.list) return;

            const target = Array.from(config.list.querySelectorAll('.history-item'))
                .find((node) => normalized(node.dataset.item) === normalized(value));
            target?.remove();

            if (!config.list.querySelector('.history-item')) {
                config.list.remove();
                config.list = null;
                config.placeholder?.classList.remove('d-none');
            }
        };

        const submitInlineHistory = async (type, value, method = 'POST') => {
            const body = new URLSearchParams();
            body.set('_token', csrfToken);
            body.set('type', type);
            body.set('value', value);
            if (method !== 'POST') {
                body.set('_method', method);
            }

            const response = await fetch(historyEndpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                },
                body: body.toString(),
            });

            return response.json().catch(() => ({})).then((data) => ({ ok: response.ok, data }));
        };

        document.addEventListener('click', async (event) => {
            const addTrigger = event.target.closest('.inline-add-btn, .inline-edit-trigger');
            if (addTrigger) {
                event.preventDefault();
                showInlineForm(addTrigger.dataset.historyType);
                return;
            }

            const removeTrigger = event.target.closest('.btn-close-inline');
            if (!removeTrigger) return;
            event.preventDefault();

            const type = String(removeTrigger.dataset.historyType || '').trim();
            const value = String(removeTrigger.dataset.item || '').trim();
            if (!type || !value) return;

            const previousNode = removeTrigger.closest('.history-item');
            previousNode?.classList.add('opacity-50');

            try {
                const { ok, data } = await submitInlineHistory(type, value, 'DELETE');
                if (!ok) {
                    previousNode?.classList.remove('opacity-50');
                    window.__careShowToast?.(data.message || 'Suppression impossible.', 'error');
                    return;
                }

                removeHistoryChip(type, value);
                window.__careShowToast?.(data.message || 'Antecedent supprime.', 'success');
            } catch (_error) {
                previousNode?.classList.remove('opacity-50');
                window.__careShowToast?.('Erreur reseau pendant la suppression.', 'error');
            }
        });

        Object.entries(inlineConfigs).forEach(([type, config]) => {
            const input = config.form?.querySelector('.inline-input');
            if (!input) return;

            input.addEventListener('keydown', async (event) => {
                if (event.key !== 'Enter') return;
                event.preventDefault();

                const value = String(input.value || '').trim();
                if (!value) return;
                if (hasHistoryValue(type, value)) {
                    input.classList.add('error');
                    window.__careShowToast?.('Element deja present.', 'error');
                    return;
                }

                input.disabled = true;
                try {
                    const { ok, data } = await submitInlineHistory(type, value, 'POST');
                    if (!ok) {
                        input.classList.add('error');
                        window.__careShowToast?.(data.message || 'Ajout impossible.', 'error');
                        return;
                    }

                    addHistoryChip(type, value);
                    input.classList.remove('error');
                    input.classList.add('success');
                    window.__careShowToast?.(data.message || 'Antecedent ajoute.', 'success');
                    hideInlineForm(type, true);
                } catch (_error) {
                    input.classList.add('error');
                    window.__careShowToast?.('Erreur reseau pendant l ajout.', 'error');
                } finally {
                    input.disabled = false;
                }
            });

            input.addEventListener('blur', () => {
                window.setTimeout(() => hideInlineForm(type, true), 120);
            });
        });
    }

    const timelineSearchInput = document.getElementById('timelineSearchInput');
    const timelineRows = Array.from(document.querySelectorAll('[data-timeline-row]'));
    timelineSearchInput?.addEventListener('input', () => {
        const term = normalized(timelineSearchInput.value);
        timelineRows.forEach((row) => {
            const searchable = normalized(row.dataset.searchable || '');
            row.closest('.timeline-item')?.classList.toggle('d-none', term !== '' && !searchable.includes(term));
        });
    });

    const consultationForm = document.getElementById('consultationForm');
    const patientHistoryModalNode = document.getElementById('patientHistoryModal');
    const patientHistoryForm = document.getElementById('patientHistoryForm');
    const patientHistoryModalMessage = document.getElementById('patientHistoryModalMessage');
    const openHistoryButtons = document.querySelectorAll('[data-open-history-modal]');
    const historyModal = window.bootstrap && patientHistoryModalNode ? bootstrap.Modal.getOrCreateInstance(patientHistoryModalNode) : null;
    const historyBadges = {
        allergies: document.getElementById('historyAllergiesBadges'),
        medical_history: document.getElementById('historyMedicalBadges'),
        critical_conditions: document.getElementById('historyRiskBadges'),
    };
    const consultationTimeline = document.querySelector('.timeline-vertical');
    const consultationHistoryTable = document.querySelector('#medical-history-complete tbody');

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[character]));

    const setFieldError = (fieldName, message) => {
        const field = consultationForm?.querySelector(`[name="${fieldName}"]`);
        if (!field) return;
        field.classList.add('is-invalid');
        let feedback = field.nextElementSibling;
        while (feedback && !feedback.classList.contains('invalid-feedback')) {
            feedback = feedback.nextElementSibling;
        }
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block';
            field.insertAdjacentElement('afterend', feedback);
        }
        feedback.textContent = message;
    };

    const clearConsultationErrors = () => {
        consultationForm?.querySelectorAll('.is-invalid').forEach((node) => node.classList.remove('is-invalid'));
        consultationForm?.querySelectorAll('.invalid-feedback').forEach((node) => {
            if (node.dataset.persistent !== '1') {
                node.remove();
            }
        });
    };

    const buildConsultationRow = (consultation) => `
        <article class="timeline-item history-refresh-flash">
            <div class="timeline-dot"></div>
            <div class="timeline-content" data-timeline-row data-searchable="${escapeHtml([consultation.reason, consultation.diagnosis, consultation.practitioner, consultation.chief_complaint, consultation.observations, consultation.notes].filter(Boolean).join(' | '))}">
                <header><strong>${escapeHtml(consultation.reason || 'Consultation')}</strong><span class="badge bg-indigo-lt">consultation</span></header>
                <div class="meta">${escapeHtml(consultation.date || '-')} | Motif: ${escapeHtml(consultation.reason || '-')} | Praticien: ${escapeHtml(consultation.practitioner || '-')} | Statut: ${escapeHtml(consultation.status || '-')}</div>
                <div class="meta">Diagnostic: ${escapeHtml(consultation.diagnosis || '-')}</div>
                <div class="timeline-actions mt-2"><button type="button" class="btn btn-sm btn-outline-primary">Voir</button><button type="button" class="btn btn-sm btn-outline-secondary">Imprimer</button></div>
            </div>
        </article>`;

    const buildHistoryRow = (date, title, practitioner, actionsHtml) => `
        <tr class="history-refresh-flash">
            <td>${escapeHtml(date)}</td>
            <td>${escapeHtml(title)}</td>
            <td>${escapeHtml(practitioner)}</td>
            <td>${actionsHtml}</td>
        </tr>`;

    const renderBadges = (group, items) => {
        const container = historyBadges[group];
        if (!container) return;
        container.innerHTML = items.length
            ? items.map((item) => `<span class="history-badge ${group === 'allergies' ? 'history-badge-danger' : 'history-badge-warning'}">${escapeHtml(item)}</span>`).join('')
            : `<span class="history-empty">Aucun Ã©lÃ©ment</span>`;
    };

    const snapshotBadgeHtml = () => Object.fromEntries(
        Object.entries(historyBadges).map(([group, container]) => [group, container?.innerHTML ?? ''])
    );
    const restoreBadgeHtml = (snapshot) => {
        Object.entries(snapshot || {}).forEach(([group, html]) => {
            const container = historyBadges[group];
            if (container) container.innerHTML = html;
        });
    };
    const appendOptimisticBadge = (group, item) => {
        const container = historyBadges[group];
        if (!container) return;
        const normalized = String(item || '').trim().toLowerCase();
        if (!normalized) return;

        const alreadyExists = Array.from(container.querySelectorAll('.history-badge'))
            .some((badge) => String(badge.textContent || '').trim().toLowerCase() === normalized);
        if (alreadyExists) return;

        container.querySelector('.history-empty')?.remove();
        const badge = document.createElement('span');
        badge.className = `history-badge ${group === 'allergies' ? 'history-badge-danger' : 'history-badge-warning'} history-badge-pending`;
        badge.textContent = String(item).trim();
        container.appendChild(badge);
    };
    let historyAddPending = false;

    const showHistoryModal = () => {
        if (historyModal) {
            historyModal.show();
            return;
        }

        if (!patientHistoryModalNode) return;
        patientHistoryModalNode.classList.add('show');
        patientHistoryModalNode.style.display = 'block';
        patientHistoryModalNode.setAttribute('aria-hidden', 'false');
        patientHistoryModalNode.setAttribute('aria-modal', 'true');
        document.body.classList.add('modal-open');
    };

    const hideHistoryModal = () => {
        if (historyModal) {
            historyModal.hide();
            return;
        }

        if (!patientHistoryModalNode) return;
        patientHistoryModalNode.classList.remove('show');
        patientHistoryModalNode.style.display = 'none';
        patientHistoryModalNode.setAttribute('aria-hidden', 'true');
        patientHistoryModalNode.removeAttribute('aria-modal');
        document.body.classList.remove('modal-open');
    };

    openHistoryButtons.forEach((button) => button.addEventListener('click', showHistoryModal));

    patientHistoryModalNode?.addEventListener('click', (event) => {
        if (event.target === patientHistoryModalNode) {
            hideHistoryModal();
        }
    });

    patientHistoryModalNode?.querySelectorAll('[data-bs-dismiss="modal"]').forEach((button) => {
        button.addEventListener('click', hideHistoryModal);
    });

    patientHistoryForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!patientHistoryModalNode) return;
        if (historyAddPending) return;

        const submitButton = patientHistoryForm.querySelector('button[type="submit"]');
        const formData = new FormData(patientHistoryForm);
        const group = String(formData.get('group') || '').trim();
        const item = String(formData.get('item') || '').trim();
        if (!group || !item) {
            patientHistoryModalMessage.textContent = 'Selectionnez un type et un libelle.';
            return;
        }
        formData.set('group', group);
        formData.set('item', item);
        const originalButtonLabel = submitButton?.textContent || 'Ajouter';
        const badgesSnapshot = snapshotBadgeHtml();
        patientHistoryModalMessage.textContent = '';
        historyAddPending = true;
        appendOptimisticBadge(group, item);
        hideHistoryModal();
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Ajout...';
        }

        try {
            if (!selectedPatientId) {
                restoreBadgeHtml(badgesSnapshot);
                patientHistoryModalMessage.textContent = 'Selectionnez un patient avant d ajouter un antecedent.';
                window.__careShowToast?.('Selectionnez un patient avant d ajouter un antecedent.', 'error');
                showHistoryModal();
                return;
            }
            const historyItemsStoreBase = <?php echo json_encode(url('/care/module-3/patients'), 15, 512) ?>;
            const response = await fetch(`${historyItemsStoreBase}/${selectedPatientId}/history-items`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: formData,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                restoreBadgeHtml(badgesSnapshot);
                patientHistoryModalMessage.textContent = data.message || 'Erreur pendant l ajout.';
                window.__careShowToast?.(data.message || 'Erreur pendant l ajout.', 'error');
                showHistoryModal();
                return;
            }

            const payload = data.patient || {};
            renderBadges('allergies', payload.allergies || []);
            renderBadges('medical_history', payload.medical_history || []);
            renderBadges('critical_conditions', payload.critical_conditions || []);
            window.__careShowToast?.(data.message || 'AntÃ©cÃ©dent ajoutÃ©.', 'success');
            patientHistoryForm.reset();
            hideHistoryModal();
            window.dispatchEvent(new Event('care:data-updated'));
        } catch (_error) {
            restoreBadgeHtml(badgesSnapshot);
            patientHistoryModalMessage.textContent = 'Erreur reseau pendant l ajout.';
            window.__careShowToast?.('Erreur reseau pendant l ajout.', 'error');
            showHistoryModal();
        } finally {
            historyAddPending = false;
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonLabel;
            }
        }
    });

    consultationForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearConsultationErrors();

        const submitButton = consultationForm.querySelector('button[type="submit"]');
        const formData = new FormData(consultationForm);
        formData.set('consent_obtained', consultationForm.querySelector('[name="consent_obtained"]')?.checked ? '1' : '0');
        if (submitButton) submitButton.disabled = true;

        try {
            const response = await fetch(consultationForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: formData,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                Object.entries(data.errors || {}).forEach(([field, messages]) => setFieldError(field, Array.isArray(messages) ? messages[0] : String(messages)));
                window.__careShowToast?.(data.message || 'Corrigez les champs en erreur.', 'error');
                return;
            }

            const consultation = data.consultation || {};
            if (consultationTimeline) {
                consultationTimeline.insertAdjacentHTML('afterbegin', buildConsultationRow(consultation));
            }
            if (consultationHistoryTable) {
                consultationHistoryTable.insertAdjacentHTML('afterbegin', buildHistoryRow(consultation.date || '-', consultation.reason || 'Consultation', consultation.practitioner || '-', '<button type="button" class="btn btn-sm btn-outline-primary">Voir</button> <button type="button" class="btn btn-sm btn-outline-secondary">Imprimer</button>'));
            }
            window.__careShowToast?.(data.message || 'Consultation enregistrée', 'success');
            consultationForm.reset();
            const consentCheckbox = consultationForm.querySelector('[name="consent_obtained"]');
            if (consentCheckbox) consentCheckbox.checked = false;
            const observationsEditor = document.getElementById('consultationObservationsEditor');
            const observationsInput = document.getElementById('consultationObservationsInput');
            if (observationsEditor) observationsEditor.innerHTML = '';
            if (observationsInput) observationsInput.value = '';
            window.dispatchEvent(new Event('care:data-updated'));
            window.dispatchEvent(new CustomEvent('care:consultation-created', { detail: consultation }));
            await refreshProcedureConsultations(consultation.id || null);
            
            // Close modal after successful submission
            const modal = bootstrap.Modal.getInstance(document.getElementById('consultationModal'));
            if (modal) modal.hide();
        } catch (_error) {
            window.__careShowToast?.('Erreur reseau pendant l enregistrement SOAP.', 'error');
        } finally {
            if (submitButton) submitButton.disabled = false;
        }
    });
    
    // Auto-open modal if there are validation errors on page load
    <?php if($errors->any() && old('_form_context') === 'consultation'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('consultationModal'));
            modal.show();
        });
    <?php endif; ?>

    window.addEventListener('care:consultation-created', (event) => {
        const consultation = event.detail || {};
        refreshProcedureConsultations(consultation.id || null);
    });

    window.addEventListener('care:data-updated', () => {
        if (procedureFormContainer?.style.display === 'block') {
            refreshProcedureConsultations(procedureConsultation?.value || null);
        }
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<?php echo $__env->make('modules.partials.questionnaire-engine', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('modules.partials.questionnaire-modern', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<script>
(() => {
    const questionnaireTemplates = <?php echo json_encode($questionnaireTemplatesPayload, 15, 512) ?>;
    const launcherModalNode = document.getElementById('questionnaireLauncherModal');
    const quickTemplateSelect = document.getElementById('questionnaireQuickTemplateSelect');
    const launcherForm = document.getElementById('questionnaireDynamicForm');
    const launcherTemplateName = document.getElementById('questionnaireLauncherTemplateName');
    const launcherMessage = document.getElementById('questionnaireLauncherMessage');
    const launcherSubmit = document.getElementById('questionnaireSubmitBtn');
    const defaultConsultationId = <?php echo json_encode(optional($consultations->first())->id, 15, 512) ?>;
    const responseStoreBase = <?php echo json_encode(url('/care/module-3/patients/'.$selectedPatientId.'/questionnaires'), 15, 512) ?>;
    let launcherModal = null;
    let fallbackCloseButton = null;

    if (quickTemplateSelect && !quickTemplateSelect.value && questionnaireTemplates.length) {
        quickTemplateSelect.value = String(questionnaireTemplates[0].id);
    }

    function selectedTemplate() {
        if (!questionnaireTemplates.length) return null;
        const selectedId = quickTemplateSelect?.value;
        return questionnaireTemplates.find((item) => String(item.id) === String(selectedId)) || questionnaireTemplates[0];
    }

    function renderLauncher(template) {
        if (!launcherForm) return;
        if (!template) {
            launcherForm.innerHTML = '<div class="text-secondary">Aucun template disponible.</div>';
            if (launcherTemplateName) launcherTemplateName.textContent = '';
            return;
        }

        launcherTemplateName.textContent = `Template: ${template.name}`;
        window.QuestionnaireEngine?.renderDynamicForm(launcherForm, template.field_schema || [], 'answers');
    }

    function showLauncherFallback() {
        if (!launcherModalNode) return;
        launcherModalNode.style.display = 'block';
        launcherModalNode.classList.add('show');
        launcherModalNode.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
        if (!fallbackCloseButton) {
            fallbackCloseButton = launcherModalNode.querySelector('[data-bs-dismiss="modal"]');
            fallbackCloseButton?.addEventListener('click', hideLauncherFallback);
        }
    }

    function hideLauncherFallback() {
        if (!launcherModalNode) return;
        launcherModalNode.style.display = 'none';
        launcherModalNode.classList.remove('show');
        launcherModalNode.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    function openLauncher() {
        if (!launcherModal && window.bootstrap && launcherModalNode) {
            launcherModal = bootstrap.Modal.getOrCreateInstance(launcherModalNode);
        }
        launcherMessage.textContent = '';
        renderLauncher(selectedTemplate());
        if (!defaultConsultationId) {
            launcherMessage.textContent = 'Aucune consultation trouvee pour ce patient. Creez une consultation avant de soumettre un questionnaire.';
        }
        if (launcherModal) {
            launcherModal.show();
        } else {
            showLauncherFallback();
        }
    }

    quickTemplateSelect?.addEventListener('change', () => renderLauncher(selectedTemplate()));

    launcherSubmit?.addEventListener('click', async () => {
        const template = selectedTemplate();
        if (!template?.id) return;
        launcherMessage.textContent = '';
        if (!defaultConsultationId) {
            launcherMessage.textContent = 'Aucune consultation disponible pour rattacher ce questionnaire.';
            return;
        }

        const payload = {
            consultation_id: defaultConsultationId,
            answered_at: new Date().toISOString().slice(0, 10),
            notes: null,
            answers: {},
        };

        launcherForm?.querySelectorAll('[name^="answers["]').forEach((input) => {
            const key = input.name.replace(/^answers\[(.*)\](\[\])?$/, '$1');
            if (input.type === 'checkbox' && input.name.endsWith('[]')) {
                if (!Array.isArray(payload.answers[key])) payload.answers[key] = [];
                if (input.checked) payload.answers[key].push(input.value);
            } else if (input.type === 'checkbox') {
                payload.answers[key] = input.checked;
            } else if (input.type === 'radio') {
                if (input.checked) payload.answers[key] = input.value;
            } else {
                payload.answers[key] = input.value;
            }
        });

        const response = await fetch(`${responseStoreBase}/${template.id}/responses`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            launcherMessage.textContent = data.message || 'Erreur pendant l enregistrement.';
            return;
        }
        launcherMessage.textContent = data.message || 'Questionnaire enregistre.';
        window.dispatchEvent(new Event('care:data-updated'));
        setTimeout(() => {
            if (launcherModal) {
                launcherModal.hide();
            } else {
                hideLauncherFallback();
            }
        }, 700);
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<!-- Three.js CDN - Fallback local if CDN fails -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
function getCareSelectedPatientId() {
    const metaNode = document.querySelector('[data-selected-patient-id]');
    const fromMeta = Number(metaNode?.dataset?.selectedPatientId || 0);
    if (Number.isFinite(fromMeta) && fromMeta > 0) {
        return fromMeta;
    }

    return <?php echo e((int) ($selectedPatientId ?? 0)); ?>;
}

const careProcedurePatientsBaseUrl = <?php echo json_encode(url('/care/module-3/patients'), 15, 512) ?>;

// Fallback: if CDN fails, try alternative
if (typeof THREE === 'undefined') {
    console.warn('Three.js CDN 1 failed, trying alternative...');
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/three@0.134.0/build/three.min.js';
    script.onload = () => initDental3D();
    script.onerror = () => {
        console.error('Three.js failed to load from all CDNs');
        const state = document.getElementById('toothState');
        if (state) state.innerHTML = '<div class="alert alert-warning">Erreur de chargement 3D. <button class="btn btn-sm btn-primary" onclick="location.reload()">Recharger</button></div>';
    };
    document.head.appendChild(script);
} else {
    initDental3D();
}

function initDental3D() {
    if (typeof window.__careModule3Cleanup === 'function') {
        window.__careModule3Cleanup();
    }
(() => {
    if (typeof THREE === 'undefined') {
        console.error('Three.js non charge');
        const state = document.getElementById('toothState');
        if (state) state.innerHTML = '<div class="alert alert-warning">Erreur de chargement 3D. <button class="btn btn-sm btn-primary" onclick="location.reload()">Recharger</button></div>';
        return;
    }

    // OrbitControls inline implementation (simple version)
    THREE.OrbitControls = function(camera, domElement) {
        this.camera = camera;
        this.domElement = domElement;
        this.enabled = true;
        this.enableDamping = false;
        this.autoRotate = false;
        this.autoRotateSpeed = 1.1;
        this.target = new THREE.Vector3();
        
        let spherical = new THREE.Spherical();
        let sphericalDelta = new THREE.Spherical();
        let scale = 1;
        let rotateStart = new THREE.Vector2();
        let rotateEnd = new THREE.Vector2();
        let rotateDelta = new THREE.Vector2();
        let state = -1; // -1: none, 0: rotate, 1: zoom
        
        const offset = new THREE.Vector3();
        
        this.reset = function() {
            camera.position.set(0, 8, 26);
            this.target.set(0, 0, 0);
            camera.lookAt(this.target);
        };
        
        const update = () => {
            offset.copy(camera.position).sub(this.target);
            spherical.setFromVector3(offset);
            spherical.theta += sphericalDelta.theta;
            spherical.phi += sphericalDelta.phi;
            spherical.phi = Math.max(0.1, Math.min(Math.PI - 0.1, spherical.phi));
            spherical.radius *= scale;
            offset.setFromSpherical(spherical);
            camera.position.copy(this.target).add(offset);
            camera.lookAt(this.target);
            sphericalDelta.theta *= 0.9;
            sphericalDelta.phi *= 0.9;
            scale = 1;
        };
        
        domElement.addEventListener('mousedown', (e) => {
            if (e.button === 0) { state = 0; rotateStart.set(e.clientX, e.clientY); }
            else if (e.button === 2) { state = 1; rotateStart.set(e.clientX, e.clientY); }
        });
        
        domElement.addEventListener('mousemove', (e) => {
            if (state === 0) {
                rotateEnd.set(e.clientX, e.clientY);
                rotateDelta.subVectors(rotateEnd, rotateStart);
                sphericalDelta.theta -= 2 * Math.PI * rotateDelta.x / domElement.clientHeight;
                sphericalDelta.phi -= 2 * Math.PI * rotateDelta.y / domElement.clientHeight;
                rotateStart.copy(rotateEnd);
            }
        });
        
        domElement.addEventListener('mouseup', () => { state = -1; });
        domElement.addEventListener('mouseleave', () => { state = -1; });
        
        domElement.addEventListener('wheel', (e) => {
            e.preventDefault();
            if (e.deltaY > 0) scale *= 1.05;
            else scale /= 1.05;
        }, { passive: false });
        
        domElement.addEventListener('dblclick', (event) => {
            // Double-click to add procedure for tooth
            pointerToRay(event);
            const hit = raycaster.intersectObjects(toothMeshes).find((it) => it.object.visible);
            if (!hit) return;
            
            const mesh = hit.object;
            const toothNumber = mesh.userData.tooth;
            
            // Show quick procedure form
            showQuickProcedureForm(event, toothNumber);
        });
        
        domElement.addEventListener('contextmenu', (e) => e.preventDefault());
        
        this.update = update;
        this.reset();
    };

    const mount = document.getElementById('dental3d');
    if (!mount) return;

    const mountW = mount.clientWidth || 800;
    const mountH = mount.clientHeight || 560;

    const data = <?php echo json_encode($odontogramTeethStatus, 15, 512) ?>;
    const annotations = {};
    const selectedTeeth = new Set();
    const colorByStatus = {
        present: 0x94a3b8, implant: 0x22c55e, extracted: 0xef4444, decay: 0xf59e0b,
        crown: 0x3b82f6, root_canal: 0x8b5cf6, filling: 0x06b6d4, fractured: 0xfb7185, absent: 0x334155
    };

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x020617);
    const camera = new THREE.PerspectiveCamera(50, mountW / mountH, 0.1, 1000);
    camera.position.set(0, 8, 26);

    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(window.devicePixelRatio || 1);
    renderer.setSize(mountW, mountH);
    mount.appendChild(renderer.domElement);

    const controls = typeof THREE.OrbitControls === 'function'
        ? new THREE.OrbitControls(camera, renderer.domElement)
        : null;
    if (controls) {
        controls.enableDamping = true;
        controls.autoRotate = true;
        controls.autoRotateSpeed = 1.1;
    }

    scene.add(new THREE.AmbientLight(0xffffff, 0.65));
    const key = new THREE.DirectionalLight(0xffffff, 0.9); key.position.set(14, 20, 10); scene.add(key);
    const fill = new THREE.PointLight(0x38bdf8, 0.35); fill.position.set(-12, -10, 6); scene.add(fill);

    const arch = new THREE.Group(); scene.add(arch);
    const toothMeshes = [];
    let multiMode = false;
    let jawMode = 'all';
    let statusFilter = 'all';
    let animationFrameId = null;

    const normalizedTeeth = Object.keys(data)
        .map((key) => Number(key))
        .filter((num) => Number.isFinite(num) && num >= 11 && num <= 48);

    const teeth = normalizedTeeth.length
        ? normalizedTeeth
        : [18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28,48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38];

    const supportsCapsule = typeof THREE.CapsuleGeometry === 'function';
    const toothGeometry = supportsCapsule
        ? new THREE.CapsuleGeometry(0.44, 0.9, 8, 16)
        : new THREE.CylinderGeometry(0.38, 0.52, 1.35, 16);

    teeth.forEach((tooth, idx) => {
        const upper = idx < 16;
        const row = upper ? 1 : -1;
        const col = idx % 16;
        const x = (col - 7.5) * 1.15;
        const y = upper ? 2.2 : -2.2;
        const z = upper ? 0.8 : -0.8;
        const toothData = data[tooth] || { status: 'present', procedures: [] };
        const status = toothData.status || 'present';
        const mesh = new THREE.Mesh(
            toothGeometry,
            new THREE.MeshStandardMaterial({ color: colorByStatus[status] || colorByStatus.present, roughness: 0.45, metalness: 0.18 })
        );
        mesh.position.set(x, y, z);
        mesh.rotation.x = upper ? -0.35 : 0.35;
        mesh.userData = { tooth, status, upper, procedures: toothData.procedures || [] };
        arch.add(mesh);
        toothMeshes.push(mesh);
    });

    const raycaster = new THREE.Raycaster();
    const pointer = new THREE.Vector2();
    let hovered = null;
    let selected = null;

    const toothTitle = document.getElementById('toothTitle');
    const toothState = document.getElementById('toothState');
    const toothProcedures = document.getElementById('toothProcedures');
    const annotationList = document.getElementById('annotationList');
    const multiSelection = document.getElementById('multiSelection');

    function paintMesh(mesh) {
        const baseColor = colorByStatus[mesh.userData.status] || colorByStatus.present;
        const toothVisualFlags = getToothVisualFlags(mesh.userData.tooth);
        mesh.material.color.setHex(baseColor);
        mesh.material.emissive.setHex(0x000000);
        mesh.visible = true;
        if (jawMode === 'maxillaire' && !mesh.userData.upper) mesh.visible = false;
        if (jawMode === 'mandibule' && mesh.userData.upper) mesh.visible = false;
        if (statusFilter !== 'all' && mesh.userData.status !== statusFilter) mesh.visible = false;
        // Highlight teeth that have procedures
        if (mesh.userData.procedures && mesh.userData.procedures.length) {
            // subtle warm glow for procedural teeth
            mesh.material.emissive.setHex(0xffa500);
            mesh.material.emissiveIntensity = 0.5;
        }
        if (toothVisualFlags.deepPocket) {
            mesh.material.emissive.setHex(0xdc2626);
            mesh.material.emissiveIntensity = 0.62;
        } else if (toothVisualFlags.recentProcedure) {
            mesh.material.emissive.setHex(0x0ea5e9);
            mesh.material.emissiveIntensity = 0.42;
        }
        if (selectedTeeth.has(mesh.userData.tooth)) mesh.material.emissive.setHex(0x2563eb);
        if (selected && selected.userData.tooth === mesh.userData.tooth) mesh.material.emissive.setHex(0x0ea5e9);
    }

    function repaintAll() { toothMeshes.forEach(paintMesh); }

    function renderSelection() {
        const ids = Array.from(selectedTeeth).sort((a,b)=>a-b);
        multiSelection.textContent = ids.length ? ids.join(', ') : 'Aucune';
    }

    function renderAnnotations() {
        const items = Object.entries(annotations);
        annotationList.innerHTML = items.length
            ? items.map(([tooth, note]) => `<div class="annotation-item-modern"><strong>Dent ${tooth}</strong><div class="muted">${note}</div></div>`).join('')
            : '<div class="empty-state small"><p>Aucune annotation</p></div>';
    }

    function getToothVisualFlags(toothNumber, info = null) {
        const tooth = Number(toothNumber);
        const procedureCount = info?.procedures?.length || data?.[tooth]?.procedures?.length || 0;
        const storeFlags = window.HybridOdonto?.state?.teeth?.[tooth]?.flags || {};

        return {
            hasProcedure: procedureCount > 0,
            recentProcedure: Boolean(storeFlags.recentProcedure),
            deepPocket: Boolean(storeFlags.deepPocket),
            bleeding: Boolean(storeFlags.bleeding),
            plaque: Boolean(storeFlags.plaque),
        };
    }

    function renderToothPanel(info) {
        // Update tooth badge and info
        const toothNumberDisplay = document.getElementById('toothNumberDisplay');
        const toothTitle = document.getElementById('toothTitle');
        const toothState = document.getElementById('toothState');
        const toothBadge = document.getElementById('toothBadge');
        const addProcedureBtn = document.getElementById('addProcedureBtn');
        const viewHistoryBtn = document.getElementById('viewHistoryBtn');
        const toothVisualFlags = getToothVisualFlags(info.tooth, info);
        
        if (toothNumberDisplay) toothNumberDisplay.textContent = info.tooth;
        if (toothTitle) toothTitle.textContent = `Dent ${info.tooth}`;
        if (toothState) {
            const badges = [];
            if (toothVisualFlags.hasProcedure) badges.push('<span class="tooth-flag-chip tooth-flag-chip-procedure">Actes</span>');
            if (toothVisualFlags.deepPocket) badges.push('<span class="tooth-flag-chip tooth-flag-chip-danger">Poche profonde</span>');
            if (toothVisualFlags.bleeding) badges.push('<span class="tooth-flag-chip tooth-flag-chip-warning">Saignement</span>');
            toothState.innerHTML = `État: ${info.status}${badges.length ? `<div class="tooth-flag-row">${badges.join('')}</div>` : ''}`;
        }
        
        // Update badge color based on status
        const statusColors = {
            present: 'linear-gradient(135deg,#3b82f6 0%,#2563eb 100%)',
            extracted: 'linear-gradient(135deg,#ef4444 0%,#dc2626 100%)',
            implant: 'linear-gradient(135deg,#22c55e 0%,#16a34a 100%)',
            crown: 'linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%)',
            filling: 'linear-gradient(135deg,#06b6d4 0%,#0891b2 100%)',
            root_canal: 'linear-gradient(135deg,#f59e0b 0%,#d97706 100%)',
            decay: 'linear-gradient(135deg,#f97316 0%,#ea580c 100%)',
            fractured: 'linear-gradient(135deg,#ec4899 0%,#db2777 100%)',
            absent: 'linear-gradient(135deg,#64748b 0%,#475569 100%)'
        };
        if (toothBadge) toothBadge.style.background = statusColors[info.status] || statusColors.present;
        
        // Enable buttons
        if (addProcedureBtn) addProcedureBtn.disabled = false;
        if (viewHistoryBtn) viewHistoryBtn.disabled = false;
        
        // Update procedures list
        const toothProcedures = document.getElementById('toothProcedures');
        const rows = info.procedures || [];
        if (toothProcedures) {
            toothProcedures.innerHTML = rows.length
                ? rows.map((p) => `
                    <div class="procedure-item-modern">
                        <div class="procedure-item-header">
                            <span class="procedure-item-name">${p.type || 'acte'}</span>
                            <span class="procedure-item-badge ${p.status || 'completed'}">${p.status || 'completed'}</span>
                        </div>
                        <div class="procedure-item-meta">
                            <span>${p.date || '-'}</span>
                            ${p.practitioner_name ? `<span>| ${p.practitioner_name}</span>` : ''}
                        </div>
                    </div>
                `).join('')
                : '<div class="empty-state"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg><p>Aucun acte enregistré</p></div>';
        }
    }

    function syncSelectedTooth(toothNumber, focusPanel = true, emitSelection = false) {
        const tooth = Number(toothNumber);
        if (!Number.isFinite(tooth) || tooth <= 0) return;

        if (window.HybridOdonto?.ensureTooth) {
            window.HybridOdonto.ensureTooth(tooth);
            if (emitSelection && window.HybridOdonto.selectTooth) {
                window.HybridOdonto.selectTooth(tooth);
            }
        }

        if (window.toothMeshes && Array.isArray(window.toothMeshes)) {
            const mesh = window.toothMeshes.find((m) => m.userData && Number(m.userData.tooth) === tooth);
            if (mesh) {
                selected = mesh;
                if (focusPanel) renderToothPanel(mesh.userData);
            }
        }

        repaintAll();
    }

    function pointerToRay(event) {
        const rect = renderer.domElement.getBoundingClientRect();
        pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
        raycaster.setFromCamera(pointer, camera);
    }

    renderer.domElement.addEventListener('pointermove', (event) => {
        pointerToRay(event);
        const hit = raycaster.intersectObjects(toothMeshes).find((it) => it.object.visible);
        if (hovered && (!hit || hit.object !== hovered) && hovered !== selected) hovered.scale.set(1,1,1);
        if (hit) {
            hovered = hit.object;
            if (hovered !== selected) hovered.scale.set(1.08,1.08,1.08);
            renderer.domElement.style.cursor = 'pointer';
        } else {
            hovered = null;
            renderer.domElement.style.cursor = 'default';
        }
    });

    renderer.domElement.addEventListener('click', (event) => {
        pointerToRay(event);
        const hit = raycaster.intersectObjects(toothMeshes).find((it) => it.object.visible);
        if (!hit) return;
        const mesh = hit.object;

        if (multiMode) {
            if (selectedTeeth.has(mesh.userData.tooth)) selectedTeeth.delete(mesh.userData.tooth);
            else selectedTeeth.add(mesh.userData.tooth);
            renderSelection();
            repaintAll();
            return;
        }

        selected = mesh;
        syncSelectedTooth(mesh.userData.tooth, true, true);
        renderToothPanel(mesh.userData);
        repaintAll();
    });

    function bindHybridOdontoListeners() {
        if (!window.HybridOdonto?.on || window.__hybridOdontoSelectionBound) return;

        window.__hybridOdontoSelectionBound = true;
        window.HybridOdonto.on('selection:changed', (tooth) => {
            if (!tooth) return;
            if (state.selectedTooth === Number(tooth) && selected?.userData?.tooth === Number(tooth)) return;
            syncSelectedTooth(tooth, true, false);
        });
    }

    bindHybridOdontoListeners();
    window.addEventListener('hybrid-odonto:ready', bindHybridOdontoListeners, { once: true });

    document.getElementById('reset3d')?.addEventListener('click', () => {
        controls?.reset();
        camera.position.set(0, 8, 26);
        if (controls) controls.target.set(0, 0, 0);
    });
    document.getElementById('toggleRotate')?.addEventListener('click', () => {
        if (controls) controls.autoRotate = !controls.autoRotate;
    });
    document.getElementById('toggleMulti')?.addEventListener('click', (e) => {
        multiMode = !multiMode;
        e.currentTarget.classList.toggle('btn-primary', multiMode);
        e.currentTarget.classList.toggle('btn-outline-secondary', !multiMode);
    });

    document.querySelectorAll('[data-jaw]').forEach((btn) => {
        btn.addEventListener('click', () => {
            jawMode = btn.getAttribute('data-jaw');
            repaintAll();
        });
    });

    document.getElementById('annotateTooth')?.addEventListener('click', () => {
        const targetTooth = selected?.userData?.tooth || Array.from(selectedTeeth)[0];
        if (!targetTooth) return alert('Selectionnez une dent d abord.');
        const note = prompt(`Annotation pour dent ${targetTooth}:`);
        if (!note) return;
        annotations[targetTooth] = note;
        renderAnnotations();
    });

    document.querySelectorAll('.legend-chip-modern').forEach((chip) => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('.legend-chip-modern').forEach((c) => c.classList.remove('active'));
            chip.classList.add('active');
            statusFilter = chip.getAttribute('data-filter') || 'all';
            repaintAll();
        });
    });
    
    // Modern Tooth Panel Interactions
    const closeToothPanelBtn = document.getElementById('closeToothPanel');
    const addProcedureBtn = document.getElementById('addProcedureBtn');
    const viewHistoryBtn = document.getElementById('viewHistoryBtn');
    const closeProcedureFormBtn = document.getElementById('closeProcedureForm');
    const procedureFormContainer = document.getElementById('procedureFormContainer');
    const procedureForm = document.getElementById('procedureForm');
    const procedureAutocomplete = document.getElementById('procedureAutocomplete');
    const procedureDropdown = document.getElementById('procedureDropdown');
    const procedureConsultation = document.getElementById('procedureConsultation');
    const addConsultationBtn = document.getElementById('addConsultationBtn');
    const procedureCodeInput = document.getElementById('procedureCode');
    const procedureToothStatusInput = document.getElementById('procedureToothStatus');
    const procedurePriceInput = document.getElementById('procedurePrice');
    const procedureStatusSelect = document.getElementById('procedureStatus');
    const submitProcedureBtn = document.getElementById('submitProcedureBtn');
    
    let selectedProcedureData = null;
    let procedureFormData = null;
    
    // Close tooth panel
    closeToothPanelBtn?.addEventListener('click', () => {
        selected = null;
        repaintAll();
        document.getElementById('toothNumberDisplay').textContent = '--';
        document.getElementById('toothTitle').textContent = 'Sélectionnez une dent';
        document.getElementById('toothState').textContent = 'Cliquez sur une dent pour commencer';
        document.getElementById('addProcedureBtn').disabled = true;
        document.getElementById('viewHistoryBtn').disabled = true;
        procedureFormContainer.style.display = 'none';
    });
    
    // Add procedure button
    addProcedureBtn?.addEventListener('click', async () => {
        if (!selected) return;
        
        // Show form
        procedureFormContainer.style.display = 'block';

        // Reload consultations so the latest saved entry is immediately available
        await refreshProcedureConsultations(procedureConsultation?.value || null);
        
        // Focus on autocomplete
        procedureAutocomplete?.focus();
    });
    
    // View history button (scroll to history section)
    viewHistoryBtn?.addEventListener('click', () => {
        document.getElementById('toothHistorySection')?.scrollIntoView({ behavior: 'smooth' });
    });
    
    // Close procedure form
    closeProcedureFormBtn?.addEventListener('click', () => {
        procedureFormContainer.style.display = 'none';
        procedureForm?.reset();
        selectedProcedureData = null;
    });
    
    // Populate consultation select
    function populateConsultationSelect(consultations, preferredConsultationId = null) {
        if (!procedureConsultation) return;
        
        if (!consultations || consultations.length === 0) {
            procedureConsultation.innerHTML = '<option value="">Aucune consultation</option>';
            document.getElementById('consultationHint').textContent = 'Créez une nouvelle consultation pour ajouter un acte';
            return;
        }
        
        // Find consultation with status "en_soin" or use most recent
        const defaultConsultation = consultations.find(c => String(c.id) === String(preferredConsultationId))
            || consultations.find(c => c.consultation_status === 'en_soin')
            || consultations[0];
        
        procedureConsultation.innerHTML = `
            <option value="">-- Sélectionner une consultation --</option>
            ${consultations.map(c => `
                <option value="${c.id}" ${c.id === defaultConsultation.id ? 'selected' : ''}>
                    ${new Date(c.consultation_date).toLocaleDateString('fr-FR')} - ${c.consultation_reason || c.consultation_type || 'Consultation'}
                </option>
            `).join('')}
        `;
        
        document.getElementById('consultationHint').textContent = `${consultations.length} consultation(s) disponible(s)`;
    }

    async function refreshProcedureConsultations(preferredConsultationId = null) {
        try {
            procedureFormData = await fetchProcedureFormData();
            populateConsultationSelect(procedureFormData.consultations, preferredConsultationId);

            if (preferredConsultationId && procedureConsultation) {
                procedureConsultation.value = String(preferredConsultationId);
            }
        } catch (error) {
            console.error('Impossible de rafraichir les consultations', error);
        }
    }
    
    // Add consultation button
    addConsultationBtn?.addEventListener('click', () => {
        const consultationModalEl = document.getElementById('consultationModal');
        if (!consultationModalEl) return;

        if (window.bootstrap?.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(consultationModalEl);
            modal.show();
            consultationModalEl.addEventListener('shown.bs.modal', () => {
                consultationModalEl.querySelector('[name="consultation_date"]')?.focus();
            }, { once: true });
            return;
        }

        consultationModalEl.classList.add('show');
        consultationModalEl.style.display = 'block';
    });
    
    // Autocomplete functionality
    let autocompleteTimeout = null;
    procedureAutocomplete?.addEventListener('input', (e) => {
        const query = e.target.value.trim().toLowerCase();
        
        clearTimeout(autocompleteTimeout);
        
        if (query.length < 2) {
            procedureDropdown.classList.remove('show');
            return;
        }
        
        autocompleteTimeout = setTimeout(() => {
            if (!procedureFormData || !procedureFormData.procedures) return;
            
            const filtered = procedureFormData.procedures.filter(p => 
                p.name.toLowerCase().includes(query) || 
                p.code.toLowerCase().includes(query) ||
                p.category.toLowerCase().includes(query)
            );
            
            if (filtered.length === 0) {
                procedureDropdown.innerHTML = '<div class="autocomplete-item"><span class="autocomplete-item-name">Aucun résultat</span></div>';
            } else {
                procedureDropdown.innerHTML = filtered.map((p, idx) => `
                    <div class="autocomplete-item" data-idx="${idx}" data-code="${p.code}" data-name="${p.name}" data-status="${p.default_status}" data-tooth-status="${p.tooth_status || ''}">
                        <div class="autocomplete-item-code">${p.code}</div>
                        <div class="autocomplete-item-name">${p.name}</div>
                        <div class="autocomplete-item-category">${p.category}</div>
                    </div>
                `).join('');
                
                // Add click handlers
                procedureDropdown.querySelectorAll('.autocomplete-item').forEach(item => {
                    item.addEventListener('click', () => {
                        selectProcedure(item);
                    });
                });
            }
            
            procedureDropdown.classList.add('show');
        }, 150);
    });
    
    // Select procedure from autocomplete
    function selectProcedure(item) {
        const name = item.dataset.name;
        const code = item.dataset.code;
        const status = item.dataset.status;
        const toothStatus = item.dataset.toothStatus;
        
        procedureAutocomplete.value = name;
        procedureCodeInput.value = code;
        procedureToothStatusInput.value = toothStatus;
        
        if (status) {
            procedureStatusSelect.value = status;
        }
        
        procedureDropdown.classList.remove('show');
        selectedProcedureData = { name, code, status, toothStatus };
        
        // Focus on price input
        procedurePriceInput?.focus();
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.autocomplete-wrapper')) {
            procedureDropdown?.classList.remove('show');
        }
    });
    
    // Procedure form submission
    procedureForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!selected) {
            alert('Veuillez sélectionner une dent');
            return;
        }
        
        const consultationId = procedureConsultation?.value;
        if (!consultationId) {
            alert('Veuillez sélectionner une consultation');
            procedureConsultation?.focus();
            return;
        }
        
        const name = procedureAutocomplete?.value.trim();
        if (!name) {
            alert('Veuillez sélectionner un acte');
            procedureAutocomplete?.focus();
            return;
        }
        
        const price = parseFloat(procedurePriceInput?.value) || 0;
        const notes = document.getElementById('procedureNotes')?.value.trim() || '';
        const status = procedureStatusSelect?.value || 'completed';
        const patientId = getCareSelectedPatientId();

        if (!patientId) {
            alert('Veuillez sélectionner un patient');
            return;
        }
        
        // Disable button
        submitProcedureBtn.disabled = true;
        submitProcedureBtn.innerHTML = '<svg class="animate-spin" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Enregistrement...';
        
        try {
            const response = await fetch(`${careProcedurePatientsBaseUrl}/${patientId}/procedures`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    tooth_number: selected.userData.tooth,
                    consultation_id: consultationId,
                    name: name,
                    procedure_code: procedureCodeInput.value || name.toUpperCase().substring(0, 10),
                    status: status,
                    price: price,
                    notes: notes,
                    specialty_id: procedureFormData?.default_specialty_id || 1
                })
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Erreur lors de la création');
            }
            
            // Success
            showToast('Acte créé avec succès !', 'success');
            
            // Update 3D view
            if (selectedProcedureData?.toothStatus) {
                updateToothStatusIn3DView(selected.userData.tooth, name, status, selectedProcedureData.toothStatus);
            }
            
            // Close form and reset
            procedureFormContainer.style.display = 'none';
            procedureForm.reset();
            selectedProcedureData = null;
            
            // Refresh tooth panel
            renderToothPanel(selected.userData);
            
        } catch (error) {
            console.error('Error:', error);
            alert('Erreur : ' + error.message);
        } finally {
            submitProcedureBtn.disabled = false;
            submitProcedureBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/></svg> Enregistrer l\'acte';
        }
    });

    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('studyFileInput');
    const fileNameBox = document.getElementById('dropFileName');
    if (dropZone && fileInput) {
        const setFile = (file) => {
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            fileNameBox.textContent = `Fichier: ${file.name}`;
        };
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            const file = e.dataTransfer.files?.[0];
            if (file) setFile(file);
        });
        fileInput.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            if (file) fileNameBox.textContent = `Fichier: ${file.name}`;
        });
    }

    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const close = document.getElementById('lightboxClose');
    document.querySelectorAll('.btn-preview').forEach((btn) => {
        btn.addEventListener('click', () => {
            lightboxImage.src = btn.getAttribute('data-src');
            lightbox.classList.add('show');
        });
    });
    close?.addEventListener('click', () => lightbox.classList.remove('show'));
    lightbox?.addEventListener('click', (e) => { if (e.target === lightbox) lightbox.classList.remove('show'); });

    repaintAll();
    renderSelection();
    renderAnnotations();

    if (!supportsCapsule) {
        const state = document.getElementById('toothState');
        if (state) state.textContent = 'Mode compatibilite active: rendu simplifie des dents (navigateur legacy).';
    }

    function animate() {
        if (controls) controls.update();
        renderer.render(scene, camera);
        animationFrameId = requestAnimationFrame(animate);
    }
    animate();

    const resizeHandler = () => {
        const w = mount.clientWidth, h = mount.clientHeight;
        if (w <= 0 || h <= 0) return;
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
    };

    window.addEventListener('resize', resizeHandler);
    const resizeObserver = new ResizeObserver(() => resizeHandler());
    resizeObserver.observe(mount);

    window.__careModule3Cleanup = () => {
        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        window.removeEventListener('resize', resizeHandler);
        resizeObserver.disconnect();
        renderer.dispose();
        mount.innerHTML = '';
        window.__careModule3Cleanup = null;
    };
})();
}
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
    const canvas = document.getElementById('signaturePad');
    const hidden = document.getElementById('signatureDataInput');
    const clearBtn = document.getElementById('clearSignature');
    const submitBtn = document.getElementById('signSubmitBtn');
    if (!canvas || !hidden) return;

    const ratio = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    const ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#0f172a';

    let drawing = false;
    const point = (e) => {
        const r = canvas.getBoundingClientRect();
        const p = e.touches ? e.touches[0] : e;
        return { x: p.clientX - r.left, y: p.clientY - r.top };
    };

    const start = (e) => {
        drawing = true;
        const p = point(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    };
    const move = (e) => {
        if (!drawing) return;
        e.preventDefault();
        const p = point(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
    };
    const end = () => {
        drawing = false;
        hidden.value = canvas.toDataURL('image/png');
    };

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    window.addEventListener('touchend', end);

    clearBtn?.addEventListener('click', () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hidden.value = '';
    });

    submitBtn?.closest('form')?.addEventListener('submit', (e) => {
        if (!hidden.value) {
            e.preventDefault();
            alert('Veuillez signer sur la tablette avant validation.');
        }
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/legacy/build/pdf.min.js"></script>
<script>
window.pdfjsLib?.GlobalWorkerOptions && (window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/legacy/build/pdf.worker.min.js');
</script>
<script>
(() => {
    const form = document.getElementById('rxForm');
    if (!form) return;

    const templateSelect = document.getElementById('rxTemplateSelect');
    const templateNote = document.getElementById('rxTemplateNote');
    const applyTemplateBtn = document.getElementById('applyRxTemplate');
    const clearAllBtn = document.getElementById('rxClearAllBtn');
    const searchInput = document.getElementById('rxMedicationSearch');
    const searchResults = document.getElementById('rxSearchResults');
    const itemsContainer = document.getElementById('rxItemsContainer');
    const itemsJsonInput = document.getElementById('rxItemsJson');
    const selectedTemplateIdInput = document.getElementById('rxSelectedTemplateId');
    const blockingAlertWrap = document.getElementById('rxBlockingAlertWrap');
    const warningAlertWrap = document.getElementById('rxWarningAlertWrap');
    const blockingAlert = document.getElementById('rxBlockingAlert');
    const warningAlert = document.getElementById('rxWarningAlert');
    const notesInput = document.getElementById('rxNotesInput');
    const paperItems = document.getElementById('rxPaperItems');
    const pdfCanvas = document.getElementById('rxPdfCanvas');
    const pdfStatus = document.getElementById('rxPdfStatus');
    const renderPdfBtn = document.getElementById('rxRenderPdfBtn');
    const printBtn = document.getElementById('rxPrintBtn');
    const paperDate = document.getElementById('rxPaperDate');
    const paperPatientName = document.getElementById('rxPaperPatientName');
    const paperPatientAge = document.getElementById('rxPaperPatientAge');
    const paperMrn = document.getElementById('rxPaperMrn');
    const paperInitials = document.getElementById('rxPaperPatientInitials');
    const patientPreview = <?php echo json_encode($rxPatientPreview, 15, 512) ?>;
    const catalog = <?php echo json_encode($rxCatalogPayload, 15, 512) ?>;
    const templates = <?php echo json_encode($rxTemplatePayload, 15, 512) ?>;
    const allergies = <?php echo json_encode(($allergies ?? collect())->map(fn($v) => mb_strtolower((string) $v))->values(), 15, 512) ?>;
    const riskTags = <?php echo json_encode(($riskTags ?? collect())->map(fn($v) => mb_strtolower((string) $v))->values(), 15, 512) ?>;
    const historyTags = <?php echo json_encode(collect($selectedPatient?->medical_history ?? [])->map(fn($v) => mb_strtolower((string) $v))->values(), 15, 512) ?>;
    const unitOptions = ['gelule', 'comprime', 'gouttes', 'pulverisation', 'ampoule', 'sachet', 'flacon'];
    const freqOptions = ['Matin/Midi/Soir', 'Matin/Soir', 'Soir', 'Au besoin'];
    let rxItems = [];
    let previewPdfUrl = null;
    let signatureDataUrl = '';
    let sortableInstance = null;

    function normalize(value) {
        return String(value ?? '').toLowerCase();
    }

    function uid() {
        return `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    }

    function medicationById(id) {
        return catalog.find((entry) => Number(entry.id) === Number(id));
    }

    function buildItem(source = {}) {
        const medication = source.medication_id ? medicationById(source.medication_id) : null;
        return {
            uid: source.uid || uid(),
            medication_id: source.medication_id || medication?.id || null,
            medication_name: source.medication_name || medication?.name || '',
            category: source.category || medication?.category || '',
            strength: source.strength || medication?.strength || '',
            dosage: source.dosage || '1',
            unit: source.unit || medication?.default_unit || 'comprime',
            frequency: source.frequency || medication?.default_frequency || 'Matin/Midi/Soir',
            duration_days: Number(source.duration_days || medication?.default_duration_days || 3),
            instructions: source.instructions || '',
        };
    }

    function syncTemplateNote() {
        const template = templates.find((entry) => String(entry.id) === String(templateSelect.value));
        if (template) {
            templateNote.textContent = template.notes || `${template.items?.length || 0} medicaments preconfigures.`;
            selectedTemplateIdInput.value = String(template.id);
        } else {
            templateNote.textContent = 'Selectionnez un modele puis appuyez sur appliquer.';
            selectedTemplateIdInput.value = '';
        }
    }

    function addItem(source) {
        rxItems.push(buildItem(source));
        renderAll();
        scrollToLatestItem();
    }

    function clearAll() {
        rxItems = [];
        notesInput.value = '';
        searchInput.value = '';
        searchResults.innerHTML = '';
        searchResults.classList.add('d-none');
        signatureDataUrl = '';
        templateSelect.value = '';
        selectedTemplateIdInput.value = '';
        syncTemplateNote();
        if (window.rxSignaturePadApi?.clear) {
            window.rxSignaturePadApi.clear();
        }
        renderAll();
    }

    function scrollToLatestItem() {
        const lastCard = itemsContainer.querySelector('.rx-item-card:last-child');
        lastCard?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function moveItem(fromIndex, toIndex) {
        if (fromIndex === toIndex) return;
        const item = rxItems.splice(fromIndex, 1)[0];
        rxItems.splice(toIndex, 0, item);
    }

    function renderItems() {
        if (!rxItems.length) {
            itemsContainer.innerHTML = `<div class="rx-empty-state"><div class="rx-empty-icon"><i class="ti ti-pill"></i></div><div>Recherchez un medicament pour construire l ordonnance.</div></div>`;
            return;
        }

        itemsContainer.innerHTML = rxItems.map((item, index) => `
            <article class="rx-item-card" data-rx-id="${item.uid}">
                <div class="rx-item-header">
                    <div class="rx-item-title">
                        <strong>${item.medication_name || 'Medicament'}</strong>
                        <div class="rx-item-meta">${item.category || '-'} ${item.strength ? '• ' + item.strength : ''}</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm rx-drag-handle" title="Reordonner">
                            <i class="ti ti-drag-drop"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rx-remove-btn" data-remove-index="${index}" title="Supprimer">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                </div>
                <div class="rx-item-grid">
                    <div>
                        <label class="form-label rx-mini-label">Posologie</label>
                        <input class="form-control" data-field="dosage" data-index="${index}" value="${escapeHtml(item.dosage)}" placeholder="1">
                    </div>
                    <div>
                        <label class="form-label rx-mini-label">Durée</label>
                        <input class="form-control" type="number" min="1" data-field="duration_days" data-index="${index}" value="${item.duration_days}">
                    </div>
                    <div>
                        <label class="form-label rx-mini-label">QSP</label>
                        <input class="form-control" data-field="instructions" data-index="${index}" value="${escapeHtml(item.instructions)}" placeholder="Prise après repas, etc.">
                    </div>
                    <div>
                        <label class="form-label rx-mini-label">Forme</label>
                        <select class="form-select" data-field="unit" data-index="${index}">${unitOptions.map((unit) => `<option value="${unit}" ${item.unit === unit ? 'selected' : ''}>${unit}</option>`).join('')}</select>
                    </div>
                </div>
                <div class="rx-item-secondary">
                    <div>
                        <label class="form-label rx-mini-label">Frequence</label>
                        <select class="form-select" data-field="frequency" data-index="${index}">${freqOptions.map((frequency) => `<option value="${frequency}" ${item.frequency === frequency ? 'selected' : ''}>${frequency}</option>`).join('')}</select>
                    </div>
                    <div class="rx-template-note">Ordre #${index + 1} - glisser pour reordonner</div>
                </div>
            </article>
        `).join('');

        itemsContainer.querySelectorAll('[data-remove-index]').forEach((button) => {
            button.addEventListener('click', () => {
                rxItems.splice(Number(button.dataset.removeIndex), 1);
                renderAll();
            });
        });

        itemsContainer.querySelectorAll('[data-field]').forEach((field) => {
            const syncField = () => {
                const item = rxItems[Number(field.dataset.index)];
                if (!item) return;
                if (field.dataset.field === 'duration_days') {
                    item[field.dataset.field] = Number(field.value || 0);
                } else {
                    item[field.dataset.field] = field.value;
                }
                renderPreviewOnly();
                syncPayload();
            };
            field.addEventListener('input', syncField);
            field.addEventListener('change', syncField);
        });

        if (window.Sortable) {
            sortableInstance?.destroy?.();
            sortableInstance = window.Sortable.create(itemsContainer, {
                animation: 160,
                handle: '.rx-drag-handle',
                ghostClass: 'rx-drag-ghost',
                onEnd: () => {
                    const order = Array.from(itemsContainer.querySelectorAll('.rx-item-card')).map((card) => card.dataset.rxId);
                    rxItems.sort((left, right) => order.indexOf(left.uid) - order.indexOf(right.uid));
                    renderAll();
                },
            });
        }
    }

    function renderPaperPreview() {
        if (!paperItems) return;

        if (!rxItems.length) {
            paperItems.innerHTML = `<div class="rx-empty-state rx-empty-state-compact"><div class="rx-empty-icon"><i class="ti ti-file-text"></i></div><div>L ordonnance se met a jour en temps reel.</div></div>`;
            return;
        }

        paperItems.innerHTML = rxItems.map((item, index) => `
            <article class="rx-paper-item">
                <div class="rx-paper-item-head">
                    <div class="rx-paper-item-name">${index + 1}. ${escapeHtml(item.medication_name)}</div>
                    <div class="rx-paper-item-badges">
                        <span class="rx-paper-badge">${escapeHtml(item.dosage || '1')}</span>
                        <span class="rx-paper-badge">${escapeHtml(formatDuration(item.duration_days))}</span>
                    </div>
                </div>
                <div class="rx-paper-item-line"><strong>Forme:</strong> ${escapeHtml(item.unit || '-')} • <strong>Frequence:</strong> ${escapeHtml(item.frequency || '-')}</div>
                <div class="rx-paper-item-line"><strong>QSP:</strong> ${escapeHtml(item.instructions || '-')}</div>
            </article>
        `).join('');
    }

    function renderPdfPreviewState(message = 'Apercu PDF actualise.') {
        if (pdfStatus) pdfStatus.textContent = message;
    }

    function syncPaperHeader() {
        if (!patientPreview) return;
        paperPatientName.textContent = patientPreview.full_name || 'Patient non selectionne';
        paperPatientAge.textContent = `${patientPreview.age ?? '-'} ans`;
        paperMrn.textContent = patientPreview.mrn || '-';
        paperInitials.textContent = `${String(patientPreview.first_name || '').slice(0, 1)}${String(patientPreview.last_name || '').slice(0, 1)}`.toUpperCase() || '--';
        paperDate.textContent = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date());
    }

    function syncPayload() {
        itemsJsonInput.value = JSON.stringify(rxItems.map((item) => ({
            medication_id: item.medication_id,
            medication_name: item.medication_name,
            dosage: item.dosage,
            unit: item.unit,
            frequency: item.frequency,
            duration_days: item.duration_days,
            instructions: item.instructions,
        })));
    }

    function validateSafety() {
        const blocking = [];
        const warnings = [];

        rxItems.forEach((item) => {
            const medication = medicationById(item.medication_id);
            const name = normalize(item.medication_name);
            const allergenKeywords = (medication?.allergen_keywords || []).map(normalize);
            const contraindicationTags = (medication?.contraindication_tags || []).map(normalize);

            allergenKeywords.forEach((keyword) => {
                if (!keyword) return;
                if (allergies.some((entry) => entry.includes(keyword) || keyword.includes(entry)) || name.includes(keyword)) {
                    blocking.push(`Alerte allergie: ${item.medication_name} incompatible (${keyword}).`);
                }
            });

            contraindicationTags.forEach((tag) => {
                const hit = riskTags.some((entry) => entry.includes(tag) || tag.includes(entry)) || historyTags.some((entry) => entry.includes(tag) || tag.includes(entry));
                if (hit) warnings.push(`Contre-indication: ${item.medication_name} avec terrain ${tag}.`);
            });
        });

        blockingAlertWrap.classList.toggle('d-none', blocking.length === 0);
        warningAlertWrap.classList.toggle('d-none', warnings.length === 0);
        blockingAlert.textContent = blocking.join(' | ');
        warningAlert.textContent = warnings.join(' | ');
        return { blocking, warnings };
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDuration(days) {
        const value = Number(days || 0);
        if (!value) return '0 jour';
        return `${value} ${value > 1 ? 'jours' : 'jour'}`;
    }

    function renderAll() {
        syncTemplateNote();
        syncPaperHeader();
        syncPayload();
        validateSafety();
        renderItems();
        renderPaperPreview();
        renderPdfFromCurrentState();
    }

    function renderPreviewOnly() {
        syncPayload();
        validateSafety();
        renderPaperPreview();
    }

    function filterMedicationSearch(term) {
        const normalized = normalize(term);
        if (normalized.length < 2) return [];
        return catalog.filter((entry) => {
            const haystack = [entry.name, entry.category, entry.strength].map(normalize).join(' ');
            return haystack.includes(normalized);
        }).slice(0, 8);
    }

    function renderSearchResults(rows) {
        if (!rows.length) {
            searchResults.innerHTML = `<div class="rx-empty-state rx-empty-state-compact"><div class="rx-empty-icon"><i class="ti ti-search"></i></div><div>Aucun resultat pertinent.</div></div>`;
            searchResults.classList.remove('d-none');
            return;
        }

        searchResults.innerHTML = rows.map((entry) => `
            <button type="button" class="rx-search-item" data-med-id="${entry.id}">
                <span class="rx-search-item-title">${escapeHtml(entry.name)} ${entry.strength ? escapeHtml(entry.strength) : ''}</span>
                <span class="rx-search-item-meta">${escapeHtml(entry.category || 'Catalogue local')}</span>
            </button>
        `).join('');
        searchResults.classList.remove('d-none');
        searchResults.querySelectorAll('[data-med-id]').forEach((button) => {
            button.addEventListener('click', () => {
                const med = rows.find((item) => String(item.id) === String(button.dataset.medId));
                if (!med) return;
                addItem({
                    medication_id: med.id,
                    medication_name: med.name,
                    category: med.category,
                    strength: med.strength,
                    dosage: '1',
                    unit: med.default_unit || 'comprime',
                    frequency: med.default_frequency || 'Matin/Midi/Soir',
                    duration_days: med.default_duration_days || 3,
                    instructions: '',
                });
                searchInput.value = '';
                searchResults.classList.add('d-none');
            });
        });
    }

    async function searchMedication(term) {
        renderSearchResults(filterMedicationSearch(term));
    }

    function applySelectedTemplate() {
        const templateId = templateSelect.value || templates[0]?.id || '';
        const template = templates.find((entry) => String(entry.id) === String(templateId));
        if (!template) {
            window.__careShowToast?.('Aucun modele favori disponible.', 'warning');
            return;
        }

        templateSelect.value = String(template.id);
        rxItems = (template.items || []).map((item) => buildItem(item));
        renderAll();
        window.__careShowToast?.(`Modele "${template.name}" applique.`, 'success');
    }

    function createPdfBlob() {
        const jsPDFClass = window.jspdf?.jsPDF;
        if (!jsPDFClass) return null;

        const doc = new jsPDFClass({ unit: 'mm', format: 'a5', orientation: 'portrait', compress: true });
        const pageWidth = 148;
        const safeWidth = pageWidth - 16;
        doc.setFillColor(255, 255, 255);
        doc.rect(0, 0, pageWidth, 210, 'F');
        doc.setFillColor(37, 99, 235);
        doc.roundedRect(8, 8, 132, 22, 4, 4, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(15);
        doc.text('Cabinet Dentaire MediOffice', 14, 16);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        doc.text('Ordonnance A5 / Apercu final', 14, 22);
        doc.text(`MRN: ${patientPreview?.mrn || '-'}`, 104, 16, { align: 'left' });
        doc.text(`Date: ${new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date())}`, 104, 22, { align: 'left' });

        doc.setTextColor(15, 23, 42);
        doc.setDrawColor(219, 234, 254);
        doc.roundedRect(8, 34, 132, 18, 4, 4, 'S');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10);
        doc.text(patientPreview?.full_name || 'Patient non selectionne', 12, 42);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.text(`${patientPreview?.age ?? '-'} ans`, 12, 48);
        doc.text('Adresse, notes et antecedents masques par defaut', 68, 48, { align: 'right' });

        let y = 60;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.text('Prescription', 8, y);
        y += 4;
        doc.setDrawColor(226, 232, 240);

        if (!rxItems.length) {
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8);
            doc.text('Aucun medicament saisi.', 8, y + 6);
        } else {
            rxItems.forEach((item, index) => {
                y += 8;
                doc.setFillColor(248, 251, 255);
                doc.roundedRect(8, y - 5, safeWidth, 20, 3, 3, 'F');
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(8.8);
                doc.text(`${index + 1}. ${item.medication_name}`, 11, y);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(7.5);
                doc.text(`Posologie: ${item.dosage || '-'} | Duree: ${formatDuration(item.duration_days)} | QSP: ${item.instructions || '-'}`, 11, y + 5);
                doc.text(`Forme: ${item.unit || '-'} | Frequence: ${item.frequency || '-'}`, 11, y + 10);
                y += 16;
                if (y > 172) {
                    doc.addPage('a5', 'portrait');
                    y = 16;
                }
            });
        }

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.2);
        doc.setTextColor(100, 116, 139);
        doc.text('Impression optimise A5. Les champs Adresse, Notes et Antecedents sont masques par defaut.', 8, 194, { maxWidth: 132 });
        doc.setDrawColor(148, 163, 184);
        doc.line(88, 184, 136, 184);
        doc.text('Signature praticien', 101, 188, { align: 'center' });

        return doc.output('blob');
    }

    async function renderPdfFromCurrentState() {
        if (!window.pdfjsLib || !pdfCanvas) {
            renderPdfPreviewState('Visionneuse PDF.js indisponible.');
            return;
        }

        const blob = createPdfBlob();
        if (!blob) {
            renderPdfPreviewState('Generation PDF indisponible.');
            return;
        }

        if (previewPdfUrl) {
            URL.revokeObjectURL(previewPdfUrl);
        }
        previewPdfUrl = URL.createObjectURL(blob);
        renderPdfPreviewState('Apercu PDF genere.');

        const pdf = await window.pdfjsLib.getDocument({ url: previewPdfUrl }).promise;
        const page = await pdf.getPage(1);
        const viewport = page.getViewport({ scale: 1.55 });
        const context = pdfCanvas.getContext('2d');
        pdfCanvas.width = viewport.width;
        pdfCanvas.height = viewport.height;
        pdfCanvas.style.height = `${Math.round(viewport.height / window.devicePixelRatio)}px`;
        await page.render({ canvasContext: context, viewport }).promise;
    }

    function syncSignaturePad() {
        const sigCanvas = document.getElementById('rxSignaturePad');
        const clearSig = document.getElementById('clearRxSignature');
        if (!sigCanvas) return;

        const ratio = window.devicePixelRatio || 1;
        const resize = () => {
            const rect = sigCanvas.getBoundingClientRect();
            sigCanvas.width = rect.width * ratio;
            sigCanvas.height = rect.height * ratio;
        };

        resize();
        const ctx = sigCanvas.getContext('2d');
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#0f172a';

        let drawing = false;
        const position = (event) => {
            const rect = sigCanvas.getBoundingClientRect();
            const pointer = event.touches?.[0] || event;
            return { x: pointer.clientX - rect.left, y: pointer.clientY - rect.top };
        };
        const start = (event) => { drawing = true; const point = position(event); ctx.beginPath(); ctx.moveTo(point.x, point.y); event.preventDefault(); };
        const move = (event) => { if (!drawing) return; const point = position(event); ctx.lineTo(point.x, point.y); ctx.stroke(); signatureDataUrl = sigCanvas.toDataURL('image/png'); event.preventDefault(); };
        const end = () => { drawing = false; signatureDataUrl = sigCanvas.toDataURL('image/png'); };

        sigCanvas.addEventListener('mousedown', start);
        sigCanvas.addEventListener('mousemove', move);
        window.addEventListener('mouseup', end);
        sigCanvas.addEventListener('touchstart', start, { passive: false });
        sigCanvas.addEventListener('touchmove', move, { passive: false });
        window.addEventListener('touchend', end);

        clearSig?.addEventListener('click', () => {
            ctx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
            signatureDataUrl = '';
            document.getElementById('rxSignatureData').value = '';
        });

        window.rxSignaturePadApi = {
            clear() {
                ctx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
                signatureDataUrl = '';
                document.getElementById('rxSignatureData').value = '';
            },
        };
    }

    form.addEventListener('submit', async (event) => {
        syncPayload();
        document.getElementById('rxSignatureData').value = signatureDataUrl;
        const { blocking } = validateSafety();
        if (blocking.length) {
            event.preventDefault();
            window.__careShowToast?.('Prescription bloquee par le controle de securite.', 'danger');
        }
    });

    searchInput.addEventListener('input', () => searchMedication(searchInput.value));
    applyTemplateBtn?.addEventListener('click', applySelectedTemplate);
    clearAllBtn?.addEventListener('click', clearAll);
    templateSelect?.addEventListener('change', syncTemplateNote);
    renderPdfBtn?.addEventListener('click', () => renderPdfFromCurrentState());
    printBtn?.addEventListener('click', () => {
        const blob = createPdfBlob();
        if (!blob) return;
        const url = URL.createObjectURL(blob);
        const popup = window.open(url, '_blank', 'noopener,noreferrer');
        if (!popup) {
            window.location.href = url;
            return;
        }
        popup.focus();
        window.setTimeout(() => popup.print?.(), 500);
    });

    syncSignaturePad();
    renderAll();
})();
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
    const initializeParodontogramme = () => {
        let chartEl = null, gridEl = null;
        try {
    const root = document.getElementById('parodontal');
    if (!root) return;

    const selectedPatientId = <?php echo (int) ($selectedPatientId ?? 0); ?>;
    const periodontalStoreBase = "<?php echo url('/care/module-3/patients'); ?>";
    const saveUrl = selectedPatientId ? `${periodontalStoreBase}/${selectedPatientId}/periodontal-chart` : '';
    const initialSeed = <?php echo json_encode($periodontalSeed ?? [], 15, 512) ?>;
    const initialHistory = <?php echo json_encode($periodontalHistorySeed ?? [], 15, 512) ?>;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    chartEl = document.getElementById('paroChart');
    gridEl = document.getElementById('paroTeethGrid');
    const summaryEl = document.getElementById('paroSummaryText');
    const historyEl = document.getElementById('paroHistoryList');
    const voiceEl = document.getElementById('paroVoiceFeedback');
    const focusedToothEl = document.getElementById('paroFocusedTooth');
    const focusedPointEl = document.getElementById('paroFocusedPoint');
    const sideToggle = document.getElementById('paroToggleSide');
    const microBtn = document.getElementById('paroMicroBtn');
    const saveBtn = document.getElementById('paroSaveBtn');
    const plusBtn = document.getElementById('paroPlus');
    const minusBtn = document.getElementById('paroMinus');
    const nextBtn = document.getElementById('paroNext');
    const bonePlusBtn = document.getElementById('paroBonePlus');
    const boneMinusBtn = document.getElementById('paroBoneMinus');
    const boneValueEl = document.getElementById('paroBoneValue');
    const mobilityWrap = document.getElementById('paroMobilityButtons');
    const chips = Array.from(root.querySelectorAll('[data-paro-presets]'));

    if (!chartEl || !gridEl) { console.error('Parodontogramme: éléments DOM manquants', { chartEl, gridEl }); return; }

    const toothOrder = [18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28,48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38];
    const pointOrder = ['mesial', 'central', 'distal'];
    const pointLabels = { mesial: 'M', central: 'C', distal: 'D' };
    const numberWords = { 0:'zÃ©ro',1:'un',2:'deux',3:'trois',4:'quatre',5:'cinq',6:'six',7:'sept',8:'huit',9:'neuf',10:'dix',11:'onze',12:'douze',13:'treize',14:'quatorze',15:'quinze',16:'seize',17:'dix-sept',18:'dix-huit',19:'dix-neuf',20:'vingt',21:'vingt et un',22:'vingt-deux',23:'vingt-trois',24:'vingt-quatre',25:'vingt-cinq',26:'vingt-six',27:'vingt-sept',28:'vingt-huit',29:'vingt-neuf',30:'trente',31:'trente et un',32:'trente-deux' };

    const createSide = () => ({ mesial: 0, central: 0, distal: 0, bone: null, bleeding: false, mobility: 0, plaque: false });
    const state = { side: 'vestibulaire', selectedTooth: 11, selectedPoint: 'central', teeth: {} };

    // Server-provided odontogram status (contains per-tooth procedures/status)
    const odontogramStatus = <?php echo json_encode($odontogramTeethStatus ?? [], 15, 512) ?>;

    toothOrder.forEach((tooth) => {
        state.teeth[tooth] = { vestibulaire: createSide(), linguale: createSide() };
    });

    console.log('Parodontogramme: état initialisé', Object.keys(state.teeth).length, 'dents');

    function getToothVisualFlagsParo(toothNumber) {
        const tooth = Number(toothNumber);
        const procedureCount = (odontogramStatus && odontogramStatus[tooth] && (odontogramStatus[tooth].procedures || [])).length || 0;
        const storeFlags = (window.HybridOdonto?.state?.teeth || {})[tooth]?.flags || {};
        return {
            hasProcedure: procedureCount > 0,
            recentProcedure: Boolean(storeFlags.recentProcedure),
            deepPocket: Boolean(storeFlags.deepPocket),
            bleeding: Boolean(storeFlags.bleeding),
            plaque: Boolean(storeFlags.plaque),
        };
    }

    function cloneSide(side) {
        return {
            mesial: Number(side?.mesial ?? 0),
            central: Number(side?.central ?? 0),
            distal: Number(side?.distal ?? 0),
            bone: side?.bone === null || side?.bone === undefined || side?.bone === '' ? null : Number(side.bone),
            bleeding: Boolean(side?.bleeding),
            mobility: Number(side?.mobility ?? 0),
            plaque: Boolean(side?.plaque),
        };
    }

    function normalizeRowSide(sideValue) {
        if (Array.isArray(sideValue)) {
            return {
                mesial: Number(sideValue[0] ?? 0),
                central: Number(sideValue[1] ?? 0),
                distal: Number(sideValue[2] ?? 0),
                bone: null,
                bleeding: false,
                mobility: 0,
                plaque: false,
            };
        }
        return cloneSide(sideValue);
    }

    function applySeed(seed) {
        (Array.isArray(seed) ? seed : []).forEach((row) => {
            const tooth = Number(row?.tooth);
            if (!state.teeth[tooth]) return;

            if (row?.sides && typeof row.sides === 'object' && !Array.isArray(row.sides)) {
                Object.entries(row.sides).forEach(([sideName, sideValue]) => {
                    if (state.teeth[tooth][sideName]) {
                        state.teeth[tooth][sideName] = normalizeRowSide(sideValue);
                    }
                });
                return;
            }

            const targetSide = row?.side && state.teeth[tooth][row.side] ? row.side : 'vestibulaire';
            if (Array.isArray(row?.pockets)) {
                state.teeth[tooth][targetSide] = {
                    mesial: Number(row.pockets[0] ?? 0),
                    central: Number(row.pockets[1] ?? 0),
                    distal: Number(row.pockets[2] ?? 0),
                    bone: null,
                    bleeding: String(row?.meta ?? '').toLowerCase().includes('b1'),
                    mobility: 0,
                    plaque: String(row?.meta ?? '').toLowerCase().includes('p1'),
                };
                return;
            }

            state.teeth[tooth][targetSide] = normalizeRowSide(row?.measurements || row?.values || row?.data || row);
        });
    }

    applySeed(initialSeed);

    console.log('Parodontogramme: seed appliqué', { seedCount: initialSeed?.length ?? 0, state });

    function activeSideData() {
        return state.teeth[state.selectedTooth][state.side];
    }

    function setMessage(message) {
        voiceEl.textContent = message;
    }

    function setFocusedLabels() {
        focusedToothEl.textContent = `Dent ${state.selectedTooth}`;
        focusedPointEl.textContent = `${state.side === 'vestibulaire' ? 'Vestibulaire' : 'Linguale'} - Point ${pointLabels[state.selectedPoint]}`;
        boneValueEl.textContent = String(activeSideData().bone ?? 0);
        sideToggle.textContent = state.side === 'vestibulaire' ? 'Vestibulaire' : 'Linguale';
    }

    function depthStatus(value) {
        if (value >= 5) return 'red';
        if (value >= 4) return 'orange';
        return 'green';
    }

    function colorForValue(value) {
        return { green: '#16a34a', orange: '#f59e0b', red: '#dc2626' }[depthStatus(value)] || '#16a34a';
    }

    function computeSummary() {
        let totalPoints = 0;
        let sum = 0;
        let bleeding = 0;
        let plaque = 0;
        let mobility = 0;
        let deepPockets = 0;

        toothOrder.forEach((tooth) => {
            ['vestibulaire', 'linguale'].forEach((sideName) => {
                const side = state.teeth[tooth][sideName];
                const values = [side.mesial, side.central, side.distal];
                totalPoints += values.length;
                sum += values.reduce((acc, val) => acc + Number(val || 0), 0);
                bleeding += side.bleeding ? 1 : 0;
                plaque += side.plaque ? 1 : 0;
                mobility += Number(side.mobility || 0);
                if (Math.max(...values) >= 5) deepPockets += 1;
            });
        });

        const avg = totalPoints ? (sum / totalPoints).toFixed(1) : '0.0';
        return `Profondeur moyenne ${avg} mm | Saignement ${bleeding} secteurs | Plaque ${plaque} secteurs | Mobilite cumulÃ©e ${mobility} | ${deepPockets} secteurs Ã  poche profonde.`;
    }

    function renderHistory(items) {
        historyEl.innerHTML = items.length
            ? items.map((item) => `
                <article class="paro-history-item">
                    <strong>${item.recorded_on || ''}</strong>
                    <div class="muted small">${item.summary || 'Sans synthese'}</div>
                </article>`).join('')
            : '<div class="text-secondary">Aucun charting parodontal.</div>';
    }

    function renderTeethGrid() {
        if (!gridEl) { console.error('Parodontogramme: gridEl manquant'); return; }
        console.log('Parodontogramme: renderTeethGrid appelé', { toothCount: toothOrder.length, side: state.side });
        const active = activeSideData();
        gridEl.innerHTML = toothOrder.map((tooth) => {
            const side = state.teeth[tooth][state.side];
            const pointButtons = pointOrder.map((point) => {
                const value = side[point];
                return `
                    <button type="button" class="paro-point ${state.selectedTooth === tooth && state.selectedPoint === point ? 'active' : ''}" data-tooth="${tooth}" data-point="${point}">
                        <span class="value">${value}</span>
                        <span class="label">${pointLabels[point]}</span>
                    </button>`;
            }).join('');

            const hasProcedure = Boolean((odontogramStatus && odontogramStatus[tooth] && (odontogramStatus[tooth].procedures || []).length));
            const toothFlags = getToothVisualFlagsParo(tooth);
            return `
                <article class="paro-tooth ${state.selectedTooth === tooth ? 'is-active' : ''} ${hasProcedure ? 'has-procedure' : ''}" data-tooth-card="${tooth}">
                    <div class="paro-tooth-num"><span>Dent ${tooth}</span><span class="paro-hint">${state.side === 'vestibulaire' ? 'V' : 'L'}</span></div>
                    <div class="paro-tooth-badges">
                        <span class="paro-badge ${side.bleeding ? 'on' : ''}" data-flag="bleeding" data-tooth="${tooth}" title="Saignement">S</span>
                        <span class="paro-badge mobility-${side.mobility || 0}" data-flag="mobility" data-tooth="${tooth}" title="Mobilite">M${side.mobility}</span>
                        <span class="paro-badge ${side.plaque ? 'on' : ''}" data-flag="plaque" data-tooth="${tooth}" title="Plaque">P</span>
                        ${toothFlags.deepPocket ? '<span class="paro-badge on paro-badge-danger" title="Poche profonde">DP</span>' : ''}
                        ${hasProcedure ? '<span class="paro-procedure-dot" title="Contient au moins un acte"></span>' : ''}
                    </div>
                    <div class="paro-points">${pointButtons}</div>
                </article>`;
        }).join('');

        gridEl.querySelectorAll('[data-tooth-card]').forEach((card) => {
            card.addEventListener('click', (event) => {
                const tooth = Number(card.dataset.toothCard);
                if (!Number.isFinite(tooth)) return;
                syncSelectedTooth(tooth, true, true);
                renderAll();
                scheduleSave();
                event.stopPropagation();
            });
        });

        gridEl.querySelectorAll('[data-point]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                state.selectedTooth = Number(button.dataset.tooth);
                state.selectedPoint = button.dataset.point;
                renderAll();
                scheduleSave();
            });
            button.addEventListener('wheel', (event) => {
                event.preventDefault();
                state.selectedTooth = Number(button.dataset.tooth);
                state.selectedPoint = button.dataset.point;
                adjustDepth(event.deltaY > 0 ? -1 : 1);
            }, { passive: false });
        });

        gridEl.querySelectorAll('[data-flag]').forEach((badge) => {
            badge.addEventListener('click', (event) => {
                event.stopPropagation();
                state.selectedTooth = Number(badge.dataset.tooth);
                const flag = badge.dataset.flag;
                toggleFlag(flag);
            });
        });
    }

    function chartPointsForSide(sideName) {
        return toothOrder.map((tooth, index) => {
            const side = state.teeth[tooth][sideName];
            const avg = (Number(side.mesial || 0) + Number(side.central || 0) + Number(side.distal || 0)) / 3;
            return { tooth, x: 40 + index * 29, y: 245 - avg * 24, value: avg, side };
        });
    }

    function renderChart() {
        if (!chartEl) return;
        const points = chartPointsForSide(state.side);
        const bonePoints = toothOrder.map((tooth, index) => {
            const side = state.teeth[tooth][state.side];
            if (side.bone === null || side.bone === undefined || side.bone === '') return null;
            return { x: 40 + index * 29, y: 245 - Number(side.bone) * 24, value: Number(side.bone) };
        });

        const gridLines = Array.from({ length: 8 }, (_, i) => {
            const y = 245 - (i + 1) * 24;
            return `<line x1="30" y1="${y}" x2="950" y2="${y}" stroke="#e2e8f0" stroke-dasharray="4 4"></line><text x="8" y="${y + 4}" font-size="11" fill="#64748b">${i + 1}mm</text>`;
        }).join('');

        const path = points.map((p, index) => `${index === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ');
        const bonePath = bonePoints.filter(Boolean).map((p, index) => `${index === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ');

        const segmentRects = points.slice(1).map((point, index) => {
            const prev = points[index];
            const color = colorForValue(point.value);
            return `<line x1="${prev.x}" y1="${prev.y}" x2="${point.x}" y2="${point.y}" stroke="${color}" stroke-width="3" stroke-linecap="round"></line>`;
        }).join('');

        const toothMarks = points.map((point, index) => {
            const color = colorForValue(point.value);
            const label = toothOrder[index];
            const toothNum = toothOrder[index];
            const hasProc = odontogramStatus && odontogramStatus[toothNum] && (odontogramStatus[toothNum].procedures || []).length;
            const toothFlags = getToothVisualFlagsParo(toothNum);
            return `
                <g>
                    <circle cx="${point.x}" cy="${point.y}" r="5.5" fill="${color}" stroke="#fff" stroke-width="2"></circle>
                    ${hasProc ? `<circle cx="${point.x}" cy="${point.y - 14}" r="4" fill="#f59e0b" stroke="#fff" stroke-width="1"></circle>` : ''}
                    ${toothFlags.deepPocket ? `<rect x="${point.x - 4}" y="${point.y + 9}" width="8" height="8" rx="2" fill="#dc2626" stroke="#fff" stroke-width="1"></rect>` : ''}
                    <text x="${point.x}" y="272" text-anchor="middle" font-size="10" fill="#475569">${label}</text>
                </g>`;
        }).join('');

        const boneVisible = bonePoints.some(Boolean);
        chartEl.innerHTML = `
            <rect x="0" y="0" width="980" height="310" fill="transparent"></rect>
            ${gridLines}
            <line x1="30" y1="245" x2="950" y2="245" stroke="#94a3b8" stroke-width="1.2"></line>
            ${segmentRects}
            <path d="${path}" fill="none" stroke="rgba(15,23,42,.2)" stroke-width="2" opacity="0.25"></path>
            ${boneVisible ? `<path d="${bonePath}" fill="none" stroke="#0f766e" stroke-width="2.5" stroke-dasharray="8 6"></path>` : ''}
            ${toothMarks}
        `;
    }

    function renderFocus() {
        const side = activeSideData();
        const point = state.selectedPoint;
        const value = side[point];
        focusedToothEl.textContent = `Dent ${state.selectedTooth}`;
        focusedPointEl.textContent = `${state.side === 'vestibulaire' ? 'Vestibulaire' : 'Linguale'} - ${pointLabels[point]} : ${value} mm`;
        boneValueEl.textContent = String(side.bone ?? 0);
        sideToggle.textContent = state.side === 'vestibulaire' ? 'Vestibulaire' : 'Linguale';
        mobilityWrap.querySelectorAll('[data-mobility]').forEach((btn) => {
            btn.classList.toggle('btn-primary', Number(btn.dataset.mobility) === Number(side.mobility || 0));
            btn.classList.toggle('btn-outline-secondary', Number(btn.dataset.mobility) !== Number(side.mobility || 0));
        });
    }

    function renderSummary() {
        summaryEl.textContent = computeSummary();
    }

    function renderAll() {
        renderTeethGrid();
        renderChart();
        renderFocus();
        renderSummary();
    }

    function normalizePointName(name) {
        const normalized = String(name || '').toLowerCase();
        if (normalized.startsWith('m')) return 'mesial';
        if (normalized.startsWith('c')) return 'central';
        if (normalized.startsWith('d')) return 'distal';
        return state.selectedPoint;
    }

    function currentPointValue() {
        return activeSideData()[state.selectedPoint];
    }

    function setPointValue(value, pointName = state.selectedPoint) {
        const side = activeSideData();
        side[pointName] = Math.max(0, Math.min(9, Number(value)));
    }

    function adjustDepth(delta) {
        setPointValue(currentPointValue() + delta);
        renderAll();
        scheduleSave();
    }

    function toggleFlag(flag) {
        const side = activeSideData();
        if (flag === 'bleeding') side.bleeding = !side.bleeding;
        if (flag === 'plaque') side.plaque = !side.plaque;
        if (flag === 'mobility') side.mobility = side.mobility ? 0 : 1;
        renderAll();
        scheduleSave();
    }

    function setMobility(value) {
        activeSideData().mobility = Number(value);
        renderAll();
        scheduleSave();
    }

    function adjustBone(delta) {
        const side = activeSideData();
        const current = side.bone === null || side.bone === undefined ? 0 : Number(side.bone);
        side.bone = Math.max(0, Math.min(9, current + delta));
        renderAll();
        scheduleSave();
    }

    function cycleSelection() {
        const currentIndex = toothOrder.indexOf(state.selectedTooth);
        const pointIndex = pointOrder.indexOf(state.selectedPoint);
        if (pointIndex < pointOrder.length - 1) {
            state.selectedPoint = pointOrder[pointIndex + 1];
        } else {
            state.selectedPoint = 'mesial';
            state.selectedTooth = toothOrder[(currentIndex + 1) % toothOrder.length];
        }
        renderAll();
        scheduleSave();
    }

    function tone() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.value = 520;
            gain.gain.value = 0.03;
            oscillator.connect(gain);
            gain.connect(ctx.destination);
            oscillator.start();
            oscillator.stop(ctx.currentTime + 0.08);
        } catch (_) {}
    }

    function speak(text) {
        if ('speechSynthesis' in window) {
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'fr-FR';
            utterance.rate = 0.95;
            utterance.pitch = 0.9;
            utterance.volume = 0.8;
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(utterance);
        } else {
            tone();
        }
        setMessage(text);
    }

    function parseTranscript(transcript) {
        const text = String(transcript || '').toLowerCase();
        if (!text.trim()) return;

        const toothMatch = text.match(/dent\s*(\d{1,2})/);
        if (toothMatch) {
            const tooth = Number(toothMatch[1]);
            if (state.teeth[tooth]) syncSelectedTooth(tooth, true, true);
        }

        if (text.includes('vestibulaire')) state.side = 'vestibulaire';
        if (text.includes('linguale')) state.side = 'linguale';

        const pointMatch = text.match(/(mesial|m[Ã©e]sial|central|distal)\s*(\d)/);
        if (pointMatch) {
            state.selectedPoint = normalizePointName(pointMatch[1]);
            setPointValue(Number(pointMatch[2]));
        }

        const pocketMatch = text.match(/poche\s*(\d)/);
        if (pocketMatch) {
            setPointValue(Number(pocketMatch[1]));
        }

        const bleedMatch = text.match(/saignement\s*(oui|non)/);
        if (bleedMatch) activeSideData().bleeding = bleedMatch[1] === 'oui';

        const plaqueMatch = text.match(/plaque\s*(oui|non)/);
        if (plaqueMatch) activeSideData().plaque = plaqueMatch[1] === 'oui';

        const mobilityMatch = text.match(/mobilit[eÃ©]\s*([0-3])/);
        if (mobilityMatch) activeSideData().mobility = Number(mobilityMatch[1]);

        const boneMatch = text.match(/(?:os|osseux)\s*(\d)/);
        if (boneMatch) activeSideData().bone = Number(boneMatch[1]);

        if (text.includes('suivant')) {
            cycleSelection();
            speak('Suivant');
            return;
        }

        renderAll();
        scheduleSave();

        const toothWord = toothMatch ? (numberWords[Number(toothMatch[1])] || toothMatch[1]) : numberWords[state.selectedTooth];
        const valueWord = pocketMatch ? (numberWords[Number(pocketMatch[1])] || pocketMatch[1]) : numberWords[currentPointValue()];
        speak(`${toothWord}, ${valueWord}, reÃ§u`);
    }

    let recognition = null;
    let isListening = false;
    function initRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            microBtn.disabled = true;
            microBtn.title = 'Reconnaissance vocale indisponible';
            return;
        }

        recognition = new SpeechRecognition();
        recognition.lang = 'fr-FR';
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.onresult = (event) => {
            const last = event.results[event.results.length - 1];
            if (!last?.isFinal) return;
            parseTranscript(last[0]?.transcript || '');
        };
        recognition.onend = () => {
            isListening = false;
            microBtn.classList.remove('recording');
            microBtn.textContent = 'Microphone';
        };
        recognition.onerror = () => {
            isListening = false;
            microBtn.classList.remove('recording');
            microBtn.textContent = 'Microphone';
        };
    }

    microBtn.addEventListener('click', () => {
        if (!recognition) initRecognition();
        if (!recognition) return;
        if (isListening) {
            recognition.stop();
            return;
        }
        isListening = true;
        microBtn.classList.add('recording');
        microBtn.textContent = 'Ecoute...';
        recognition.start();
        setMessage('Commande vocale active');
    });

    let saveTimer = null;
    async function saveChart() {
        if (!saveUrl) {
            setMessage('Selectionnez un patient pour enregistrer le parodontogramme');
            return;
        }

        const payload = {
            recorded_on: document.getElementById('paroRecordedOn')?.value || new Date().toISOString().slice(0, 10),
            measurements_json: JSON.stringify(toothOrder.map((tooth) => ({
                tooth,
                sides: state.teeth[tooth],
            }))),
            summary: computeSummary(),
        };

        const response = await fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) throw new Error('save_failed');
        const data = await response.json();
        if (data?.chart) {
            summaryEl.textContent = data.chart.summary || payload.summary;
            historyEl.prepend(Object.assign(document.createElement('article'), {
                className: 'paro-history-item',
                innerHTML: `<strong>${data.chart.recorded_on || ''}</strong><div class="muted small">${data.chart.summary || payload.summary}</div>`,
            }));
            setMessage('Parodontogramme enregistrÃ©');
        }
    }

    function scheduleSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(async () => {
            try {
                await saveChart();
            } catch (_) {
                setMessage('Erreur de sauvegarde');
            }
        }, 550);
    }

    sideToggle.addEventListener('click', () => {
        state.side = state.side === 'vestibulaire' ? 'linguale' : 'vestibulaire';
        renderAll();
        scheduleSave();
    });

    plusBtn.addEventListener('click', () => adjustDepth(1));
    minusBtn.addEventListener('click', () => adjustDepth(-1));
    bonePlusBtn.addEventListener('click', () => adjustBone(1));
    boneMinusBtn.addEventListener('click', () => adjustBone(-1));
    nextBtn.addEventListener('click', cycleSelection);
    saveBtn.addEventListener('click', async () => {
        try {
            await saveChart();
            tone();
        } catch (_) {
            setMessage('Erreur de sauvegarde');
        }
    });

    mobilityWrap.querySelectorAll('[data-mobility]').forEach((button) => {
        button.addEventListener('click', () => setMobility(Number(button.dataset.mobility)));
    });

    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            chips.forEach((node) => node.classList.remove('active'));
            chip.classList.add('active');
            const preset = Number(chip.dataset.paroPresets || 0);
            setPointValue(preset >= 5 ? 5 : preset, state.selectedPoint);
            renderAll();
            scheduleSave();
        });
    });
    
    renderHistory(initialHistory);
    renderAll();
    console.log('Parodontogramme: initialisation terminée');
    setMessage('Parodontogramme prêt');
        } catch (error) {
            console.error('Parodontogramme ERROR:', error);
            if (gridEl) gridEl.innerHTML = '<div class="alert alert-danger">Erreur: ' + error.message + '</div>';
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeParodontogramme, { once: true });
    } else {
        initializeParodontogramme();
    }
})();

// Function to show quick procedure form for a tooth
async function showQuickProcedureForm(event, toothNumber) {
    // Remove any existing form
    const existingForm = document.getElementById('quick-procedure-form');
    if (existingForm) {
        existingForm.remove();
    }
    
    // Fetch form data from backend
    const formData = await fetchProcedureFormData();
    
    // Create form element
    const formDiv = document.createElement('div');
    formDiv.id = 'quick-procedure-form';
    formDiv.style.position = 'fixed';
    formDiv.style.top = `${event.clientY}px`;
    formDiv.style.left = `${event.clientX}px`;
    formDiv.style.background = 'white';
    formDiv.style.border = '1px solid #dbeafe';
    formDiv.style.borderRadius = '8px';
    formDiv.style.padding = '16px';
    formDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    formDiv.style.zIndex = '1000';
    formDiv.style.width = '320px';
    formDiv.style.fontFamily = 'system-ui, sans-serif';
    formDiv.style.maxHeight = '80vh';
    formDiv.style.overflowY = 'auto';
    
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    // Find consultation with status "en_soin" or use most recent
    const defaultConsultationId = formData.consultations.find(c => c.consultation_status === 'en_soin')?.id 
        || formData.consultations[0]?.id 
        || '';
    
    formDiv.innerHTML = `
        <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 16px; color: #0f172a;">
            Acte pour la dent ${toothNumber}
        </h3>
        
        <!-- Consultation -->
        <div style="margin-bottom: 12px;">
            <label style="display: block; margin-bottom: 4px; font-size: 13px; color: #64748b; font-weight: 600;">Consultation *</label>
            <select id="procedure-consultation" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 14px;">
                <option value="">-- Sélectionner une consultation --</option>
                ${formData.consultations.map(c => `
                    <option value="${c.id}" ${c.id === defaultConsultationId ? 'selected' : ''}>
                        ${new Date(c.consultation_date).toLocaleDateString('fr-FR')} - ${c.consultation_reason || c.consultation_type || 'Consultation'}
                    </option>
                `).join('')}
            </select>
        </div>
        
        <!-- Acte avec autocomplete -->
        <div style="margin-bottom: 12px;">
            <label style="display: block; margin-bottom: 4px; font-size: 13px; color: #64748b; font-weight: 600;">Acte *</label>
            <input type="text" id="procedure-name" list="procedure-list" placeholder="Rechercher un acte..." 
                   style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 14px;">
            <datalist id="procedure-list">
                ${formData.procedures.map(p => `
                    <option value="${p.name}" data-code="${p.code}" data-status="${p.default_status}" data-tooth-status="${p.tooth_status || ''}">
                        ${p.code} - ${p.name} (${p.category})
                    </option>
                `).join('')}
            </datalist>
        </div>
        
        <!-- Code acte (auto-rempli, caché) -->
        <input type="hidden" id="procedure-code">
        
        <!-- Statut -->
        <div style="margin-bottom: 12px;">
            <label style="display: block; margin-bottom: 4px; font-size: 13px; color: #64748b; font-weight: 600;">Statut</label>
            <select id="procedure-status" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 14px;">
                <option value="completed">Terminé</option>
                <option value="planned">Planifié</option>
                <option value="in_progress">En cours</option>
                <option value="cancelled">Annulé</option>
            </select>
        </div>
        
        <!-- Prix -->
        <div style="margin-bottom: 12px;">
            <label style="display: block; margin-bottom: 4px; font-size: 13px; color: #64748b; font-weight: 600;">Prix (MAD)</label>
            <input type="number" id="procedure-price" min="0" step="0.01" 
                   style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 14px;">
        </div>
        
        <!-- Notes -->
        <div style="margin-bottom: 16px;">
            <label style="display: block; margin-bottom: 4px; font-size: 13px; color: #64748b; font-weight: 600;">Notes (optionnel)</label>
            <textarea id="procedure-notes" rows="2" placeholder="Notes complémentaires..."
                      style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 14px; resize: vertical;"></textarea>
        </div>
        
        <!-- Boutons -->
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button id="cancel-procedure" style="flex: 1; padding: 8px 12px; background: #f8fafc; border: 1px solid #dbeafe; border-radius: 4px; cursor: pointer; font-size: 14px; color: #64748b;">
                Annuler
            </button>
            <button id="save-procedure" style="flex: 1; padding: 8px 12px; background: #2563eb; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; color: white;">
                Créer l'acte
            </button>
        </div>
    `;
    
    document.body.appendChild(formDiv);
    
    // Focus on the name input
    const nameInput = formDiv.querySelector('#procedure-name');
    const codeInput = formDiv.querySelector('#procedure-code');
    const statusSelect = formDiv.querySelector('#procedure-status');
    const consultationSelect = formDiv.querySelector('#procedure-consultation');
    
    if (nameInput) nameInput.focus();
    
    // Auto-select consultation if only one available
    if (defaultConsultationId) {
        consultationSelect.value = defaultConsultationId;
    }
    
    // Handle procedure name change (autocomplete)
    nameInput.addEventListener('input', () => {
        const selectedOption = Array.from(formDiv.querySelectorAll('#procedure-list option'))
            .find(opt => opt.value === nameInput.value);
        
        if (selectedOption) {
            codeInput.value = selectedOption.dataset.code || '';
            
            // Update status default
            if (selectedOption.dataset.status) {
                statusSelect.value = selectedOption.dataset.status;
            }
        }
    });
    
    // Handle form submission
    const saveBtn = formDiv.querySelector('#save-procedure');
    const cancelBtn = formDiv.querySelector('#cancel-procedure');
    
    saveBtn.addEventListener('click', async () => {
        const consultationId = consultationSelect?.value;
        if (!consultationId) {
            alert('Veuillez sélectionner une consultation');
            consultationSelect.focus();
            return;
        }
        
        const name = nameInput?.value.trim() || '';
        if (!name) {
            alert('Veuillez entrer un nom pour l\'acte');
            nameInput.focus();
            return;
        }
        
        const selectedOption = Array.from(formDiv.querySelectorAll('#procedure-list option'))
            .find(opt => opt.value === name);
        
        const procedureCode = codeInput.value || selectedOption?.dataset.code || name.toUpperCase().substring(0, 10);
        const status = statusSelect?.value || 'completed';
        const priceInput = formDiv.querySelector('#procedure-price');
        const price = priceInput ? parseFloat(priceInput.value) : 0;
        const notes = formDiv.querySelector('#procedure-notes')?.value.trim() || '';
        const patientId = getCareSelectedPatientId();

        if (!patientId) {
            alert('Veuillez sélectionner un patient');
            return;
        }
        
        // Disable button during request
        saveBtn.disabled = true;
        saveBtn.textContent = 'Création...';
        
        try {
            const response = await fetch(`${careProcedurePatientsBaseUrl}/${patientId}/procedures`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    tooth_number: toothNumber,
                    consultation_id: consultationId,
                    name: name,
                    procedure_code: procedureCode,
                    status: status,
                    price: isNaN(price) ? 0 : price,
                    notes: notes,
                    specialty_id: formData.default_specialty_id || 1
                })
            });

            const responseText = await response.text();
            let responseData = null;
            try {
                responseData = responseText ? JSON.parse(responseText) : null;
            } catch (parseError) {
                responseData = null;
            }
            
            if (!response.ok) {
                throw new Error((responseData && responseData.message) || responseText || 'Erreur lors de la création de l\'acte');
            }
            
            const result = responseData || {};

            // Remove form
            formDiv.remove();

            // Update the 3D tooth status in real-time
            updateToothStatusIn3DView(toothNumber, name, status, selectedOption?.dataset?.toothStatus || '');

            // Attempt to update local mapping immediately using response
            try {
                const createdRaw = result.procedure || result || null;
                const created = normalizeProcedure(createdRaw);
                if (created) {
                    // ensure mapping exists
                    if (!odontogramStatus[toothNumber]) odontogramStatus[toothNumber] = { procedures: [] };
                    odontogramStatus[toothNumber].procedures = odontogramStatus[toothNumber].procedures || [];
                    odontogramStatus[toothNumber].procedures.push(created);

                    // Update 3D mesh userData
                    if (window.toothMeshes && Array.isArray(window.toothMeshes)) {
                        const mesh = window.toothMeshes.find(m => m.userData && m.userData.tooth === toothNumber);
                        if (mesh) {
                            mesh.userData.procedures = mesh.userData.procedures || [];
                            mesh.userData.procedures.push(created);
                        }
                    }

                    // If the selected panel corresponds to this tooth, refresh it
                    if (typeof selected !== 'undefined' && selected && selected.userData && selected.userData.tooth === toothNumber) {
                        renderToothPanel({ tooth: toothNumber, status: (odontogramStatus[toothNumber].status || selected.userData.status), procedures: odontogramStatus[toothNumber].procedures });
                    }

                    // Refresh grid/chart/3D visuals
                    try { renderTeethGrid(); renderChart(); repaintAll(); } catch (e) { console.warn(e); }
                }
            } catch (e) {
                console.warn('Could not apply immediate procedure update:', e);
            }

            // Show success feedback
            showToast('Acte créé avec succès !', 'success');

            // Trigger event for any other listeners, include created procedure
            window.dispatchEvent(new CustomEvent('procedureCreated', { detail: result }));
            
        } catch (error) {
            console.error('Error creating procedure:', error);
            alert('Erreur : ' + error.message);
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Créer l\'acte';
        }
    });
    
    cancelBtn.addEventListener('click', () => {
        formDiv.remove();
    });
    
    // Allow Enter key to submit
    formDiv.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveBtn.click();
        }
    });
    
    // Click outside to close
    formDiv.addEventListener('click', (e) => {
        if (e.target === formDiv) {
            formDiv.remove();
        }
    });
}

// Function to fetch procedure form data from backend
async function fetchProcedureFormData() {
    try {
        const patientId = getCareSelectedPatientId();
        if (!patientId) {
            return {
                consultations: [],
                procedures: [],
                default_specialty_id: 1,
            };
        }

        const response = await fetch(`${careProcedurePatientsBaseUrl}/${patientId}/procedure-form-data`);
        if (!response.ok) throw new Error('Erreur chargement données');
        return await response.json();
    } catch (error) {
        console.error('Error fetching procedure form data:', error);
        // Valeurs par défaut en cas d'erreur
        return {
            consultations: [],
            procedures: [],
            default_specialty_id: 1
        };
    }
}

// Simple toast notification function
function showToast(message, type = 'info') {
    // Remove any existing toast
    const existingToast = document.getElementById('toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.id = 'toast-notification';
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.background = type === 'success' ? '#10b981' : '#dc2626';
    toast.style.color = 'white';
    toast.style.padding = '12px 16px';
    toast.style.borderRadius = '6px';
    toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    toast.style.zIndex = '1000';
    toast.style.fontSize = '14px';
    toast.style.display = 'flex';
    toast.style.alignItems = 'center';
    toast.style.gap = '8px';
    
    toast.innerHTML = `
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 3000);
}

// Listen for procedure created events to potentially update UI
// Helper: fetch all procedures for the current patient and update local odontogramStatus
async function refreshPatientProceduresAndUI() {
    try {
        const patientId = getCareSelectedPatientId();
        if (!patientId) return;
        const resp = await fetch(`${careProcedurePatientsBaseUrl}/${patientId}/procedures`);
        if (!resp.ok) return;
        const list = await resp.json().catch(() => []);

        // Rebuild odontogramStatus mapping by tooth
        const map = {};
        (list || []).forEach((p) => {
            const tooth = Number(p.tooth_number || p.tooth || p.toothNumber || 0);
            if (!tooth) return;

            // Normalize procedure object to the shape expected by renderToothPanel
            const normalized = {
                id: p.id || p.clinical_procedure_id || null,
                type: p.type || p.procedure_code || p.name || p.label || '',
                status: p.status || p.procedure_status || p.tooth_status || 'completed',
                date: p.performed_at || p.performedAt || p.created_at || p.createdAt || null,
                practitioner_name: (p.practitioner && (p.practitioner.name || p.practitioner_name)) || p.practitioner_name || (p.practitioner_name_display || '') ,
                raw: p,
            };

            if (!map[tooth]) map[tooth] = { procedures: [] };
            map[tooth].procedures.push(normalized);
        });

        // Merge into global odontogramStatus
        Object.assign(odontogramStatus, map);

        // Update 3D meshes userData for procedures
        if (window.toothMeshes && Array.isArray(window.toothMeshes)) {
            window.toothMeshes.forEach((m) => {
                const t = m.userData && m.userData.tooth;
                if (t && map[t]) m.userData.procedures = map[t].procedures;
            });
        }

        // If a tooth panel is open for a selected mesh, refresh it
        if (typeof selected !== 'undefined' && selected && selected.userData) {
            renderToothPanel(selected.userData);
        }

        // Refresh grid, chart and 3D repaint
        try { renderTeethGrid(); renderChart(); repaintAll(); } catch (e) { console.warn(e); }
    } catch (e) {
        console.error('Error refreshing patient procedures:', e);
    }
}

// Normalize a procedure object to the shape used by the UI
function normalizeProcedure(p) {
    if (!p) return null;
    return {
        id: p.id || p.clinical_procedure_id || null,
        type: p.type || p.procedure_code || p.name || p.label || '',
        status: p.status || p.procedure_status || p.tooth_status || 'completed',
        date: p.performed_at || p.performedAt || p.created_at || p.createdAt || null,
        practitioner_name: (p.practitioner && (p.practitioner.name || p.practitioner_name)) || p.practitioner_name || (p.practitioner_name_display || ''),
        raw: p,
    };
}

window.addEventListener('procedureCreated', () => {
    // When an act is created, refresh procedures from server and update UI
    console.log('Procedure created — refreshing procedures for patient');
    refreshPatientProceduresAndUI();
});

// Also listen for the local event dispatched when updateToothStatusIn3DView runs
window.addEventListener('toothProcedureAdded', (ev) => {
    const detail = ev?.detail || {};
    // If the tooth panel is visible for this tooth, append the entry locally
    if (detail && detail.toothNumber && typeof selected !== 'undefined' && selected && selected.userData && selected.userData.tooth === detail.toothNumber) {
        // Reuse refresh to ensure consistent state
        refreshPatientProceduresAndUI();
    } else {
        // still refresh mapping to mark the grid/3D
        refreshPatientProceduresAndUI();
    }
});

// Function to update tooth status in the 3D view after procedure creation
function updateToothStatusIn3DView(toothNumber, procedureName, procedureStatus, toothStatusFromOption = '') {
    // Use tooth_status from the selected procedure option if available
    let newStatus = toothStatusFromOption || null;
    
    // Fallback to local mapping if no tooth_status from option
    if (!newStatus) {
        const procedureNameLower = procedureName.toLowerCase().trim();
        
        // Mapping of procedure keywords to tooth statuses
        const statusMapping = {
            'extraction': 'extracted',
            'extract': 'extracted',
            'exodontie': 'extracted',
            'implant': 'implant',
            'implantation': 'implant',
            'crown': 'crown',
            'couronne': 'crown',
            'bridge': 'crown',
            'pont': 'crown',
            'filling': 'filling',
            'obturation': 'filling',
            'composite': 'filling',
            'amalgame': 'filling',
            'root_canal': 'root_canal',
            'canal': 'root_canal',
            'traitement_canal': 'root_canal',
            'traitement endodontique': 'root_canal',
            'endodontique': 'root_canal',
            'decay': 'decay',
            'carie': 'decay',
            'cavity': 'decay',
            'fracture': 'fractured',
            'fractured': 'fractured',
            'absent': 'absent',
            'missing': 'absent'
        };
        
        // Check if procedure name contains any mapping keywords
        for (const [keyword, status] of Object.entries(statusMapping)) {
            if (procedureNameLower.includes(keyword)) {
                newStatus = status;
                break;
            }
        }
    }
    
    // If we determined a new status, update the tooth data
    if (newStatus && typeof window.updateToothStatus === 'function') {
        window.updateToothStatus(toothNumber, newStatus);
    }
    
    // Also update the procedure list for this tooth in the UI if visible
    const event = new CustomEvent('toothProcedureAdded', {
        detail: { toothNumber, procedureName, procedureStatus }
    });
    window.dispatchEvent(event);
}

// Expose a method to update tooth status from outside (to be called by the main odontogram logic)
window.updateToothStatus = function(toothNumber, newStatus) {
    // Find the tooth mesh and update its userData and appearance
    if (!window.toothMeshes) return;
    
    const mesh = window.toothMeshes.find(m => m.userData && m.userData.tooth === toothNumber);
    if (!mesh) return;
    
    // Update the userData
    mesh.userData.status = newStatus;
    
    // Repaint the mesh with new color
    const colorByStatus = {
        present: 0x94a3b8, implant: 0x22c55e, extracted: 0xef4444, decay: 0xf59e0b,
        crown: 0x3b82f6, root_canal: 0x8b5cf6, filling: 0x06b6d4, fractured: 0xfb7185, absent: 0x334155
    };
    
    const baseColor = colorByStatus[newStatus] || colorByStatus.present;
    mesh.material.color.setHex(baseColor);
    
    // Add a slight pulse effect to indicate update
    mesh.material.emissive.setHex(0x2563eb);
    setTimeout(() => {
        mesh.material.emissive.setHex(0x000000);
    }, 500);
};
</script>
<?php $__env->stopPush(); ?>


<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('js/inline-history-edit.js')); ?>?v=<?php echo e(@filemtime(public_path('js/inline-history-edit.js')) ?: time()); ?>"></script>
<script src="<?php echo e(asset('js/hybrid-store.js')); ?>?v=<?php echo e(@filemtime(public_path('js/hybrid-store.js')) ?: time()); ?>" defer></script>
<?php $__env->stopPush(); ?>

<?php if(in_array($currentSpecialtyCode, ['GYNECO', 'OMNI'])): ?>
<?php $__env->startPush('scripts'); ?>
<?php echo $__env->make('gynecology::partials.gynecology-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>
<?php endif; ?>

<?php if(in_array($currentSpecialtyCode, ['PEDIA', 'OMNI'])): ?>
<?php $__env->startPush('scripts'); ?>
<?php echo $__env->make('pediatrics::partials.pediatrics-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>
<?php endif; ?>

<?php if(in_array($currentSpecialtyCode, ['BURNS', 'OMNI'])): ?>
<?php $__env->startPush('scripts'); ?>
<?php echo $__env->make('burns::partials.burns-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>
<?php endif; ?>

<?php if(in_array($currentSpecialtyCode, ['REHAB', 'OMNI'])): ?>
<?php $__env->startPush('scripts'); ?>
<?php echo $__env->make('rehab::partials.rehab-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>
<?php endif; ?>

<?php if(in_array($currentSpecialtyCode, ['INTMED', 'OMNI'])): ?>
<?php $__env->startPush('scripts'); ?>
<?php echo $__env->make('internal-medicine::partials.internal-medicine-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>
<?php endif; ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp8.2\htdocs\fils_attente\resources\views/modules/clinical-workflow.blade.php ENDPATH**/ ?>