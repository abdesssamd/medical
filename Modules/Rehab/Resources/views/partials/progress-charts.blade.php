@props(['stats' => [], 'sessions' => collect()])

@if(!empty($stats['pain_evolution']) && count($stats['pain_evolution']) > 0)
<div class="rehab-charts-section mt-3">
    <h4>{{ __('rehab.progress_charts') }}</h4>
    <div class="rehab-charts-grid">
        <div class="rehab-chart-card">
            <h5>{{ __('rehab.pain_evolution') }}</h5>
            <canvas id="rehabPainChart" height="200"></canvas>
        </div>
        <div class="rehab-chart-card">
            <h5>{{ __('rehab.session_attendance') }}</h5>
            <canvas id="rehabAttendanceChart" height="200"></canvas>
        </div>
    </div>
</div>

<script>
(() => {
    const painData = @json($stats['pain_evolution'] ?? []);
    const timelineData = @json($stats['session_timeline'] ?? []);

    if (!painData.length) return;

    const painCtx = document.getElementById('rehabPainChart');
    if (painCtx) {
        new Chart(painCtx, {
            type: 'line',
            data: {
                labels: painData.map(d => 'S' + d.session),
                datasets: [{
                    label: '{{ __('rehab.pain_score') }}',
                    data: painData.map(d => d.pain),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 5,
                    pointBackgroundColor: painData.map(d => d.pain <= 3 ? '#22c55e' : (d.pain <= 6 ? '#eab308' : '#ef4444')),
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { min: 0, max: 10, ticks: { stepSize: 2 } }
                }
            }
        });
    }

    const attendCtx = document.getElementById('rehabAttendanceChart');
    if (attendCtx && timelineData.length) {
        const statusCounts = timelineData.reduce((acc, s) => {
            acc[s.status] = (acc[s.status] || 0) + 1;
            return acc;
        }, {});

        new Chart(attendCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusCounts).map(s => ({planned:'{{ __('rehab.planned') }}',completed:'{{ __('rehab.completed') }}',cancelled:'{{ __('rehab.cancelled') }}',missed:'{{ __('rehab.missed') }}'}[s] || s)),
                datasets: [{
                    data: Object.values(statusCounts),
                    backgroundColor: ['#3b82f6', '#22c55e', '#ef4444', '#f59e0b'],
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
})();
</script>
@endif

<style>
.rehab-charts-section{background:#fff;border:1px solid #e9d5ff;border-radius:12px;padding:16px}
.rehab-charts-section h4{font-size:0.9rem;font-weight:600;color:#7c3aed;margin-bottom:12px}
.rehab-charts-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px}
.rehab-chart-card{background:#fafafa;border:1px solid #e5e7eb;border-radius:8px;padding:12px}
.rehab-chart-card h5{font-size:0.8rem;font-weight:600;color:#374151;margin-bottom:8px}
</style>
