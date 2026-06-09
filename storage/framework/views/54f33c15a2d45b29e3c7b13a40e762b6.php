<?php $__env->startSection('title', 'Paramétrage des plannings'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h1 class="page-title">Paramétrage des plannings</h1>
            <p class="text-secondary mb-0">Configurer les modes de planification par spécialiste.</p>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="<?php echo e(route('admin.settings')); ?>">Paramètres généraux</a>
        </div>
    </section>

    <section class="card p-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-lg-4">
                <label class="form-label">Organisation</label>
                <select name="organization_id" class="form-select" onchange="this.form.submit()">
                    <?php $__currentLoopData = $organizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $org): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($org->id); ?>" <?php if($organizationId == $org->id): echo 'selected'; endif; ?>><?php echo e($org->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-lg-6">
                <label class="form-label">Professionnel</label>
                <select name="professional_id" class="form-select" onchange="this.form.submit()">
                    <?php $__currentLoopData = $professionals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pro): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($pro->id); ?>" <?php if($selectedProfessionalId == $pro->id): echo 'selected'; endif; ?>>
                            <?php echo e($pro->display_name); ?> (<?php echo e($pro->specialty?->name ?? 'Généraliste'); ?>)
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-lg-2">
                <button type="submit" class="btn btn-primary w-100">Charger</button>
            </div>
        </form>
    </section>

    <?php if($selectedProfessional): ?>
        <section class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="section-title mb-0">
                    Planning de <strong><?php echo e($selectedProfessional->display_name); ?></strong>
                    <span class="badge bg-secondary ms-2"><?php echo e($selectedProfessional->specialty?->name ?? 'Général'); ?></span>
                </h2>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                    + Ajouter un créneau
                </button>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jour</th>
                            <th>Horaire</th>
                            <th>Mode</th>
                            <th>Type d'acte</th>
                            <th>Durée</th>
                            <th>Max/jour</th>
                            <th>Actif</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $grid; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php $__currentLoopData = $day['plannings']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><strong><?php echo e($day['day_name']); ?></strong></td>
                                    <td><?php echo e(substr($p['start_time'], 0, 5)); ?> — <?php echo e(substr($p['end_time'], 0, 5)); ?></td>
                                    <td>
                                        <span class="badge <?php switch($p['planning_mode']):
                                            case ('by_act'): ?> bg-info <?php break; ?>
                                            <?php case ('mixed'): ?> bg-warning <?php break; ?>
                                            <?php default: ?> bg-secondary <?php endswitch; ?>
                                        ">
                                            <?php echo e($planningModes[$p['planning_mode']] ?? $p['planning_mode']); ?>

                                        </span>
                                    </td>
                                    <td><?php echo e($p['appointment_type'] ?? '—'); ?></td>
                                    <td><?php echo e($p['consultation_minutes']); ?> min</td>
                                    <td><?php echo e($p['max_patients'] ?? '∞'); ?></td>
                                    <td>
                                        <span class="badge <?php echo e($p['is_active'] ? 'bg-success' : 'bg-danger'); ?>">
                                            <?php echo e($p['is_active'] ? 'Oui' : 'Non'); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-secondary edit-btn"
                                                data-id="<?php echo e($p['id']); ?>"
                                                data-start="<?php echo e(substr($p['start_time'], 0, 5)); ?>"
                                                data-end="<?php echo e(substr($p['end_time'], 0, 5)); ?>"
                                                data-mode="<?php echo e($p['planning_mode']); ?>"
                                                data-acte="<?php echo e($p['appointment_type_id']); ?>"
                                                data-duration="<?php echo e($p['consultation_minutes']); ?>"
                                                data-max="<?php echo e($p['max_patients']); ?>"
                                                data-active="<?php echo e($p['is_active'] ? '1' : '0'); ?>"
                                                data-bs-toggle="modal" data-bs-target="#editModal">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            <form method="POST" action="<?php echo e(route('admin.planning.destroy', $p['id'])); ?>" onsubmit="return confirm('Supprimer ce créneau ?')">
                                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                                <button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if(count($day['plannings']) === 0): ?>
                                <tr><td colspan="8" class="text-muted small"><?php echo e($day['day_name']); ?> — Aucun créneau configuré</td></tr>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="8" class="text-center text-muted py-3">Sélectionnez un professionnel pour voir son planning</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>


<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?php echo e(route('admin.planning.store')); ?>" class="modal-content">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="professional_id" value="<?php echo e($selectedProfessionalId); ?>">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un créneau</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label">Jour</label>
                        <select name="day_of_week" class="form-select" required>
                            <?php $__currentLoopData = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($i); ?>"><?php echo e($d); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Début</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Fin</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Mode de planification</label>
                        <select name="planning_mode" class="form-select" id="planningMode" required>
                            <?php $__currentLoopData = $planningModes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($val); ?>"><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-lg-6" id="acteField">
                        <label class="form-label">Type d'acte</label>
                        <select name="appointment_type_id" class="form-select">
                            <option value="">— Sélectionner —</option>
                            <?php $__currentLoopData = $appointmentTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $specialty => $types): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <optgroup label="<?php echo e($specialty); ?>">
                                    <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $at): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($at->id); ?>">
                                            <?php echo e($at->name); ?> (<?php echo e($at->duration_minutes); ?> min)
                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </optgroup>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Durée consultation (min)</label>
                        <input type="number" name="consultation_minutes" class="form-control"
                               value="<?php echo e(config('appointment.default_consultation_minutes', 20)); ?>" min="5">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Max patients / jour</label>
                        <input type="number" name="max_patients_per_day" class="form-control" placeholder="Illimité">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>


<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="" class="modal-content" id="editForm">
            <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
            <div class="modal-header">
                <h5 class="modal-title">Modifier le créneau</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label">Début</label>
                        <input type="time" name="start_time" id="edit_start" class="form-control" required>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Fin</label>
                        <input type="time" name="end_time" id="edit_end" class="form-control" required>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Mode</label>
                        <select name="planning_mode" class="form-select" id="edit_mode" required>
                            <?php $__currentLoopData = $planningModes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($val); ?>"><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-lg-6" id="editActeField">
                        <label class="form-label">Type d'acte</label>
                        <select name="appointment_type_id" id="edit_acte" class="form-select">
                            <option value="">— Sélectionner —</option>
                            <?php $__currentLoopData = $appointmentTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $specialty => $types): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <optgroup label="<?php echo e($specialty); ?>">
                                    <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $at): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($at->id); ?>"><?php echo e($at->name); ?> (<?php echo e($at->duration_minutes); ?> min)</option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </optgroup>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Durée (min)</label>
                        <input type="number" name="consultation_minutes" id="edit_duration" class="form-control" min="5">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Max/jour</label>
                        <input type="number" name="max_patients_per_day" id="edit_max" class="form-control" placeholder="Illimité">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Actif</label>
                        <select name="is_active" id="edit_active" class="form-select">
                            <option value="1">Oui</option>
                            <option value="0">Non</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Mettre à jour</button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('head'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modeSelect = document.getElementById('planningMode');
    const acteField = document.getElementById('acteField');
    function toggleActeField() {
        acteField.style.display = modeSelect.value === 'by_specialist' ? 'none' : 'block';
    }
    if (modeSelect) {
        modeSelect.addEventListener('change', toggleActeField);
        toggleActeField();
    }

    const editMode = document.getElementById('edit_mode');
    const editActeField = document.getElementById('editActeField');
    function toggleEditActeField() {
        editActeField.style.display = editMode.value === 'by_specialist' ? 'none' : 'block';
    }
    if (editMode) {
        editMode.addEventListener('change', toggleEditActeField);
        toggleEditActeField();
    }

    // Pre-fill edit modal
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const mode = this.dataset.mode;
            document.getElementById('edit_start').value = this.dataset.start;
            document.getElementById('edit_end').value = this.dataset.end;
            document.getElementById('edit_mode').value = mode;
            document.getElementById('edit_acte').value = this.dataset.acte || '';
            document.getElementById('edit_duration').value = this.dataset.duration;
            document.getElementById('edit_max').value = this.dataset.max || '';
            document.getElementById('edit_active').value = this.dataset.active;
            document.getElementById('editForm').action = '<?php echo e(url('admin/planning-settings/planning')); ?>/' + this.dataset.id;
            toggleEditActeField();
        });
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xamp8.1\htdocs\medical\Modules\Appointment\Providers/../Resources/views/admin/planning-settings.blade.php ENDPATH**/ ?>