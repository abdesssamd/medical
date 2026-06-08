<script>
(() => {
    const patientId = typeof getCareSelectedPatientId === 'function' ? getCareSelectedPatientId() : 0;
    if (!patientId) return;

    const baseUrl = `/care/pediatrics/patients/${patientId}`;
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

    const neonatalForm = document.getElementById('neonatalHistoryForm');
    if (neonatalForm) {
        neonatalForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = neonatalForm.querySelector('[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Enregistrement...';

            try {
                const data = formDataToJson(neonatalForm);
                const result = await apiPost(`${baseUrl}/birth-history`, data);
                showToast(result.message || 'Antécédents néonataux enregistrés.');
                window.dispatchEvent(new Event('care:data-updated'));
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }

    const submitGrowthBtn = document.getElementById('submitGrowthRecord');
    if (submitGrowthBtn) {
        submitGrowthBtn.addEventListener('click', async () => {
            const form = document.getElementById('growthRecordForm');
            if (!form) return;

            submitGrowthBtn.disabled = true;
            const originalText = submitGrowthBtn.innerHTML;
            submitGrowthBtn.innerHTML = 'Enregistrement...';

            try {
                const data = formDataToJson(form);
                const result = await apiPost(`${baseUrl}/growth-record`, data);
                showToast(result.message || 'Mesure enregistrée.');
                window.dispatchEvent(new Event('care:data-updated'));

                const modal = bootstrap.Modal.getInstance(document.getElementById('growthRecordModal'));
                if (modal) modal.hide();

                setTimeout(() => location.reload(), 500);
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                submitGrowthBtn.disabled = false;
                submitGrowthBtn.innerHTML = originalText;
            }
        });
    }

    const submitVaccBtn = document.getElementById('submitVaccinationRecord');
    if (submitVaccBtn) {
        submitVaccBtn.addEventListener('click', async () => {
            const form = document.getElementById('vaccinationRecordForm');
            if (!form) return;

            submitVaccBtn.disabled = true;
            const originalText = submitVaccBtn.innerHTML;
            submitVaccBtn.innerHTML = 'Enregistrement...';

            try {
                const data = formDataToJson(form);
                const result = await apiPost(`${baseUrl}/vaccination-record`, data);
                showToast(result.message || 'Vaccination enregistrée.');
                window.dispatchEvent(new Event('care:data-updated'));

                const modal = bootstrap.Modal.getInstance(document.getElementById('vaccinationRecordModal'));
                if (modal) modal.hide();

                setTimeout(() => location.reload(), 500);
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                submitVaccBtn.disabled = false;
                submitVaccBtn.innerHTML = originalText;
            }
        });
    }

    document.querySelectorAll('.vacc-administer-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const vaccineId = btn.dataset.vaccineId;
            const scheduledDate = btn.dataset.scheduledDate;

            const vaccineSelect = document.getElementById('vaccineSelect');
            const vaccScheduledDate = document.getElementById('vaccScheduledDate');

            if (vaccineSelect) vaccineSelect.value = vaccineId;
            if (vaccScheduledDate) vaccScheduledDate.value = scheduledDate;

            const modal = new bootstrap.Modal(document.getElementById('vaccinationRecordModal'));
            modal.show();
        });
    });

    const refreshBtn = document.getElementById('pediaRefreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => location.reload());
    }

    console.log('Pediatrics module initialized for patient', patientId);
})();
</script>
