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

<?php if($dashboard && $dashboard['active_pregnancy']): ?>
<?php
    $pregnancy = $dashboard['active_pregnancy'];
    $ob = $dashboard['obstetric_dashboard'];
    $visits = $ob['visits'] ?? collect();
?>

<section id="prenatal-visits-table" class="card gyneco-card" data-care-tab-panel="clinical">
    <div class="section-head">
        <h3 class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-pink-500"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Historique des Visites Prénatales
        </h3>
        <span class="pnv-count-badge"><?php echo e($visits->count()); ?> visite(s)</span>
    </div>

    <?php if($visits->count() > 0): ?>
        <div class="pnv-table-wrap">
            <table class="pnv-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Terme (SA)</th>
                        <th>Poids</th>
                        <th>Tension Art.</th>
                        <th>Haut. Utérine</th>
                        <th>BCF</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $visits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $visit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="<?php echo e(($visit->blood_pressure_systolic >= 140 || $visit->blood_pressure_diastolic >= 90) ? 'pnv-row-alert' : ''); ?>">
                            <td class="pnv-cell-date"><?php echo e($visit->visit_date->format('d/m/Y')); ?></td>
                            <td class="pnv-cell-sa">
                                <?php if($visit->gestational_weeks_at_visit !== null): ?>
                                    <span class="pnv-sa-badge"><?php echo e($visit->gestational_weeks_at_visit); ?>+<?php echo e($visit->gestational_days_at_visit ?? 0); ?></span>
                                <?php else: ?>
                                    <span class="pnv-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="pnv-cell-weight">
                                <?php if($visit->weight_kg): ?>
                                    <?php echo e($visit->weight_kg); ?> <small>kg</small>
                                <?php else: ?>
                                    <span class="pnv-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="pnv-cell-bp">
                                <?php if($visit->blood_pressure_systolic && $visit->blood_pressure_diastolic): ?>
                                    <span class="<?php echo e(($visit->blood_pressure_systolic >= 140 || $visit->blood_pressure_diastolic >= 90) ? 'pnv-bp-alert' : ''); ?>">
                                        <?php echo e($visit->blood_pressure_systolic); ?>/<?php echo e($visit->blood_pressure_diastolic); ?>

                                    </span>
                                    <?php if($visit->blood_pressure_systolic >= 140 || $visit->blood_pressure_diastolic >= 90): ?>
                                        <span class="pnv-hta-tag">HTA</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="pnv-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="pnv-cell-hu">
                                <?php if($visit->fundal_height_cm): ?>
                                    <?php echo e($visit->fundal_height_cm); ?> <small>cm</small>
                                <?php else: ?>
                                    <span class="pnv-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="pnv-cell-bcf">
                                <?php if($visit->fetal_heart_rate): ?>
                                    <span class="pnv-bcf-value"><?php echo e($visit->fetal_heart_rate); ?></span> <small>bpm</small>
                                <?php else: ?>
                                    <span class="pnv-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="pnv-cell-actions">
                                <button type="button" class="pnv-action-btn pnv-btn-detail" title="Détails" data-visit-id="<?php echo e($visit->id); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="pnv-empty">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-pink-300"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p>Aucune visite prénatale enregistrée</p>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="visitDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#fdf2f8,#fce7f3);border-bottom:1px solid #f9a8d4">
                <h5 class="modal-title" style="font-weight:800;color:#be185d">Détails de la visite</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="visitDetailBody">
            </div>
        </div>
    </div>
</div>

<style>
.pnv-count-badge{font-size:.75rem;font-weight:700;color:#be185d;background:#fce7f3;padding:3px 10px;border-radius:999px;border:1px solid #f9a8d4}
.pnv-table-wrap{overflow-x:auto;border-radius:12px;border:1px solid #e2e8f0;background:#fff}
.pnv-table{width:100%;border-collapse:collapse;font-size:.84rem}
.pnv-table thead{background:#f8fafc;border-bottom:2px solid #e2e8f0}
.pnv-table th{padding:10px 12px;text-align:start;font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;white-space:nowrap}
.pnv-table td{padding:8px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.pnv-table tbody tr:hover{background:#fdf2f8}
.pnv-table tbody tr:last-child td{border-bottom:none}
.pnv-row-alert{background:#fef2f2 !important}
.pnv-cell-date{font-weight:700;color:#0f172a;white-space:nowrap}
.pnv-sa-badge{font-size:.78rem;font-weight:800;color:#db2777;background:#fce7f3;padding:2px 8px;border-radius:999px}
.pnv-cell-weight,.pnv-cell-hu{font-weight:600;color:#334155}
.pnv-cell-weight small,.pnv-cell-hu small,.pnv-cell-bcf small{color:#94a3b8;font-weight:400}
.pnv-bp-alert{font-weight:800;color:#dc2626}
.pnv-hta-tag{font-size:.65rem;font-weight:800;color:#fff;background:#dc2626;padding:1px 6px;border-radius:999px;margin-inline-start:4px}
.pnv-bcf-value{font-weight:700;color:#7c3aed}
.pnv-muted{color:#cbd5e1}
.pnv-cell-actions{white-space:nowrap}
.pnv-action-btn{width:28px;height:28px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:#64748b;transition:all .15s ease}
.pnv-action-btn:hover{border-color:#f9a8d4;background:#fce7f3;color:#db2777}
.pnv-empty{text-align:center;padding:32px 16px;color:#94a3b8}
.pnv-empty p{margin:8px 0 0;font-size:.88rem}
@media (max-width:768px){.pnv-table{font-size:.78rem}.pnv-table th,.pnv-table td{padding:6px 8px}}
</style>

<script>
(() => {
    document.querySelectorAll('.pnv-btn-detail').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            if (!row) return;
            const cells = row.querySelectorAll('td');
            const detail = `
                <div class="d-grid gap-2">
                    <div><strong>Date:</strong> ${cells[0]?.textContent?.trim()}</div>
                    <div><strong>Terme:</strong> ${cells[1]?.textContent?.trim()}</div>
                    <div><strong>Poids:</strong> ${cells[2]?.textContent?.trim()}</div>
                    <div><strong>Tension:</strong> ${cells[3]?.textContent?.trim()}</div>
                    <div><strong>Hauteur utérine:</strong> ${cells[4]?.textContent?.trim()}</div>
                    <div><strong>BCF:</strong> ${cells[5]?.textContent?.trim()}</div>
                </div>`;
            const body = document.getElementById('visitDetailBody');
            if (body) body.innerHTML = detail;
            const modal = new bootstrap.Modal(document.getElementById('visitDetailModal'));
            modal.show();
        });
    });
})();
</script>
<?php endif; ?>
<?php /**PATH D:\xampp8.2\htdocs\fils_attente\Modules\Gynecology\Providers/../Resources/views/partials/prenatal-visits-table.blade.php ENDPATH**/ ?>