<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['pregnancy' => null, 'selectedPatientId' => 0]));

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

foreach (array_filter((['pregnancy' => null, 'selectedPatientId' => 0]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="modal fade" id="fetalBiometryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border-bottom:1px solid #93c5fd">
                <h5 class="modal-title" style="font-weight:800;color:#1e40af">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    Biométrie Fœtale
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="fetalBiometryForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="pregnancy_record_id" value="<?php echo e($pregnancy?->id); ?>">

                    <div class="biometry-section">
                        <h4>Informations générales</h4>
                        <div class="biometry-grid biometry-grid-3">
                            <div class="biometry-field">
                                <label>Date d'examen *</label>
                                <input type="date" name="exam_date" class="form-control" required value="<?php echo e(date('Y-m-d')); ?>">
                            </div>
                            <div class="biometry-field">
                                <label>Type d'échographie</label>
                                <select name="ultrasound_type" class="form-select">
                                    <option value="obstetric">Obstétricale</option>
                                    <option value="morphological">Morphologique</option>
                                    <option value="doppler">Doppler</option>
                                    <option value="gynecological">Gynécologique</option>
                                </select>
                            </div>
                            <div class="biometry-field">
                                <label>Trimestre</label>
                                <select name="trimester" class="form-select">
                                    <option value="">-</option>
                                    <option value="1">1er trimestre (T1)</option>
                                    <option value="2">2ème trimestre (T2)</option>
                                    <option value="3">3ème trimestre (T3)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="biometry-section">
                        <h4>Biométrie fœtale</h4>
                        <div class="biometry-grid biometry-grid-5">
                            <div class="biometry-field biometry-field-highlight">
                                <label>BIP (mm)</label>
                                <input type="number" name="bip_mm" step="0.1" min="0" max="120" class="form-control biometry-input" placeholder="Diamètre bipariétal">
                                <span class="biometry-unit">mm</span>
                            </div>
                            <div class="biometry-field biometry-field-highlight">
                                <label>PC (mm)</label>
                                <input type="number" name="hc_mm" step="0.1" min="0" max="400" class="form-control biometry-input" placeholder="Périmètre crânien">
                                <span class="biometry-unit">mm</span>
                            </div>
                            <div class="biometry-field biometry-field-highlight">
                                <label>PA (mm)</label>
                                <input type="number" name="ac_mm" step="0.1" min="0" max="450" class="form-control biometry-input" placeholder="Périmètre abdominal">
                                <span class="biometry-unit">mm</span>
                            </div>
                            <div class="biometry-field biometry-field-highlight">
                                <label>LF (mm)</label>
                                <input type="number" name="fl_mm" step="0.1" min="0" max="90" class="form-control biometry-input" placeholder="Longueur fémorale">
                                <span class="biometry-unit">mm</span>
                            </div>
                            <div class="biometry-field biometry-field-epf">
                                <label>EPF (g)</label>
                                <input type="number" name="efw_grams" id="efwGrams" step="1" min="0" max="6000" class="form-control biometry-input biometry-input-auto" placeholder="Calcul auto" readonly>
                                <span class="biometry-unit">grammes</span>
                                <span class="biometry-hint">Hadlock</span>
                            </div>
                        </div>
                        <div class="biometry-grid biometry-grid-3 mt-2">
                            <div class="biometry-field">
                                <label>RCF (bpm)</label>
                                <input type="number" name="fetal_heart_rate" min="60" max="220" class="form-control" placeholder="Rythme cardiaque fœtal">
                            </div>
                            <div class="biometry-field">
                                <label>Présentation</label>
                                <select name="fetal_presentation" class="form-select">
                                    <option value="">-</option>
                                    <option value="cephalic">Céphalique</option>
                                    <option value="breech">Siège</option>
                                    <option value="transverse">Transverse</option>
                                </select>
                            </div>
                            <div class="biometry-field">
                                <label>Mouvements fœtaux</label>
                                <select name="fetal_movements" class="form-select">
                                    <option value="present">Présents</option>
                                    <option value="reduced">Diminués</option>
                                    <option value="absent">Absents</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="biometry-section">
                        <h4>Liquide amniotique & Placenta</h4>
                        <div class="biometry-grid biometry-grid-3">
                            <div class="biometry-field">
                                <label>ILA (mm)</label>
                                <input type="number" name="amniotic_fluid_index_mm" step="0.1" min="0" max="400" class="form-control" placeholder="Index liquide amniotique">
                            </div>
                            <div class="biometry-field">
                                <label>Évaluation LA</label>
                                <select name="amniotic_fluid_assessment" class="form-select">
                                    <option value="">-</option>
                                    <option value="normal">Normal</option>
                                    <option value="oligohydramnios">Oligoamnios</option>
                                    <option value="polyhydramnios">Hydramnios</option>
                                </select>
                            </div>
                            <div class="biometry-field">
                                <label>Localisation placenta</label>
                                <select name="placenta_location" class="form-select">
                                    <option value="">-</option>
                                    <option value="anterior">Antérieur</option>
                                    <option value="posterior">Postérieur</option>
                                    <option value="fundal">Fundique</option>
                                    <option value="lateral">Latéral</option>
                                    <option value="low">Bas inséré</option>
                                    <option value="previa">Praevia</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="biometry-section">
                        <h4>Doppler (optionnel)</h4>
                        <div class="biometry-grid biometry-grid-4">
                            <div class="biometry-field">
                                <label>AU PI</label>
                                <input type="text" name="umbilical_artery_pi" class="form-control" placeholder="Index de pulsatilité">
                            </div>
                            <div class="biometry-field">
                                <label>AU RI</label>
                                <input type="text" name="umbilical_artery_ri" class="form-control" placeholder="Index de résistance">
                            </div>
                            <div class="biometry-field">
                                <label>ACM PI</label>
                                <input type="text" name="middle_cerebral_artery_pi" class="form-control" placeholder="Artère cérébrale moyenne">
                            </div>
                            <div class="biometry-field">
                                <label>DV PI</label>
                                <input type="text" name="ductus_venosus_pi" class="form-control" placeholder="Ductus venosus">
                            </div>
                        </div>
                    </div>

                    <div class="biometry-section">
                        <h4>Conclusion</h4>
                        <textarea name="conclusion" class="form-control" rows="3" placeholder="Conclusion échographique..."></textarea>
                        <textarea name="recommendations" class="form-control mt-2" rows="2" placeholder="Recommandations..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-blue" id="submitBiometryForm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.biometry-section{padding:14px;border:1px solid #f1f5f9;border-radius:14px;background:#fafbfc;margin-bottom:14px}
.biometry-section h4{font-size:.9rem;font-weight:800;color:#0f172a;margin:0 0 12px;padding-bottom:8px;border-bottom:1px solid #e2e8f0}
.biometry-grid{display:grid;gap:10px}
.biometry-grid-5{grid-template-columns:repeat(5,minmax(0,1fr))}
.biometry-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
.biometry-grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.biometry-field{position:relative}
.biometry-field label{display:block;font-size:.78rem;font-weight:700;color:#334155;margin-bottom:4px}
.biometry-field .form-control,.biometry-field .form-select{border-radius:10px;border-color:#e2e8f0;font-size:.88rem;padding-right:40px}
.biometry-field .form-control:focus,.biometry-field .form-select:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.12)}
.biometry-field-highlight .form-control{background:linear-gradient(135deg,#eff6ff,#fff);border-color:#93c5fd;font-weight:700;font-size:1rem}
.biometry-field-epf .form-control{background:linear-gradient(135deg,#f0fdf4,#fff);border-color:#86efac;font-weight:800;font-size:1.1rem;color:#16a34a}
.biometry-unit{position:absolute;right:12px;top:32px;font-size:.72rem;color:#64748b;font-weight:600}
.biometry-hint{position:absolute;right:12px;top:50px;font-size:.65rem;color:#94a3b8;font-style:italic}
.biometry-input-auto{cursor:not-allowed;background:#f8fafc !important}
.btn-blue{background:#3b82f6;color:#fff;border:none;padding:8px 20px;border-radius:10px;font-weight:700;font-size:.88rem;cursor:pointer;display:flex;align-items:center;gap:6px}
.btn-blue:hover{background:#2563eb;color:#fff}
@media (max-width:1200px){.biometry-grid-5{grid-template-columns:repeat(3,1fr)}.biometry-grid-4{grid-template-columns:repeat(2,1fr)}}
@media (max-width:768px){.biometry-grid-5,.biometry-grid-4,.biometry-grid-3{grid-template-columns:1fr}}
</style>

<script>
(() => {
    const bipInput = document.querySelector('[name="bip_mm"]');
    const hcInput = document.querySelector('[name="hc_mm"]');
    const acInput = document.querySelector('[name="ac_mm"]');
    const flInput = document.querySelector('[name="fl_mm"]');
    const efwOutput = document.getElementById('efwGrams');

    function calculateHadlockEFW() {
        const bip = parseFloat(bipInput?.value);
        const hc = parseFloat(hcInput?.value);
        const ac = parseFloat(acInput?.value);
        const fl = parseFloat(flInput?.value);

        if (!bip || !hc || !ac || !fl) {
            efwOutput.value = '';
            return;
        }

        const logEFW = 1.326
            - 0.00326 * ac * fl
            + 0.0107 * hc
            + 0.0438 * ac
            + 0.158 * fl;

        const efw = Math.round(Math.pow(10, logEFW));

        if (efw > 0 && efw < 6000) {
            efwOutput.value = efw;
        } else {
            efwOutput.value = '';
        }
    }

    [bipInput, hcInput, acInput, flInput].forEach(input => {
        if (input) {
            input.addEventListener('input', calculateHadlockEFW);
            input.addEventListener('change', calculateHadlockEFW);
        }
    });

    const submitBtn = document.getElementById('submitBiometryForm');
    if (submitBtn) {
        submitBtn.addEventListener('click', async () => {
            const form = document.getElementById('fetalBiometryForm');
            if (!form) return;

            const patientId = typeof getCareSelectedPatientId === 'function' ? getCareSelectedPatientId() : 0;
            if (!patientId) {
                alert('Veuillez sélectionner un patient');
                return;
            }

            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Enregistrement...';

            try {
                const fd = new FormData(form);
                const data = {};
                for (const [key, value] of fd.entries()) {
                    if (value !== '') {
                        data[key] = value;
                    }
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const response = await fetch(`/care/gynecology/patients/${patientId}/ultrasound-biometries`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(data),
                });

                if (!response.ok) {
                    const errors = await response.json().catch(() => ({}));
                    throw new Error(errors.message || `Erreur ${response.status}`);
                }

                const result = await response.json();

                if (window.__careShowToast) {
                    window.__careShowToast(result.message || 'Biométrie enregistrée.', 'success');
                }

                window.dispatchEvent(new Event('care:data-updated'));

                const modal = bootstrap.Modal.getInstance(document.getElementById('fetalBiometryModal'));
                if (modal) modal.hide();

                setTimeout(() => location.reload(), 500);
            } catch (error) {
                if (window.__careShowToast) {
                    window.__careShowToast(error.message, 'error');
                } else {
                    alert(error.message);
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
})();
</script>
<?php /**PATH D:\xampp8.2\htdocs\fils_attente\Modules\Gynecology\Providers/../Resources/views/partials/fetal-biometry-form.blade.php ENDPATH**/ ?>