<script>
(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const getPatientId = () => typeof getCareSelectedPatientId === 'function' ? getCareSelectedPatientId() : 0;
    const rehabBase = '/care/rehab';

    const apiPost = async (url, data) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(data),
        });
        return response;
    };

    const painSlider = document.getElementById('painSlider');
    const painValue = document.getElementById('painValue');
    const painLabel = document.getElementById('painLabel');

    if (painSlider) {
        const updatePainDisplay = (value) => {
            painValue.textContent = value;
            const labels = {
                0: 'Aucune douleur',
                1: 'Très légère',
                2: 'Légère',
                3: 'Modérée légère',
                4: 'Modérée',
                5: 'Modérée forte',
                6: 'Forte',
                7: 'Très forte',
                8: 'Intense',
                9: 'Très intense',
                10: 'Maximale',
            };
            painLabel.textContent = labels[value] || '';

            painValue.className = 'badge fs-4 ' + (value <= 3 ? 'bg-success' : (value <= 6 ? 'bg-warning' : 'bg-danger'));
        };

        painSlider.addEventListener('input', (e) => updatePainDisplay(e.target.value));
        updatePainDisplay(painSlider.value);
    }

    const evalTypeBtns = document.querySelectorAll('[data-eval-type]');
    evalTypeBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const typeInput = document.getElementById('rehabEvalType');
            if (typeInput) typeInput.value = btn.dataset.evalType;
        });
    });

    const addGonioRow = document.getElementById('addGonioRow');
    if (addGonioRow) {
        let gonioCount = 1;
        addGonioRow.addEventListener('click', () => {
            const container = document.getElementById('goniometryFields');
            const row = document.createElement('div');
            row.className = 'gonio-row mb-2';
            row.innerHTML = `
                <div class="row g-2">
                    <div class="col-md-4"><input type="text" class="form-control form-control-sm" placeholder="Articulation" name="goniometry[${gonioCount}][joint]"></div>
                    <div class="col-md-4"><input type="number" class="form-control form-control-sm" placeholder="Flexion (°)" name="goniometry[${gonioCount}][flexion]"></div>
                    <div class="col-md-4"><input type="number" class="form-control form-control-sm" placeholder="Extension (°)" name="goniometry[${gonioCount}][extension]"></div>
                </div>
            `;
            container.insertBefore(row, addGonioRow);
            gonioCount++;
        });
    }

    const addMuscleRow = document.getElementById('addMuscleRow');
    if (addMuscleRow) {
        let muscleCount = 1;
        addMuscleRow.addEventListener('click', () => {
            const container = document.getElementById('muscleTestingFields');
            const row = document.createElement('div');
            row.className = 'muscle-row mb-2';
            row.innerHTML = `
                <div class="row g-2">
                    <div class="col-md-6"><input type="text" class="form-control form-control-sm" placeholder="Groupe musculaire" name="muscle_testing[${muscleCount}][group]"></div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="muscle_testing[${muscleCount}][grade]">
                            <option value="">Grade</option>
                            <option value="0">0 - Aucune contraction</option>
                            <option value="1">1 - Contraction palpable</option>
                            <option value="2">2 - Mouvement sans gravité</option>
                            <option value="3">3 - Mouvement contre gravité</option>
                            <option value="4">4 - Mouvement contre résistance</option>
                            <option value="5">5 - Force normale</option>
                        </select>
                    </div>
                    <div class="col-md-3"><input type="text" class="form-control form-control-sm" placeholder="Côté" name="muscle_testing[${muscleCount}][side]"></div>
                </div>
            `;
            container.insertBefore(row, addMuscleRow);
            muscleCount++;
        });
    }

    const prescriptionForm = document.getElementById('rehabPrescriptionForm');
    if (prescriptionForm) {
        prescriptionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(prescriptionForm);
            const data = Object.fromEntries(formData.entries());

            const submitBtn = prescriptionForm.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const patientId = getPatientId();
                if (!patientId) {
                    window.__careShowToast?.('Aucun patient sélectionné.', 'error');
                    return;
                }
                const response = await apiPost(`${rehabBase}/patients/${patientId}/prescriptions`, data);
                const result = await response.json().catch(() => ({}));

                if (!response.ok) {
                    window.__careShowToast?.(result.message || 'Erreur lors de la création.', 'error');
                    return;
                }

                window.__careShowToast?.(result.message || 'Prescription créée.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('rehabPrescriptionModal'))?.hide();
                window.dispatchEvent(new Event('care:data-updated'));
                setTimeout(() => location.reload(), 800);
            } catch (err) {
                window.__careShowToast?.('Erreur réseau.', 'error');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }

    const evaluationForm = document.getElementById('rehabEvaluationForm');
    if (evaluationForm) {
        evaluationForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(evaluationForm);
            const data = Object.fromEntries(formData.entries());

            const goniometry = [];
            document.querySelectorAll('.gonio-row').forEach((row) => {
                const joint = row.querySelector('[name*="[joint]"]')?.value;
                const flexion = row.querySelector('[name*="[flexion]"]')?.value;
                const extension = row.querySelector('[name*="[extension]"]')?.value;
                if (joint) goniometry.push({ joint, flexion: flexion ? Number(flexion) : null, extension: extension ? Number(extension) : null });
            });
            data.goniometry = goniometry;

            const muscleTesting = [];
            document.querySelectorAll('.muscle-row').forEach((row) => {
                const group = row.querySelector('[name*="[group]"]')?.value;
                const grade = row.querySelector('[name*="[grade]"]')?.value;
                const side = row.querySelector('[name*="[side]"]')?.value;
                if (group) muscleTesting.push({ group, grade: grade ? Number(grade) : null, side });
            });
            data.muscle_testing = muscleTesting;

            const activePrescriptionId = document.getElementById('rehab-doctor-dashboard')?.dataset.prescriptionId;
            if (!activePrescriptionId) {
                window.__careShowToast?.('Aucune prescription active.', 'error');
                return;
            }

            const submitBtn = evaluationForm.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const response = await apiPost(`${rehabBase}/prescriptions/${activePrescriptionId}/evaluations`, data);
                const result = await response.json().catch(() => ({}));

                if (!response.ok) {
                    window.__careShowToast?.(result.message || 'Erreur lors de l\'enregistrement.', 'error');
                    return;
                }

                window.__careShowToast?.(result.message || 'Bilan enregistré.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('rehabEvaluationModal'))?.hide();
                window.dispatchEvent(new Event('care:data-updated'));
                setTimeout(() => location.reload(), 800);
            } catch (err) {
                window.__careShowToast?.('Erreur réseau.', 'error');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }

    const sessionForm = document.getElementById('rehabSessionForm');
    if (sessionForm) {
        sessionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(sessionForm);
            const data = Object.fromEntries(formData.entries());

            const exercises = [];
            document.querySelectorAll('#exercisesChecklist input[type="checkbox"]:checked').forEach((cb) => {
                exercises.push(cb.value);
            });
            data.exercises_performed = exercises;

            const activePrescriptionId = document.getElementById('rehab-physio-tracker')?.dataset.prescriptionId;
            if (!activePrescriptionId) {
                window.__careShowToast?.('Aucune prescription active.', 'error');
                return;
            }

            const submitBtn = sessionForm.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const response = await apiPost(`${rehabBase}/prescriptions/${activePrescriptionId}/sessions`, data);
                const result = await response.json().catch(() => ({}));

                if (!response.ok) {
                    window.__careShowToast?.(result.message || 'Erreur lors de la validation.', 'error');
                    return;
                }

                window.__careShowToast?.(result.message || 'Séance validée.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('rehabSessionModal'))?.hide();
                window.dispatchEvent(new Event('care:data-updated'));
                setTimeout(() => location.reload(), 800);
            } catch (err) {
                window.__careShowToast?.('Erreur réseau.', 'error');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }
})();
</script>
