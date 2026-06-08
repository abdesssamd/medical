@props(['growthRecords' => null, 'growthChartData' => null, 'selectedPatientId' => 0])

<section id="growth-tracking" class="card pedia-card" data-care-tab-panel="clinical">
    <div class="section-head">
        <h3 class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-cyan-500"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>
            {{ __('pediatrics.growth_tracking') }}
        </h3>
        <div class="pedia-toolbar">
            <button type="button" class="btn btn-sm btn-outline-cyan" data-bs-toggle="modal" data-bs-target="#growthRecordModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('pediatrics.new_measurement') }}
            </button>
        </div>
    </div>

    <div class="growth-tabs">
        <button type="button" class="growth-tab active" data-growth-tab="weight">{{ __('pediatrics.weight') }}</button>
        <button type="button" class="growth-tab" data-growth-tab="height">{{ __('pediatrics.height') }}</button>
        <button type="button" class="growth-tab" data-growth-tab="head_circumference">{{ __('pediatrics.head_circumference') }}</button>
        <button type="button" class="growth-tab" data-growth-tab="bmi">{{ __('pediatrics.bmi') }}</button>
    </div>

    <div class="growth-chart-container">
        <canvas id="growthChart" height="300"></canvas>
    </div>

    @if($growthRecords && $growthRecords->count() > 0)
        <div class="growth-history-table">
            <h4>{{ __('pediatrics.measurement_history') }}</h4>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('pediatrics.date') }}</th>
                            <th>{{ __('pediatrics.age') }}</th>
                            <th>{{ __('pediatrics.weight') }}</th>
                            <th>{{ __('pediatrics.height') }}</th>
                            <th>{{ __('pediatrics.head_circumference') }}</th>
                            <th>{{ __('pediatrics.bmi') }}</th>
                            <th>{{ __('pediatrics.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($growthRecords->reverse() as $record)
                            <tr>
                                <td>{{ $record->measurement_date->format('d/m/Y') }}</td>
                                <td>{{ $record->age_months }} {{ __('pediatrics.months') }}</td>
                                <td>{{ $record->weight_kg ? $record->weight_kg . ' kg' : '-' }}</td>
                                <td>{{ $record->height_cm ? $record->height_cm . ' cm' : '-' }}</td>
                                <td>{{ $record->head_circumference_cm ? $record->head_circumference_cm . ' cm' : '-' }}</td>
                                <td>{{ $record->bmi ?? '-' }}</td>
                                <td>
                                    @if($record->nutritional_status)
                                        <span class="growth-status growth-status-{{ $record->nutritional_status }}">
                                            {{ __('pediatrics.nutritional_' . $record->nutritional_status) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="pedia-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-cyan-300"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>
            <h4>{{ __('pediatrics.no_growth_data') }}</h4>
            <p>{{ __('pediatrics.click_to_add_measurement') }}</p>
        </div>
    @endif
</section>

<div class="modal fade" id="growthRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#ecfeff,#cffafe);border-bottom:1px solid #67e8f9">
                <h5 class="modal-title" style="font-weight:800;color:#0e7490">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>
                    {{ __('pediatrics.new_measurement') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="growthRecordForm">
                    @csrf
                    <div class="pedia-form-grid pedia-form-grid-2">
                        <div class="pedia-field">
                            <label>{{ __('pediatrics.measurement_date') }} *</label>
                            <input type="date" name="measurement_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="pedia-field">
                            <label>{{ __('pediatrics.age_months') }}</label>
                            <input type="number" name="age_months" min="0" max="240" class="form-control" placeholder="{{ __('pediatrics.auto_calculated') }}">
                            <span class="pedia-hint">{{ __('pediatrics.auto_from_birthdate') }}</span>
                        </div>
                    </div>
                    <div class="pedia-form-grid pedia-form-grid-2 mt-3">
                        <div class="pedia-field pedia-field-highlight">
                            <label>{{ __('pediatrics.weight_kg') }}</label>
                            <input type="number" name="weight_kg" step="0.01" min="0.3" max="200" class="form-control" placeholder="kg">
                        </div>
                        <div class="pedia-field pedia-field-highlight">
                            <label>{{ __('pediatrics.height_cm') }}</label>
                            <input type="number" name="height_cm" step="0.1" min="20" max="220" class="form-control" placeholder="cm">
                        </div>
                    </div>
                    <div class="pedia-form-grid pedia-form-grid-2 mt-3">
                        <div class="pedia-field">
                            <label>{{ __('pediatrics.head_circumference_cm') }}</label>
                            <input type="number" name="head_circumference_cm" step="0.1" min="20" max="70" class="form-control" placeholder="cm">
                        </div>
                        <div class="pedia-field">
                            <label>{{ __('pediatrics.arm_circumference_cm') }}</label>
                            <input type="number" name="arm_circumference_cm" step="0.1" min="5" max="50" class="form-control" placeholder="cm">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label>{{ __('pediatrics.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('pediatrics.optional_notes') }}"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('pediatrics.cancel') }}</button>
                <button type="button" class="btn btn-cyan" id="submitGrowthRecord">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    {{ __('pediatrics.save') }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.growth-tabs{display:flex;gap:4px;margin-bottom:16px;border-bottom:2px solid #e2e8f0;padding-bottom:4px}
.growth-tab{padding:8px 16px;border:none;background:transparent;font-size:.85rem;font-weight:700;color:#64748b;cursor:pointer;border-radius:8px 8px 0 0;transition:all .15s ease}
.growth-tab:hover{color:#0891b2;background:#ecfeff}
.growth-tab.active{color:#0e7490;background:#cffafe;border-bottom:2px solid #06b6d4;margin-bottom:-6px}
.growth-chart-container{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px;margin-bottom:16px;min-height:320px}
.growth-history-table{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px}
.growth-history-table h4{font-size:.9rem;font-weight:800;color:#0f172a;margin:0 0 12px}
.growth-history-table .table{font-size:.84rem;margin-bottom:0}
.growth-status{font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:999px}
.growth-status-normal{background:#dcfce7;color:#166534}
.growth-status-wasting,.growth-status-thinness{background:#fef3c7;color:#92400e}
.growth-status-severe_wasting,.growth-status-severe_thinness{background:#fee2e2;color:#991b1b}
.growth-status-overweight{background:#fef3c7;color:#92400e}
.growth-status-obesity{background:#fee2e2;color:#991b1b}
.pedia-empty-state{text-align:center;padding:48px 24px;color:#64748b}
.pedia-empty-state h4{color:#0f172a;margin:12px 0 6px}
.pedia-empty-state p{font-size:.9rem;max-width:400px;margin:0 auto}
</style>

<script>
(() => {
    const growthChartData = @json($growthChartData ?? []);
    let chart = null;

    function renderChart(type) {
        const ctx = document.getElementById('growthChart');
        if (!ctx) return;

        if (chart) {
            chart.destroy();
        }

        const data = growthChartData[type] || [];
        const sex = growthChartData.sex || 'male';

        const labels = data.map(d => d.age_months + ' mois');

        const datasets = [];

        const config = {
            weight: { label: 'Poids (kg)', yLabel: 'kg', mainColor: '#06b6d4', mainKey: 'weight' },
            height: { label: 'Taille (cm)', yLabel: 'cm', mainColor: '#8b5cf6', mainKey: 'height' },
            head_circumference: { label: 'Périmètre crânien (cm)', yLabel: 'cm', mainColor: '#10b981', mainKey: 'head_circumference' },
            bmi: { label: 'IMC', yLabel: 'kg/m²', mainColor: '#f59e0b', mainKey: 'bmi' },
        };

        const cfg = config[type] || config.weight;

        datasets.push({
            label: 'P97',
            data: data.map(d => ({ x: d.age_months, y: d.p97 })),
            borderColor: 'rgba(239, 68, 68, 0.3)',
            backgroundColor: 'rgba(239, 68, 68, 0.05)',
            borderWidth: 1,
            borderDash: [5, 5],
            pointRadius: 0,
            fill: false,
        });

        datasets.push({
            label: 'P85',
            data: data.map(d => ({ x: d.age_months, y: d.p85 })),
            borderColor: 'rgba(251, 146, 60, 0.3)',
            backgroundColor: 'rgba(251, 146, 60, 0.05)',
            borderWidth: 1,
            borderDash: [5, 5],
            pointRadius: 0,
            fill: '-1',
        });

        datasets.push({
            label: 'P50',
            data: data.map(d => ({ x: d.age_months, y: d.p50 })),
            borderColor: 'rgba(34, 197, 94, 0.5)',
            backgroundColor: 'rgba(34, 197, 94, 0.05)',
            borderWidth: 2,
            pointRadius: 0,
            fill: false,
        });

        datasets.push({
            label: 'P15',
            data: data.map(d => ({ x: d.age_months, y: d.p15 })),
            borderColor: 'rgba(251, 146, 60, 0.3)',
            backgroundColor: 'rgba(251, 146, 60, 0.05)',
            borderWidth: 1,
            borderDash: [5, 5],
            pointRadius: 0,
            fill: '-1',
        });

        datasets.push({
            label: 'P3',
            data: data.map(d => ({ x: d.age_months, y: d.p3 })),
            borderColor: 'rgba(239, 68, 68, 0.3)',
            backgroundColor: 'rgba(239, 68, 68, 0.05)',
            borderWidth: 1,
            borderDash: [5, 5],
            pointRadius: 0,
            fill: false,
        });

        datasets.push({
            label: 'Patient',
            data: data.map(d => ({ x: d.age_months, y: d[cfg.mainKey] })),
            borderColor: cfg.mainColor,
            backgroundColor: cfg.mainColor,
            borderWidth: 3,
            pointRadius: 6,
            pointHoverRadius: 8,
            fill: false,
            tension: 0.3,
        });

        chart = new Chart(ctx, {
            type: 'line',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    title: {
                        display: true,
                        text: cfg.label + ' - ' + (sex === 'male' ? 'Garçon' : 'Fille'),
                        font: { size: 14, weight: 'bold' },
                        color: '#0f172a',
                    },
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, padding: 15, font: { size: 11 } },
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' ' + cfg.yLabel;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: { display: true, text: 'Âge (mois)', font: { weight: 'bold' } },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                    },
                    y: {
                        title: { display: true, text: cfg.yLabel, font: { weight: 'bold' } },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                    }
                }
            }
        });
    }

    document.querySelectorAll('.growth-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.growth-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            renderChart(tab.dataset.growthTab);
        });
    });

    if (typeof Chart !== 'undefined') {
        renderChart('weight');
    } else {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        script.onload = () => renderChart('weight');
        document.head.appendChild(script);
    }
})();
</script>
