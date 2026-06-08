@extends('layouts.app')

@section('title', 'Statistiques Questionnaires')

@section('content')
<div class="container-fluid py-4" id="questionnaire-stats-dashboard">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Statistiques des Questionnaires
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="questionnaire-select" class="form-label fw-bold">
                                <i class="fas fa-list me-1"></i>
                                Questionnaire
                            </label>
                            <select id="questionnaire-select" class="form-select">
                                <option value="">-- Sélectionner un questionnaire --</option>
                                @foreach($questionnaires as $q)
                                    <option value="{{ $q->id }}" data-responses="{{ $q->responses_count }}">
                                        {{ $q->name }} ({{ $q->responses_count }} réponses)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="question-select" class="form-label fw-bold">
                                <i class="fas fa-question-circle me-1"></i>
                                Question
                            </label>
                            <select id="question-select" class="form-select" disabled>
                                <option value="">-- Sélectionner d'abord un questionnaire --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-filter me-1"></i>
                                Période
                            </label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-secondary period-btn" data-period="all">Tout</button>
                                <button type="button" class="btn btn-outline-secondary period-btn" data-period="7">7j</button>
                                <button type="button" class="btn btn-outline-secondary period-btn active" data-period="30">30j</button>
                                <button type="button" class="btn btn-outline-secondary period-btn" data-period="90">90j</button>
                                <button type="button" class="btn btn-outline-secondary period-btn" data-period="custom">
                                    <i class="fas fa-calendar"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-2" id="custom-date-range" style="display: none;">
                        <div class="col-md-6">
                            <label for="start-date" class="form-label">Date de début</label>
                            <input type="date" id="start-date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="end-date" class="form-label">Date de fin</label>
                            <input type="date" id="end-date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <button id="analyze-btn" class="btn btn-primary" disabled>
                                <i class="fas fa-chart-pie me-1"></i>
                                Analyser
                            </button>
                            <button id="reset-btn" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-redo me-1"></i>
                                Réinitialiser
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="stats-results" style="display: none;">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Résumé
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="stat-box">
                                    <div class="stat-label">Total Réponses</div>
                                    <div class="stat-value" id="stat-total">-</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-box">
                                    <div class="stat-label">Réponses Valides</div>
                                    <div class="stat-value text-success" id="stat-valid">-</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-box">
                                    <div class="stat-label">Valeurs Manquantes</div>
                                    <div class="stat-value text-warning" id="stat-null">-</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-box">
                                    <div class="stat-label">Taux de Complétion</div>
                                    <div class="stat-value text-info" id="stat-completion">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" id="chart-container">
            <div class="col-md-6" id="pie-chart-wrapper" style="display: none;">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Répartition des Réponses
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="pie-chart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6" id="bar-chart-wrapper" style="display: none;">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Distribution
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="bar-chart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4" id="numeric-stats-wrapper" style="display: none;">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-calculator me-2"></i>
                            Statistiques Descriptives
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2">
                                <div class="stat-box">
                                    <div class="stat-label">Moyenne</div>
                                    <div class="stat-value" id="stat-mean">-</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-box">
                                    <div class="stat-label">Médiane</div>
                                    <div class="stat-value" id="stat-median">-</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-box">
                                    <div class="stat-label">Min</div>
                                    <div class="stat-value" id="stat-min">-</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-box">
                                    <div class="stat-label">Max</div>
                                    <div class="stat-value" id="stat-max">-</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-box">
                                    <div class="stat-label">Écart-type</div>
                                    <div class="stat-value" id="stat-stddev">-</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-box">
                                    <div class="stat-label">Somme</div>
                                    <div class="stat-value" id="stat-sum">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="loading-spinner" style="display: none;">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-3">Analyse en cours...</p>
        </div>
    </div>

    <div id="error-message" class="alert alert-danger" style="display: none;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <span id="error-text"></span>
    </div>
</div>

@push('styles')
<style>
.stat-box {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 1rem;
}
.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}
.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #212529;
}
.period-btn.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const questionnaireSelect = document.getElementById('questionnaire-select');
    const questionSelect = document.getElementById('question-select');
    const analyzeBtn = document.getElementById('analyze-btn');
    const resetBtn = document.getElementById('reset-btn');
    const periodBtns = document.querySelectorAll('.period-btn');
    const customDateRange = document.getElementById('custom-date-range');
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const statsResults = document.getElementById('stats-results');
    const loadingSpinner = document.getElementById('loading-spinner');
    const errorMessage = document.getElementById('error-message');

    let currentPeriod = '30';
    let pieChart = null;
    let barChart = null;

    questionnaireSelect.addEventListener('change', async function() {
        const questionnaireId = this.value;
        questionSelect.innerHTML = '<option value="">Chargement...</option>';
        questionSelect.disabled = true;
        analyzeBtn.disabled = true;

        if (!questionnaireId) {
            questionSelect.innerHTML = '<option value="">-- Sélectionner d\'abord un questionnaire --</option>';
            return;
        }

        try {
            const response = await fetch(`/questionnaires/${questionnaireId}/stats/questions`);
            const data = await response.json();

            if (data.success) {
                questionSelect.innerHTML = '<option value="">-- Sélectionner une question --</option>';
                data.questions.forEach(q => {
                    const option = document.createElement('option');
                    option.value = q.key;
                    option.textContent = `${q.label} (${q.type})`;
                    option.dataset.type = q.type;
                    questionSelect.appendChild(option);
                });
                questionSelect.disabled = false;
            } else {
                showError(data.message || 'Erreur lors du chargement des questions');
            }
        } catch (error) {
            showError('Erreur réseau: ' + error.message);
        }
    });

    questionSelect.addEventListener('change', function() {
        analyzeBtn.disabled = !this.value;
    });

    periodBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            periodBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentPeriod = this.dataset.period;

            if (currentPeriod === 'custom') {
                customDateRange.style.display = 'flex';
            } else {
                customDateRange.style.display = 'none';
            }
        });
    });

    analyzeBtn.addEventListener('click', async function() {
        const questionnaireId = questionnaireSelect.value;
        const questionKey = questionSelect.value;

        if (!questionnaireId || !questionKey) {
            showError('Veuillez sélectionner un questionnaire et une question');
            return;
        }

        let startDate = null;
        let endDate = null;

        if (currentPeriod === 'custom') {
            startDate = startDateInput.value;
            endDate = endDateInput.value;
        } else if (currentPeriod !== 'all') {
            const days = parseInt(currentPeriod);
            const end = new Date();
            const start = new Date();
            start.setDate(end.getDate() - days);
            startDate = start.toISOString().split('T')[0];
            endDate = end.toISOString().split('T')[0];
        }

        loadingSpinner.style.display = 'block';
        statsResults.style.display = 'none';
        errorMessage.style.display = 'none';

        try {
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await fetch(`/questionnaires/${questionnaireId}/stats/analyze/${questionKey}?${params}`);
            const data = await response.json();

            if (data.success) {
                displayStats(data.stats);
            } else {
                showError(data.message || 'Erreur lors de l\'analyse');
            }
        } catch (error) {
            showError('Erreur réseau: ' + error.message);
        } finally {
            loadingSpinner.style.display = 'none';
        }
    });

    resetBtn.addEventListener('click', function() {
        questionnaireSelect.value = '';
        questionSelect.innerHTML = '<option value="">-- Sélectionner d\'abord un questionnaire --</option>';
        questionSelect.disabled = true;
        analyzeBtn.disabled = true;
        statsResults.style.display = 'none';
        errorMessage.style.display = 'none';
        currentPeriod = '30';
        periodBtns.forEach(b => b.classList.remove('active'));
        document.querySelector('[data-period="30"]').classList.add('active');
        customDateRange.style.display = 'none';
        startDateInput.value = '';
        endDateInput.value = new Date().toISOString().split('T')[0];
    });

    function displayStats(stats) {
        document.getElementById('stat-total').textContent = stats.total_responses;
        document.getElementById('stat-valid').textContent = stats.valid_responses;
        document.getElementById('stat-null').textContent = stats.null_responses;

        const completionRate = stats.total_responses > 0
            ? ((stats.valid_responses / stats.total_responses) * 100).toFixed(1) + '%'
            : '0%';
        document.getElementById('stat-completion').textContent = completionRate;

        if (pieChart) {
            pieChart.destroy();
            pieChart = null;
        }
        if (barChart) {
            barChart.destroy();
            barChart = null;
        }

        document.getElementById('pie-chart-wrapper').style.display = 'none';
        document.getElementById('bar-chart-wrapper').style.display = 'none';
        document.getElementById('numeric-stats-wrapper').style.display = 'none';

        if (stats.type === 'choice' || stats.type === 'text' || stats.type === 'date') {
            displayPieChart(stats);
        }

        if (stats.type === 'numeric') {
            displayBarChart(stats);
            displayNumericStats(stats);
        }

        statsResults.style.display = 'block';
    }

    function displayPieChart(stats) {
        const ctx = document.getElementById('pie-chart').getContext('2d');
        const chartData = stats.chart_data;

        pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.values,
                    backgroundColor: generateColors(chartData.labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        document.getElementById('pie-chart-wrapper').style.display = 'block';
    }

    function displayBarChart(stats) {
        const ctx = document.getElementById('bar-chart').getContext('2d');
        const chartData = stats.chart_data;

        barChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Nombre de réponses',
                    data: chartData.values,
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        document.getElementById('bar-chart-wrapper').style.display = 'block';
    }

    function displayNumericStats(stats) {
        if (!stats.statistics) return;

        document.getElementById('stat-mean').textContent = stats.statistics.mean;
        document.getElementById('stat-median').textContent = stats.statistics.median;
        document.getElementById('stat-min').textContent = stats.statistics.min;
        document.getElementById('stat-max').textContent = stats.statistics.max;
        document.getElementById('stat-stddev').textContent = stats.statistics.std_dev;
        document.getElementById('stat-sum').textContent = stats.statistics.sum;

        document.getElementById('numeric-stats-wrapper').style.display = 'block';
    }

    function generateColors(count) {
        const colors = [
            '#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545',
            '#fd7e14', '#ffc107', '#198754', '#20c997', '#0dcaf0'
        ];
        const result = [];
        for (let i = 0; i < count; i++) {
            result.push(colors[i % colors.length]);
        }
        return result;
    }

    function showError(message) {
        document.getElementById('error-text').textContent = message;
        errorMessage.style.display = 'block';
        statsResults.style.display = 'none';
    }
});
</script>
@endpush
@endsection
