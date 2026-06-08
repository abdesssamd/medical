<script>
(() => {
    const patientId = typeof getCareSelectedPatientId === 'function' ? getCareSelectedPatientId() : 0;
    if (!patientId) return;

    const baseUrl = `/care/burns/patients/${patientId}`;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function headers() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        };
    }

    async function apiPost(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify(data),
        });
        if (!response.ok) {
            const errors = await response.json().catch(() => ({}));
            throw new Error(errors.message || `Erreur ${response.status}`);
        }
        return response.json();
    }

    async function apiPatch(url, data) {
        const response = await fetch(url, {
            method: 'PATCH',
            headers: headers(),
            body: JSON.stringify(data),
        });
        if (!response.ok) {
            const errors = await response.json().catch(() => ({}));
            throw new Error(errors.message || `Erreur ${response.status}`);
        }
        return response.json();
    }

    function showToast(message, type = 'success') {
        if (window.__careShowToast) {
            window.__careShowToast(message, type);
            return;
        }
        const zone = document.getElementById('clinicalToastZone');
        if (!zone) return;
        const toast = document.createElement('div');
        toast.className = `clinical-toast toast-${type}`;
        toast.innerHTML = `<div class="toast-body">${message}</div>`;
        zone.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 4000);
    }

    function formDataToJson(form) {
        const fd = new FormData(form);
        const data = {};
        for (const [key, value] of fd.entries()) {
            if (key.endsWith('[]')) {
                const cleanKey = key.replace('[]', '');
                if (!data[cleanKey]) data[cleanKey] = [];
                data[cleanKey].push(value);
            } else {
                data[key] = value;
            }
        }
        return data;
    }

    const regionInputs = document.querySelectorAll('.region-input');
    const tbsaValue = document.getElementById('tbsaValue');
    const tbsaSeverity = document.getElementById('tbsaSeverity');
    const bodyRegions = document.querySelectorAll('.body-region');

    function calculateTBSA() {
        let total = 0;
        regionInputs.forEach(input => {
            const val = parseFloat(input.value) || 0;
            const max = parseFloat(input.dataset.maxPercent) || 100;
            if (val > max) {
                input.value = max;
            }
            total += Math.min(val, max);
        });

        total = Math.min(total, 100);

        if (tbsaValue) tbsaValue.textContent = total.toFixed(1) + '%';

        if (tbsaSeverity) {
            let severity = '-';
            let className = '';
            if (total > 0) {
                if (total < 10) { severity = 'Mineure'; className = 'mineure'; }
                else if (total < 20) { severity = 'Modérée'; className = 'modérée'; }
                else if (total < 40) { severity = 'Sévère'; className = 'sévère'; }
                else { severity = 'Critique'; className = 'critique'; }
            }
            tbsaSeverity.textContent = severity;
            tbsaSeverity.className = 'tbsa-severity ' + className;
        }

        updateSVGHighlights();
    }

    function updateSVGHighlights() {
        bodyRegions.forEach(region => {
            const regionKey = region.dataset.region;
            const input = document.querySelector(`.region-input[data-region="${regionKey}"]`);
            if (input) {
                const val = parseFloat(input.value) || 0;
                if (val > 0) {
                    region.classList.add('burned');
                    const intensity = Math.min(val / parseFloat(input.dataset.maxPercent || 1), 1);
                    region.style.fillOpacity = 0.3 + (intensity * 0.7);
                } else {
                    region.classList.remove('burned');
                    region.style.fillOpacity = '';
                }
            }
        });
    }

    regionInputs.forEach(input => {
        input.addEventListener('input', calculateTBSA);
        input.addEventListener('change', calculateTBSA);
    });

    bodyRegions.forEach(region => {
        region.addEventListener('click', () => {
            const regionKey = region.dataset.region;
            const input = document.querySelector(`.region-input[data-region="${regionKey}"]`);
            if (input) {
                const max = parseFloat(input.dataset.maxPercent) || 100;
                const current = parseFloat(input.value) || 0;
                input.value = current > 0 ? 0 : max;
                calculateTBSA();
            }
        });
    });

    const resetBtn = document.getElementById('resetBodyMapping');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            regionInputs.forEach(input => { input.value = 0; });
            calculateTBSA();
        });
    }

    calculateTBSA();

    const submitBodyMappingBtn = document.getElementById('submitBodyMapping');
    if (submitBodyMappingBtn) {
        submitBodyMappingBtn.addEventListener('click', async () => {
            const admissionId = document.getElementById('burn-body-mapping')?.dataset?.admissionId;
            if (!admissionId) { showToast('Aucune admission active', 'error'); return; }

            submitBodyMappingBtn.disabled = true;
            const originalText = submitBodyMappingBtn.innerHTML;
            submitBodyMappingBtn.innerHTML = 'Enregistrement...';

            try {
                const data = {};
                regionInputs.forEach(input => {
                    const key = input.dataset.region + '_percent';
                    data[key] = parseFloat(input.value) || 0;
                });

                const depthRadio = document.querySelector('input[name="depth_dominant"]:checked');
                if (depthRadio) data.depth_dominant = depthRadio.value;

                const result = await apiPost(`/care/burns/admissions/${admissionId}/assessments`, data);
                showToast(result.message || 'Évaluation enregistrée.');
                window.dispatchEvent(new Event('care:data-updated'));
                setTimeout(() => location.reload(), 500);
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                submitBodyMappingBtn.disabled = false;
                submitBodyMappingBtn.innerHTML = originalText;
            }
        });
    }

    const weightInput = document.querySelector('#parklandCalcForm [name="patient_weight_kg"]');
    const scbInput = document.querySelector('#parklandCalcForm [name="burn_surface_area_percent"]');
    const formulaSelect = document.querySelector('#parklandCalcForm [name="formula_used"]');
    const parklandPreview = document.getElementById('parklandPreview');

    function updateParklandPreview() {
        const weight = parseFloat(weightInput?.value) || 0;
        const scb = parseFloat(scbInput?.value) || 0;
        const formula = formulaSelect?.value || 'parkland';

        if (weight <= 0 || scb <= 0 || scb > 100) {
            if (parklandPreview) parklandPreview.style.display = 'none';
            return;
        }

        const multiplier = formula === 'modified_brooke' ? 2 : formula === 'consensus' ? 3 : 4;
        const total = multiplier * weight * scb;
        const phase1Rate = (total * 0.5) / 8;
        const phase2Rate = (total * 0.5) / 16;

        const previewTotal = document.getElementById('previewTotal');
        const previewPhase1 = document.getElementById('previewPhase1');
        const previewPhase2 = document.getElementById('previewPhase2');

        if (previewTotal) previewTotal.textContent = Math.round(total).toLocaleString('fr-FR') + ' ml';
        if (previewPhase1) previewPhase1.textContent = Math.round(phase1Rate).toLocaleString('fr-FR') + ' ml/h';
        if (previewPhase2) previewPhase2.textContent = Math.round(phase2Rate).toLocaleString('fr-FR') + ' ml/h';

        if (parklandPreview) parklandPreview.style.display = 'block';
    }

    if (weightInput) weightInput.addEventListener('input', updateParklandPreview);
    if (scbInput) scbInput.addEventListener('input', updateParklandPreview);
    if (formulaSelect) formulaSelect.addEventListener('change', updateParklandPreview);

    const submitParklandBtn = document.getElementById('submitParklandCalc');
    if (submitParklandBtn) {
        submitParklandBtn.addEventListener('click', async () => {
            const form = document.getElementById('parklandCalcForm');
            if (!form) return;

            const admissionId = document.getElementById('fluid-resuscitation-dashboard')?.dataset?.admissionId;
            if (!admissionId) { showToast('Aucune admission active', 'error'); return; }

            submitParklandBtn.disabled = true;
            const originalText = submitParklandBtn.innerHTML;
            submitParklandBtn.innerHTML = 'Calcul...';

            try {
                const data = formDataToJson(form);
                const result = await apiPost(`/care/burns/admissions/${admissionId}/fluid-resuscitation`, data);
                showToast(result.message || 'Réanimation hydrique calculée.');
                window.dispatchEvent(new Event('care:data-updated'));
                const modal = bootstrap.Modal.getInstance(document.getElementById('parklandCalcModal'));
                if (modal) modal.hide();
                setTimeout(() => location.reload(), 500);
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                submitParklandBtn.disabled = false;
                submitParklandBtn.innerHTML = originalText;
            }
        });
    }

    const submitWoundBtn = document.getElementById('submitWoundEvolution');
    if (submitWoundBtn) {
        submitWoundBtn.addEventListener('click', async () => {
            const form = document.getElementById('woundEvolutionForm');
            if (!form) return;

            const admissionId = document.getElementById('wound-evolution-gallery')?.dataset?.admissionId;
            if (!admissionId) { showToast('Aucune admission active', 'error'); return; }

            submitWoundBtn.disabled = true;
            const originalText = submitWoundBtn.innerHTML;
            submitWoundBtn.innerHTML = 'Enregistrement...';

            try {
                const data = formDataToJson(form);
                const result = await apiPost(`/care/burns/admissions/${admissionId}/wound-evolutions`, data);
                showToast(result.message || 'Évolution enregistrée.');
                window.dispatchEvent(new Event('care:data-updated'));
                const modal = bootstrap.Modal.getInstance(document.getElementById('woundEvolutionModal'));
                if (modal) modal.hide();
                setTimeout(() => location.reload(), 500);
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                submitWoundBtn.disabled = false;
                submitWoundBtn.innerHTML = originalText;
            }
        });
    }

    document.querySelectorAll('.send-pharmacy-order-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const evolutionId = btn.dataset.evolutionId;
            if (!evolutionId) return;

            btn.disabled = true;
            try {
                const result = await apiPost(`/care/burns/wound-evolutions/${evolutionId}/pharmacy-order`, {});
                showToast(result.message || 'Bon de commande envoyé.');
                setTimeout(() => location.reload(), 400);
            } catch (error) {
                showToast(error.message, 'error');
                btn.disabled = false;
            }
        });
    });

    const countdownEl = document.getElementById('parklandCountdown');
    if (countdownEl) {
        let remainingMinutes = parseInt(countdownEl.dataset.remainingMinutes) || 0;

        setInterval(() => {
            if (remainingMinutes <= 0) return;
            remainingMinutes--;
            const hours = Math.floor(remainingMinutes / 60);
            const mins = remainingMinutes % 60;
            countdownEl.textContent = hours > 0 ? `${hours}h ${mins}min` : `${mins}min`;
        }, 60000);
    }

    console.log('Burns module initialized for patient', patientId);
})();
</script>
