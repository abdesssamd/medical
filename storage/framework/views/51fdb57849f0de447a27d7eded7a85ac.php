<?php $__env->startSection('title', 'Actes RIS'); ?>
<?php $__env->startSection('page-title', 'Gestion des actes RIS'); ?>

<?php $__env->startSection('content'); ?>
<style>
    .param-page { display: grid; gap: 18px; color: #0f172a; }
    .param-hero { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding: 18px; border: 1px solid #dbe8f3; border-radius: 18px; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); box-shadow: 0 18px 40px rgba(15,23,42,0.06); }
    .param-hero h2 { margin: 0 0 8px; font-size: 1.25rem; font-weight: 900; }
    .param-hero p { margin: 0; color: #64748b; }
    .param-grid { display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(380px, 0.92fr); gap: 18px; align-items: start; }
    .param-card { background: #fff; border: 1px solid #dbe8f3; border-radius: 18px; box-shadow: 0 18px 40px rgba(15,23,42,0.06); }
    .param-card-inner { padding: 18px; }
    .param-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
    .param-title { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
    .param-muted { color: #64748b; }
    .param-link { color:#2563eb; text-decoration:none; font-weight:700; }
    .param-list { display:flex; flex-direction:column; gap:12px; }
    .param-item { border:1px solid #e2e8f0; border-radius:16px; padding:16px; background: #fff; }
    .param-item-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
    .param-badge { display:inline-flex; padding:4px 10px; border-radius:999px; background:#e0f2fe; color:#075985; font-size:12px; font-weight:700; }
    .param-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .param-btn { display:inline-flex; align-items:center; justify-content:center; min-height: 38px; border:1px solid #cbd5e1; background:#fff; color:#0f172a; text-decoration:none; padding:8px 12px; border-radius:10px; font-weight:700; cursor:pointer; }
    .param-btn:hover { border-color:#93c5fd; background:#eff6ff; color:#1d4ed8; }
    .param-btn-danger { border-color:#fca5a5; background:#fee2e2; color:#b91c1c; }
    .param-btn-primary { background:#2563eb; color:#fff; border-color:#2563eb; }
    .param-btn-primary:hover { background:#1d4ed8; color:#fff; border-color:#1d4ed8; }
    .param-form label { display:block; margin-bottom:6px; font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.04em; }
    .param-form input,.param-form select { width:100%; border:1px solid #cbd5e1; border-radius:12px; padding:10px 12px; font: inherit; background:#fff; }
    .param-grid-form { display:grid; gap:12px; }
    .param-empty { padding:18px; border:1px dashed #cbd5e1; border-radius:14px; color:#64748b; }
    .param-warning { padding: 10px 12px; border-radius: 10px; background: #fff7ed; color:#9a3412; border:1px solid #fdba74; font-weight:700; }
    @media (max-width: 1100px) { .param-grid { grid-template-columns: 1fr; } .param-hero { flex-direction:column; } }
</style>

<div class="param-page">
    <?php if(session('success')): ?>
        <div class="param-warning" style="background:#dcfce7; border-color:#86efac; color:#166534;"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="param-warning" style="background:#fee2e2; border-color:#fecaca; color:#991b1b;"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <section class="param-hero">
        <div>
            <h2>Gestion des actes RIS</h2>
            <p>Créer, modifier ou supprimer les actes d'imagerie disponibles dans le module RIS.</p>
        </div>
        <a href="<?php echo e(route('ris.exams.index')); ?>" class="param-link">Retour aux examens</a>
    </section>

    <div class="param-grid">
        <section class="param-card">
            <div class="param-card-inner">
                <div class="param-toolbar">
                    <div>
                        <div class="param-title">Actes enregistrés</div>
                        <div class="param-muted">Liste des actes disponibles pour les demandes d'examen.</div>
                    </div>
                </div>

                <div class="param-list">
                    <?php $__empty_1 = true; $__currentLoopData = $procedures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $procedure): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <article class="param-item">
                            <div class="param-item-head">
                                <div>
                                    <div style="font-weight:800; font-size:16px;"><?php echo e($procedure->label); ?></div>
                                    <div class="param-muted" style="margin-top:4px;">
                                        Code: <span class="param-badge"><?php echo e($procedure->code); ?></span>
                                        &middot; Prix: <strong><?php echo e(number_format((float) $procedure->price, 2, ',', ' ')); ?> MAD</strong>
                                        &middot; <?php echo e($procedure->orders()->count()); ?> examen(s)
                                    </div>
                                </div>
                                <div class="param-actions">
                                    <a href="<?php echo e(route('ris.parametrage.procedures.edit', $procedure)); ?>" class="param-btn"><i class="ti ti-edit"></i></a>
                                    <form method="POST" action="<?php echo e(route('ris.parametrage.procedures.destroy', $procedure)); ?>" onsubmit="return confirm('Supprimer cet acte ?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="param-btn param-btn-danger"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="param-empty">Aucun acte RIS pour le moment. Créez-en un via le formulaire.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="param-card">
            <div class="param-card-inner">
                <div class="param-title">
                    <?php if($editingProcedure): ?>
                        Modifier l'acte
                    <?php else: ?>
                        Nouvel acte
                    <?php endif; ?>
                </div>
                <div class="param-muted" style="margin-bottom:14px;">
                    <?php if($editingProcedure): ?>
                        Modification de <strong><?php echo e($editingProcedure->label); ?></strong>
                    <?php else: ?>
                        Remplissez le formulaire pour ajouter un acte.
                    <?php endif; ?>
                </div>

                <form method="POST" action="<?php echo e($editingProcedure ? route('ris.parametrage.procedures.update', $editingProcedure) : route('ris.parametrage.procedures.store')); ?>" class="param-form">
                    <?php echo csrf_field(); ?>
                    <?php if($editingProcedure): ?>
                        <?php echo method_field('PUT'); ?>
                    <?php endif; ?>

                    <div class="param-grid-form">
                        <div>
                            <label>Code *</label>
                            <input type="text" name="code" value="<?php echo e(old('code', $editingProcedure?->code)); ?>" placeholder="EX: RX-PANO" required maxlength="60">
                        </div>
                        <div>
                            <label>Libellé *</label>
                            <input type="text" name="label" value="<?php echo e(old('label', $editingProcedure?->label)); ?>" placeholder="Panoramique dentaire" required maxlength="191">
                        </div>
                        <div>
                            <label>Prix *</label>
                            <input type="number" step="0.01" min="0" name="price" value="<?php echo e(old('price', $editingProcedure?->price)); ?>" placeholder="250.00" required>
                        </div>
                    </div>

                    <div class="param-actions" style="margin-top:14px;">
                        <button type="submit" class="param-btn param-btn-primary">
                            <?php echo e($editingProcedure ? 'Enregistrer' : 'Créer l\'acte'); ?>

                        </button>
                        <?php if($editingProcedure): ?>
                            <a href="<?php echo e(route('ris.parametrage.procedures.index')); ?>" class="param-btn">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xamp8.1\htdocs\medical\Modules\RIS\Providers/../Resources/views/parametrage/procedures/index.blade.php ENDPATH**/ ?>