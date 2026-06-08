@props(['dashboard' => null, 'selectedPatientId' => 0])

@if($dashboard && $dashboard['active_pregnancy'])
<section id="fetal-biometry-chart" class="card gyneco-card" data-care-tab-panel="clinical">
    <div class="section-head">
        <h3 class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-pink-500"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>
            Courbes de Biométrie Fœtale
        </h3>
        <div class="biom-chart-tabs">
            <button type="button" class="biom-tab active" data-biom-tab="efw">Poids Estimé (EPF)</button>
            <button type="button" class="biom-tab" data-biom-tab="bip">BIP</button>
            <button type="button" class="biom-tab" data-biom-tab="ac">PA</button>
            <button type="button" class="biom-tab" data-biom-tab="fl">LF</button>
        </div>
    </div>

    <div class="biom-chart-container">
        <canvas id="fetalBiometryChart" height="320"></canvas>
    </div>

    <div class="biom-legend-bar">
        <span class="biom-legend-item"><span class="biom-legend-dot biom-dot-patient"></span> Patient</span>
        <span class="biom-legend-item"><span class="biom-legend-dot biom-dot-p90"></span> P90</span>
        <span class="biom-legend-item"><span class="biom-legend-dot biom-dot-p50"></span> P50</span>
        <span class="biom-legend-item"><span class="biom-legend-dot biom-dot-p10"></span> P10</span>
    </div>
</section>

<style>
.biom-chart-tabs{display:flex;gap:4px}
.biom-tab{padding:5px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:.76rem;font-weight:700;color:#64748b;cursor:pointer;transition:all .15s ease}
.biom-tab:hover{border-color:#f9a8d4;color:#db2777}
.biom-tab.active{background:#fce7f3;border-color:#f9a8d4;color:#be185d}
.biom-chart-container{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px;min-height:340px}
.biom-legend-bar{display:flex;gap:16px;justify-content:center;padding:10px 0 0}
.biom-legend-item{display:flex;align-items:center;gap:6px;font-size:.78rem;font-weight:600;color:#64748b}
.biom-legend-dot{width:10px;height:10px;border-radius:50%;display:inline-block}
.biom-dot-patient{background:#db2777}
.biom-dot-p90{background:rgba(239,68,68,.5)}
.biom-dot-p50{background:rgba(34,197,94,.6)}
.biom-dot-p10{background:rgba(59,130,246,.5)}
</style>

<script>
(() => {
    const patientId = typeof getCareSelectedPatientId === 'function' ? getCareSelectedPatientId() : 0;
    if (!patientId) return;

    const baseUrl = `/care/gynecology/patients/${patientId}`;
    let chart = null;
    let chartData = [];
    let refCurves = {};

    const config = {
        efw: { label: 'Poids Estimé (g)', yLabel: 'grammes', mainKey: 'efw_grams', mainColor: '#db2777', refKey: 'efw' },
        bip: { label: 'BIP (mm)', yLabel: 'mm', mainKey: 'bip_mm', mainColor: '#8b5cf6', refKey: 'bip' },
        ac: { label: 'Périmètre Abdominal (mm)', yLabel: 'mm', mainKey: 'ac_mm', mainColor: '#0891b2', refKey: null },
        fl: { label: 'Longueur Fémorale (mm)', yLabel: 'mm', mainKey: 'fl_mm', mainColor: '#d97706', refKey: null },
    };

    async function loadBiometryData() {
        try {
            const resp = await fetch(`${baseUrl}/biometry-chart`, { headers: { 'Accept': 'application/json' } });
            if (!resp.ok) return;
            const data = await resp.json();
            chartData = data.chart_data || [];
            refCurves = data.reference_curves || {};
            renderChart('efw');
        } catch (e) {
            console.error('Biometry chart load failed', e);
        }
    }

    function renderChart(type) {
        const ctx = document.getElementById('fetalBiometryChart');
        if (!ctx) return;
        if (chart) chart.destroy();

        const cfg = config[type] || config.efw;
        const datasets = [];

        if (cfg.refKey && refCurves[cfg.refKey]) {
            const ref = refCurves[cfg.refKey];
            const weeks = Object.keys(ref).map(Number).sort((a, b) => a - b);

            datasets.push({
                label: 'P90',
                data: weeks.map(w => ({ x: w, y: ref[w].p90 })),
                borderColor: 'rgba(239, 68, 68, 0.4)',
                borderWidth: 1.5,
                borderDash: [6, 4],
                pointRadius: 0,
                fill: false,
                tension: 0.4,
            });

            datasets.push({
                label: 'P50',
                data: weeks.map(w => ({ x: w, y: ref[w].p50 })),
                borderColor: 'rgba(34, 197, 94, 0.5)',
                borderWidth: 2,
                pointRadius: 0,
                fill: false,
                tension: 0.4,
            });

            datasets.push({
                label: 'P10',
                data: weeks.map(w => ({ x: w, y: ref[w].p10 })),
                borderColor: 'rgba(59, 130, 246, 0.4)',
                borderWidth: 1.5,
                borderDash: [6, 4],
                pointRadius: 0,
                fill: false,
                tension: 0.4,
            });

            datasets.push({
                label: 'Zone normale',
                data: weeks.map(w => ({ x: w, y: ref[w].p90 })),
                borderColor: 'transparent',
                backgroundColor: 'rgba(34, 197, 94, 0.06)',
                pointRadius: 0,
                fill: '+1',
                tension: 0.4,
            });
        }

        const patientPoints = chartData
            .filter(d => d[cfg.mainKey] && d.ga_weeks)
            .map(d => ({ x: d.ga_weeks, y: d[cfg.mainKey] }));

        datasets.push({
            label: 'Patient',
            data: patientPoints,
            borderColor: cfg.mainColor,
            backgroundColor: cfg.mainColor,
            borderWidth: 3,
            pointRadius: 7,
            pointHoverRadius: 10,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            fill: false,
            tension: 0.3,
        });

        chart = new Chart(ctx, {
            type: 'line',
            data: { datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'nearest', intersect: true },
                plugins: {
                    title: {
                        display: true,
                        text: cfg.label + ' — Courbe de croissance fœtale',
                        font: { size: 13, weight: 'bold' },
                        color: '#0f172a',
                        padding: { bottom: 16 },
                    },
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 11 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            title: (items) => items[0]?.parsed?.x ? items[0].parsed.x + ' SA' : '',
                            label: (ctx) => ctx.dataset.label + ': ' + ctx.parsed.y + ' ' + cfg.yLabel,
                        },
                    },
                },
                scales: {
                    x: {
                        type: 'linear',
                        title: { display: true, text: 'Âge gestationnel (SA)', font: { weight: 'bold', size: 11 }, color: '#64748b' },
                        min: 10,
                        max: 42,
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { stepSize: 2, font: { size: 10 } },
                    },
                    y: {
                        title: { display: true, text: cfg.yLabel, font: { weight: 'bold', size: 11 }, color: '#64748b' },
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { font: { size: 10 } },
                    },
                },
            },
        });
    }

    document.querySelectorAll('.biom-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.biom-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            renderChart(tab.dataset.biomTab);
        });
    });

    if (typeof Chart !== 'undefined') {
        loadBiometryData();
    } else {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        s.onload = () => loadBiometryData();
        document.head.appendChild(s);
    }
})();
</script>
@endif
