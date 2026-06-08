<script>
(() => {
    const patientId = typeof getCareSelectedPatientId === 'function' ? getCareSelectedPatientId() : 0;
    if (!patientId) return;

    const baseUrl = `/care/gynecology/patients/${patientId}`;
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

    async function apiGet(url) {
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) throw new Error(`Erreur ${response.status}`);
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

    const lmpInput = document.getElementById('lmpDate');
    const eddInput = document.getElementById('estimatedDeliveryDate');
    if (lmpInput && eddInput) {
        lmpInput.addEventListener('change', () => {
            if (!lmpInput.value) return;
            const lmp = new Date(lmpInput.value);
            const edd = new Date(lmp.getTime() + 280 * 24 * 60 * 60 * 1000);
            eddInput.value = edd.toISOString().slice(0, 10);
        });
    }

    document.querySelectorAll('.gyneco-chips').forEach((container) => {
        container.querySelectorAll('.gyneco-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                const checkbox = chip.querySelector('input[type="checkbox"]');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    chip.classList.toggle('active', checkbox.checked);
                }
            });
        });
    });

    const submitHistoryBtn = document.getElementById('submitGynecologicalHistory');
    if (submitHistoryBtn) {
        submitHistoryBtn.addEventListener('click', async () => {
            const form = document.getElementById('gynecologicalHistoryForm');
            if (!form) return;

            submitHistoryBtn.disabled = true;
            const originalText = submitHistoryBtn.innerHTML;
            submitHistoryBtn.innerHTML = 'Enregistrement...';

            try {
                const data = formDataToJson(form);
                const result = await apiPost(`${baseUrl}/gynecological-history`, data);
                showToast(result.message || 'Antécédents enregistrés.');
                window.dispatchEvent(new Event('care:data-updated'));

                const modal = bootstrap.Modal.getInstance(document.getElementById('gynecologicalHistoryModal'));
                if (modal) modal.hide();

                setTimeout(() => location.reload(), 400);
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                submitHistoryBtn.disabled = false;
                submitHistoryBtn.innerHTML = originalText;
            }
        });
    }

    const submitPregnancyBtn = document.getElementById('submitPregnancyRecord');
    if (submitPregnancyBtn) {
        submitPregnancyBtn.addEventListener('click', async () => {
            const form = document.getElementById('pregnancyRecordForm');
            if (!form) return;

            submitPregnancyBtn.disabled = true;
            const originalText = submitPregnancyBtn.innerHTML;
            submitPregnancyBtn.innerHTML = 'Enregistrement...';

            try {
                const data = formDataToJson(form);
                const result = await apiPost(`${baseUrl}/pregnancy-record`, data);
                showToast(result.message || 'Dossier obstétrical enregistré.');
                window.dispatchEvent(new Event('care:data-updated'));

                const modal = bootstrap.Modal.getInstance(document.getElementById('pregnancyRecordModal'));
                if (modal) modal.hide();

                loadDashboard();
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                submitPregnancyBtn.disabled = false;
                submitPregnancyBtn.innerHTML = originalText;
            }
        });
    }

    const refreshBtn = document.getElementById('gynecoRefreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => loadDashboard());
    }

    async function loadDashboard() {
        try {
            const data = await apiGet(`${baseUrl}/dashboard`);
            console.log('Gynecology dashboard loaded', data);
        } catch (error) {
            console.error('Failed to load gynecology dashboard:', error);
        }
    }

    document.querySelectorAll('.qe-risk-option').forEach(option => {
        option.addEventListener('click', () => {
            document.querySelectorAll('.qe-risk-option').forEach(o => {
                o.classList.remove('active');
                o.style.borderColor = '#e2e8f0';
                o.style.background = '#fff';
            });
            option.classList.add('active');
            const radio = option.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    const submitRiskBtn = document.getElementById('submitQuickEditRisk');
    if (submitRiskBtn) {
        submitRiskBtn.addEventListener('click', async () => {
            const form = document.getElementById('quickEditRiskForm');
            if (!form) return;
            const selected = form.querySelector('input[name="risk_level"]:checked');
            if (!selected) return;

            const pregnancyId = document.getElementById('gynecology-dashboard')?.dataset?.pregnancyId;
            if (!pregnancyId) { showToast('ID grossesse introuvable', 'error'); return; }

            submitRiskBtn.disabled = true;
            try {
                const result = await apiPatch(`${baseUrl}/pregnancy/${pregnancyId}/quick-edit`, {
                    risk_level: selected.value,
                });
                showToast(result.message || 'Risque mis à jour.');
                window.dispatchEvent(new Event('care:data-updated'));
                const modal = bootstrap.Modal.getInstance(document.getElementById('quickEditRiskModal'));
                if (modal) modal.hide();
                setTimeout(() => location.reload(), 400);
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                submitRiskBtn.disabled = false;
            }
        });
    }

    const submitWeightBtn = document.getElementById('submitQuickEditWeight');
    if (submitWeightBtn) {
        submitWeightBtn.addEventListener('click', async () => {
            const form = document.getElementById('quickEditWeightForm');
            if (!form) return;

            const pregnancyId = document.getElementById('gynecology-dashboard')?.dataset?.pregnancyId;
            if (!pregnancyId) { showToast('ID grossesse introuvable', 'error'); return; }

            submitWeightBtn.disabled = true;
            try {
                const data = formDataToJson(form);
                const result = await apiPost(`/care/gynecology/pregnancy-records/${pregnancyId}/prenatal-visits`, data);
                showToast(result.message || 'Visite enregistrée.');
                window.dispatchEvent(new Event('care:data-updated'));
                const modal = bootstrap.Modal.getInstance(document.getElementById('quickEditWeightModal'));
                if (modal) modal.hide();
                setTimeout(() => location.reload(), 400);
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                submitWeightBtn.disabled = false;
            }
        });
    }

    console.log('Gynecology module initialized for patient', patientId);
})();
</script>
