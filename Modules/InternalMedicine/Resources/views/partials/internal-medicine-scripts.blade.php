<script>
document.addEventListener('DOMContentLoaded', () => {
    const patientId = {{ $selectedPatientId ?? 0 }};

    if (!patientId) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const apiBase = '/care/internal-medicine';

    async function apiPost(url, data) {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data),
        });
        if (!r.ok) {
            const err = await r.json().catch(() => ({ message: 'Erreur serveur' }));
            throw new Error(err.message || `HTTP ${r.status}`);
        }
        return r.json();
    }

    function getPatientId() {
        return patientId;
    }

    // Chronic Condition CRUD
    const condForm = document.getElementById('chronicConditionForm');
    if (condForm) {
        const condModal = document.getElementById('chronicConditionModal');
        condModal?.addEventListener('show.bs.modal', () => {
            if (!document.getElementById('conditionId')?.value) {
                document.getElementById('chronicConditionModalTitle').textContent = '{{ __('internal_med.add_condition') }}';
                condForm.reset();
                document.getElementById('conditionId').value = '';
            }
        });

        condForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const pid = getPatientId();
            const id = document.getElementById('conditionId')?.value;
            const data = Object.fromEntries(new FormData(condForm));
            const url = id
                ? `/care/internal-medicine/conditions/${id}`
                : `/care/internal-medicine/patients/${pid}/conditions`;

            try {
                const result = await apiPost(url, data);
                showToast(result.message);
                bootstrap.Modal.getInstance(document.getElementById('chronicConditionModal'))?.hide();
                setTimeout(() => location.reload(), 400);
            } catch (err) {
                showToast(err.message, 'error');
            }
        });
    }

    document.querySelectorAll('.edit-condition-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('chronicConditionModalTitle').textContent = 'Modifier la pathologie';
            document.getElementById('conditionId').value = btn.dataset.id;
            document.getElementById('condNameInput').value = btn.dataset.name;
            document.getElementById('condIcd10Input').value = btn.dataset.icd10;
            document.getElementById('condDateInput').value = btn.dataset.date;
            document.getElementById('condStatusInput').value = btn.dataset.status;
            document.getElementById('condNotesInput').value = btn.dataset.notes;
            new bootstrap.Modal(document.getElementById('chronicConditionModal')).show();
        });
    });

    document.querySelectorAll('.delete-condition-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Confirmer la suppression ?')) return;
            try {
                const result = await apiPost(`/care/internal-medicine/conditions/${btn.dataset.id}`, { _method: 'DELETE' });
                showToast(result.message);
                location.reload();
            } catch (err) {
                showToast(err.message, 'error');
            }
        });
    });

    // CHA2DS2-VASc Calculator (real-time)
    function calculateChads() {
        const fields = document.querySelectorAll('.chads-field');
        let score = 0;
        const details = [];

        fields.forEach(f => {
            if (f.checked) {
                const key = f.dataset.key;
                const pts = (key === 'age_over_75' || key === 'stroke') ? 2 : 1;
                score += pts;
            }
        });

        const sex = document.getElementById('chadsSex')?.value;
        if (sex === 'female') { score += 1; }

        const display = document.getElementById('chadsScoreDisplay');
        const riskDisplay = document.getElementById('chadsRiskDisplay');
        if (display) display.textContent = score;

        if (riskDisplay) {
            if (score >= 3) riskDisplay.innerHTML = '<span class="text-danger fw-bold">Risque élevé - Anticoagulation recommandée</span>';
            else if (score >= 2) riskDisplay.innerHTML = '<span class="text-warning fw-bold">Risque modéré - Anticoagulation à discuter</span>';
            else riskDisplay.innerHTML = '<span class="text-success fw-bold">Risque faible - Pas d\'anticoagulation</span>';
        }
    }

    document.querySelectorAll('.chads-field').forEach(f => f.addEventListener('change', calculateChads));
    document.getElementById('chadsSex')?.addEventListener('change', calculateChads);
    calculateChads();

    document.getElementById('saveChadsScore')?.addEventListener('click', async () => {
        const pid = getPatientId();
        const score = parseInt(document.getElementById('chadsScoreDisplay')?.textContent || '0');
        try {
            const result = await apiPost(`/care/internal-medicine/patients/${pid}/scores`, {
                score_type: 'CHA2DS2-VASc',
                calculated_value: score,
                date: new Date().toISOString().split('T')[0],
            });
            showToast(result.message);
        } catch (err) {
            showToast(err.message, 'error');
        }
    });

    // Kidney function calculator (real-time)
    function calculateKidney() {
        const age = parseFloat(document.getElementById('kidneyAge')?.value) || 0;
        const weight = parseFloat(document.getElementById('kidneyWeight')?.value) || 0;
        const creatinine = parseFloat(document.getElementById('kidneyCreatinine')?.value) || 0;
        const sex = document.getElementById('kidneySex')?.value || 'male';

        if (age > 0 && weight > 0 && creatinine > 0) {
            const cockcroft = ((140 - age) * weight) / (72 * creatinine) * (sex === 'female' ? 0.85 : 1);
            const mdrd = 175 * Math.pow(creatinine, -1.154) * Math.pow(age, -0.203) * (sex === 'female' ? 0.742 : 1);

            document.getElementById('cockcroftDisplay').textContent = cockcroft.toFixed(1);
            document.getElementById('mdrdDisplay').textContent = mdrd.toFixed(1);
        } else {
            document.getElementById('cockcroftDisplay').textContent = '-';
            document.getElementById('mdrdDisplay').textContent = '-';
        }
    }

    ['kidneyAge', 'kidneyWeight', 'kidneyCreatinine', 'kidneySex'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', calculateKidney);
        document.getElementById(id)?.addEventListener('change', calculateKidney);
    });

    document.getElementById('saveKidneyScore')?.addEventListener('click', async () => {
        const pid = getPatientId();
        const cockcroft = parseFloat(document.getElementById('cockcroftDisplay')?.textContent) || 0;
        const mdrd = parseFloat(document.getElementById('mdrdDisplay')?.textContent) || 0;
        try {
            await apiPost(`/care/internal-medicine/patients/${pid}/scores`, {
                score_type: 'Cockcroft-Gault',
                calculated_value: cockcroft,
                date: new Date().toISOString().split('T')[0],
            });
            await apiPost(`/care/internal-medicine/patients/${pid}/scores`, {
                score_type: 'MDRD',
                calculated_value: mdrd,
                date: new Date().toISOString().split('T')[0],
            });
            showToast('Scores rénaux enregistrés.');
        } catch (err) {
            showToast(err.message, 'error');
        }
    });

    // BMI / BSA calculator (real-time)
    function calculateBmi() {
        const weight = parseFloat(document.getElementById('bmiWeight')?.value) || 0;
        const height = parseFloat(document.getElementById('bmiHeight')?.value) || 0;

        if (weight > 0 && height > 0) {
            const hM = height / 100;
            const bmi = weight / (hM * hM);
            const bsa = Math.sqrt((height * weight) / 3600);

            document.getElementById('bmiDisplay').textContent = bmi.toFixed(1);
            document.getElementById('bsaDisplay').textContent = bsa.toFixed(2);
        } else {
            document.getElementById('bmiDisplay').textContent = '-';
            document.getElementById('bsaDisplay').textContent = '-';
        }
    }

    ['bmiWeight', 'bmiHeight'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', calculateBmi);
        document.getElementById(id)?.addEventListener('change', calculateBmi);
    });

    document.getElementById('saveBmiScore')?.addEventListener('click', async () => {
        const pid = getPatientId();
        const bmi = parseFloat(document.getElementById('bmiDisplay')?.textContent) || 0;
        const bsa = parseFloat(document.getElementById('bsaDisplay')?.textContent) || 0;
        try {
            await apiPost(`/care/internal-medicine/patients/${pid}/scores`, {
                score_type: 'BMI',
                calculated_value: bmi,
                date: new Date().toISOString().split('T')[0],
            });
            await apiPost(`/care/internal-medicine/patients/${pid}/scores`, {
                score_type: 'BSA',
                calculated_value: bsa,
                date: new Date().toISOString().split('T')[0],
            });
            showToast('IMC et SC enregistrés.');
        } catch (err) {
            showToast(err.message, 'error');
        }
    });

    // Lab Result submission
    const labForm = document.getElementById('labResultForm');
    if (labForm) {
        labForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const pid = getPatientId();
            const formData = new FormData(labForm);
            const data = {};
            data.test_date = formData.get('test_date');
            data.parameters = {};
            for (const [key, value] of formData.entries()) {
                if (key.startsWith('parameters[')) {
                    const paramKey = key.match(/parameters\[(.+?)\]/)[1];
                    if (value) data.parameters[paramKey] = parseFloat(value);
                }
            }
            try {
                const result = await apiPost(`/care/internal-medicine/patients/${pid}/lab-results`, data);
                showToast(result.message);
                bootstrap.Modal.getInstance(document.getElementById('labResultModal'))?.hide();
                setTimeout(() => location.reload(), 400);
            } catch (err) {
                showToast(err.message, 'error');
            }
        });
    }

    // Lab Charts (Chart.js)
    const labChartData = @json($labChartData ?? []);

    const chartDefaults = {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { font: { size: 9 }, maxRotation: 45 }, grid: { display: false } },
                y: { beginAtZero: false, ticks: { font: { size: 9 } } },
            },
            animation: { duration: 300 },
        },
    };

    const keyRefs = {
        hba1c: { ref: 7, refMin: 4, refMax: 6, label: 'HbA1c (%)' },
        creatinine: { refMax: 1.2, label: 'Créatinine (mg/dL)' },
        cholesterol_ldl: { refMax: 1.6, label: 'LDL (g/L)' },
        cholesterol_hdl: { refMin: 0.4, label: 'HDL (g/L)' },
        triglycerides: { refMax: 1.5, label: 'TG (g/L)' },
        potassium: { refMin: 3.5, refMax: 5.0, label: 'K⁺ (mmol/L)' },
        sodium: { refMin: 136, refMax: 146, label: 'Na⁺ (mmol/L)' },
        tsh: { refMin: 0.4, refMax: 4.0, label: 'TSH (mUI/L)' },
    };

    Object.entries(labChartData).forEach(([key, dataPoints]) => {
        const canvas = document.getElementById(`labChart_${key}`);
        if (!canvas || !dataPoints?.length) return;

        const refs = keyRefs[key];
        if (!refs) return;

        const labels = dataPoints.map(d => d.date);
        const values = dataPoints.map(d => d.value);

        const annotationLines = [];
        if (refs.ref !== undefined) annotationLines.push({ value: refs.ref, label: `Cible: ${refs.ref}${refs.label.includes('%') ? '%' : ''}`, color: 'red' });
        if (refs.refMin !== undefined) annotationLines.push({ value: refs.refMin, label: `Min: ${refs.refMin}`, color: 'green' });
        if (refs.refMax !== undefined && refs.ref !== undefined) annotationLines.push({ value: refs.refMax, label: `Max: ${refs.refMax}`, color: 'green' });
        else if (refs.refMax !== undefined) annotationLines.push({ value: refs.refMax, label: `Max: ${refs.refMax}`, color: 'orange' });

        const datasets = [{
            label: refs.label,
            data: values,
            borderColor: '#dc2626',
            backgroundColor: 'rgba(220, 38, 38, 0.08)',
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointHoverRadius: 5,
            spanGaps: true,
        }];

        annotationLines.forEach(line => {
            datasets.push({
                label: line.label,
                data: Array(labels.length).fill(line.value),
                borderColor: line.color,
                borderDash: [4, 4],
                pointRadius: 0,
                fill: false,
                borderWidth: 1.5,
            });
        });

        new Chart(canvas, {
            ...chartDefaults,
            data: { labels, datasets },
            options: {
                ...chartDefaults.options,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                if (ctx.dataset.label === refs.label) return `${ctx.parsed.y} ${refs.label.includes('(') ? refs.label.match(/\((.+?)\)/)?.[1] || '' : ''}`;
                                return ctx.dataset.label;
                            },
                        },
                    },
                },
            },
        });
    });
});
</script>
