@props(['labChartData' => [], 'selectedPatientId' => 0])

<section id="biology-dashboard" class="card mb-3" data-care-tab-panel="internal-medicine">
    <div class="section-head">
        <h3 class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-primary"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            {{ __('internal_med.biology_dashboard') }}
        </h3>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#labResultModal">
                {{ __('internal_med.add_lab_result') }}
            </button>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @php
            $keyLabels = [
                'hba1c' => ['label' => 'HbA1c', 'unit' => '%', 'ref' => 7, 'refMin' => 4, 'refMax' => 6],
                'creatinine' => ['label' => 'Créatinine', 'unit' => 'mg/dL', 'refMax' => 1.2],
                'cholesterol_ldl' => ['label' => 'LDL', 'unit' => 'g/L', 'refMax' => 1.6],
                'cholesterol_hdl' => ['label' => 'HDL', 'unit' => 'g/L', 'refMin' => 0.4],
                'triglycerides' => ['label' => 'Triglycérides', 'unit' => 'g/L', 'refMax' => 1.5],
                'potassium' => ['label' => 'K⁺', 'unit' => 'mmol/L', 'refMin' => 3.5, 'refMax' => 5.0],
                'sodium' => ['label' => 'Na⁺', 'unit' => 'mmol/L', 'refMin' => 136, 'refMax' => 146],
                'tsh' => ['label' => 'TSH', 'unit' => 'mUI/L', 'refMin' => 0.4, 'refMax' => 4.0],
            ];
            $availableParams = array_intersect_key($keyLabels, $labChartData);
        @endphp

        @forelse($availableParams as $key => $meta)
            @php $data = $labChartData[$key] ?? []; @endphp
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header py-2">
                        <h6 class="mb-0 fw-bold">{{ $meta['label'] }} <span class="text-secondary fw-normal">({{ $meta['unit'] }})</span></h6>
                    </div>
                    <div class="card-body p-2">
                        <canvas id="labChart_{{ $key }}" height="160"></canvas>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-secondary text-center py-4">{{ __('internal_med.no_lab_data') }}</div>
            </div>
        @endforelse
    </div>
</section>

<div class="modal fade" id="labResultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="labResultForm">
                <div class="modal-header" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border-bottom:1px solid #93c5fd">
                    <h5 class="modal-title">{{ __('internal_med.add_lab_result') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('internal_med.test_date') }} *</label>
                        <input type="date" name="test_date" class="form-control" required>
                    </div>
                    <div class="row g-2">
                        @php $labFields = ['hba1c' => 'HbA1c (%)', 'creatinine' => 'Créatinine (mg/dL)', 'urea' => 'Urée (g/L)', 'sodium' => 'Na⁺ (mmol/L)', 'potassium' => 'K⁺ (mmol/L)', 'cholesterol_total' => 'CT (g/L)', 'cholesterol_ldl' => 'LDL (g/L)', 'cholesterol_hdl' => 'HDL (g/L)', 'triglycerides' => 'TG (g/L)', 'tsh' => 'TSH (mUI/L)', 't4l' => 'T4L (ng/dL)', 'alt' => 'ALAT (UI/L)', 'ast' => 'ASAT (UI/L)', 'ggt' => 'GGT (UI/L)', 'crp' => 'CRP (mg/L)', 'ferritin' => 'Ferritine (µg/L)', 'vitamin_d' => 'Vit D (ng/mL)', 'vitamin_b12' => 'Vit B12 (pg/mL)', 'hgb' => 'Hb (g/dL)', 'wbc' => 'GB (G/L)', 'platelets' => 'Plaquettes (G/L)']; @endphp
                        @foreach($labFields as $key => $label)
                        <div class="col-md-4 col-6">
                            <div class="mb-2">
                                <label class="form-label small">{{ $label }}</label>
                                <input type="number" step="0.01" name="parameters[{{ $key }}]" class="form-control form-control-sm" placeholder="-">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('internal_med.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('internal_med.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
