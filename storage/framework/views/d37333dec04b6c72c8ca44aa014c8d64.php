<?php $__env->startSection('title', 'Module 1 - Noyau Administratif'); ?>
<?php $__env->startSection('page_pretitle', 'Module 1'); ?>
<?php $__env->startSection('page_title', 'Admin / KPI / RBAC / Multi-praticien'); ?>

<?php $__env->startSection('content'); ?>
<div class="care-theme">
    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-label">CA Total</div>
                    <div class="metric-value"><?php echo e(number_format($kpi['ca_total'], 2, ',', ' ')); ?> MAD</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-label">Devis acceptés</div>
                    <div class="metric-value"><?php echo e($kpi['quotes']['acceptance_rate_percent']); ?>%</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-label">Annulation/No-show</div>
                    <div class="metric-value"><?php echo e($kpi['appointments']['cancellation_rate_percent']); ?>%</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-label">Période</div>
                    <div class="metric-value metric-small"><?php echo e($from); ?> → <?php echo e($to); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card section-card mb-3">
        <div class="card-header"><h3 class="card-title">Filtres KPI</h3></div>
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Du</label>
                    <input type="date" name="from" value="<?php echo e($from); ?>" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Au</label>
                    <input type="date" name="to" value="<?php echo e($to); ?>" class="form-control">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Actualiser</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-lg-6">
            <div class="card section-card">
                <div class="card-header"><h3 class="card-title">Affectation des rôles</h3></div>
                <div class="card-body">
                    <form method="POST" id="roleForm">
                        <?php echo csrf_field(); ?>
                        <div class="mb-2">
                            <label class="form-label">Utilisateur</label>
                            <select class="form-select" id="roleUserSelect" name="user_id">
                                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($user->id); ?>"><?php echo e($user->name); ?> (<?php echo e($user->role); ?>)</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Rôles</label>
                            <select class="form-select" name="role_codes[]" multiple size="5">
                                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($role->code); ?>"><?php echo e($role->name); ?> (<?php echo e($role->code); ?>)</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <button class="btn btn-outline-primary">Mettre à jour</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card section-card">
                <div class="card-header"><h3 class="card-title">Profil comptable praticien</h3></div>
                <div class="card-body">
                    <form method="POST" id="accountingForm">
                        <?php echo csrf_field(); ?>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label">Praticien</label>
                                <select class="form-select" id="accountingUserSelect" name="user_id">
                                    <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($user->id); ?>"><?php echo e($user->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="col-md-6"><label class="form-label">Code entité</label><input class="form-control" name="entity_code"></div>
                            <div class="col-md-6"><label class="form-label">Préfixe facture</label><input class="form-control" name="invoice_prefix" value="FAC"></div>
                            <div class="col-md-6"><label class="form-label">Devise</label><input class="form-control" name="currency" value="MAD"></div>
                            <div class="col-md-6"><label class="form-label">Taxe %</label><input class="form-control" type="number" step="0.01" name="default_tax_rate" value="0"></div>
                            <div class="col-12"><button class="btn btn-outline-success">Enregistrer</button></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card section-card mt-3">
        <div class="card-header"><h3 class="card-title">Surcharges permissions utilisateur</h3></div>
        <div class="card-body">
            <form method="POST" id="permissionForm">
                <?php echo csrf_field(); ?>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Utilisateur</label>
                        <select class="form-select" id="permissionUserSelect" name="user_id">
                            <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($user->id); ?>"><?php echo e($user->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Permission</label>
                        <select class="form-select" name="permissions[0][code]">
                            <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($permission->code); ?>"><?php echo e($permission->name); ?> (<?php echo e($permission->code); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">État</label>
                        <select class="form-select" name="permissions[0][is_granted]">
                            <option value="1">Accorder</option>
                            <option value="0">Refuser</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-outline-dark w-100">Appliquer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card section-card mt-3">
        <div class="card-header"><h3 class="card-title">CA par spécialité</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Spécialité</th><th class="text-end">CA</th></tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $kpi['ca_by_specialty']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($row->specialty_name); ?></td>
                            <td class="text-end"><?php echo e(number_format((float)$row->total, 2, ',', ' ')); ?> MAD</td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="2" class="text-secondary">Aucune donnée.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('head'); ?>
<style>
.care-theme { --tone-a:#0f766e; --tone-b:#0f172a; --tone-c:#fb923c; }
.metric-card { background: linear-gradient(135deg, color-mix(in srgb, var(--tone-a) 90%, white), var(--tone-b)); color: #fff; border: 0; }
.metric-label { opacity: .9; font-size: .85rem; text-transform: uppercase; letter-spacing: .04em; }
.metric-value { font-size: 1.6rem; font-weight: 700; line-height: 1.2; margin-top: 4px; }
.metric-small { font-size: 1.05rem; }
.section-card { border: 1px solid #dbe2ea; box-shadow: 0 8px 18px rgba(15,23,42,.06); }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xamp8.1\htdocs\medical\resources\views/modules/core-admin.blade.php ENDPATH**/ ?>