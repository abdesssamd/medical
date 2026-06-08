@props(['scoresSummary' => [], 'clinicalScores' => null, 'selectedPatientId' => 0])

<section id="clinical-scores" class="card mb-3" data-care-tab-panel="internal-medicine">
    <div class="section-head">
        <h3 class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-info"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/></svg>
            {{ __('internal_med.clinical_scores') }}
        </h3>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0 fw-bold">{{ __('internal_med.chads2_vasc') }}</h6>
                </div>
                <div class="card-body">
                    <p class="text-secondary small mb-2">{{ __('internal_med.chads2_vasc_desc') }}</p>
                    <div class="mb-2">
                        @php $chadsFields = ['heart_failure' => __('internal_med.heart_failure'), 'hypertension' => __('internal_med.hypertension'), 'age_over_75' => __('internal_med.age_over_75'), 'diabetes' => __('internal_med.diabetes'), 'stroke' => __('internal_med.stroke_tia'), 'vascular_disease' => __('internal_med.vascular_disease'), 'age_65_to_74' => __('internal_med.age_65_74')]; @endphp
                        @foreach($chadsFields as $key => $label)
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input chads-field" id="chads_{{ $key }}" data-key="{{ $key }}">
                            <label class="form-check-label small" for="chads_{{ $key }}">{{ $label }}</label>
                        </div>
                        @endforeach
                        <div class="mt-2">
                            <label class="form-label small">{{ __('internal_med.sex') }}</label>
                            <select id="chadsSex" class="form-select form-select-sm">
                                <option value="male">{{ __('internal_med.male') }}</option>
                                <option value="female">{{ __('internal_med.female') }}</option>
                            </select>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>{{ __('internal_med.score') }}:</strong>
                        <span class="badge bg-primary fs-5" id="chadsScoreDisplay">0</span>
                    </div>
                    <div class="mt-1 small" id="chadsRiskDisplay"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="saveChadsScore">{{ __('internal_med.save_score') }}</button>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white py-2">
                    <h6 class="mb-0 fw-bold">{{ __('internal_med.kidney_function') }}</h6>
                </div>
                <div class="card-body">
                    <p class="text-secondary small mb-2">{{ __('internal_med.kidney_function_desc') }}</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">{{ __('internal_med.age') }}</label>
                            <input type="number" id="kidneyAge" class="form-control form-control-sm" min="1" max="130">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">{{ __('internal_med.weight_kg') }}</label>
                            <input type="number" id="kidneyWeight" class="form-control form-control-sm" min="1" max="300" step="0.1">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">{{ __('internal_med.creatinine') }} (mg/dL)</label>
                            <input type="number" id="kidneyCreatinine" class="form-control form-control-sm" min="0.1" max="50" step="0.01">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">{{ __('internal_med.sex') }}</label>
                            <select id="kidneySex" class="form-select form-select-sm">
                                <option value="male">{{ __('internal_med.male') }}</option>
                                <option value="female">{{ __('internal_med.female') }}</option>
                            </select>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="small text-secondary">{{ __('internal_med.cockcroft_gault') }}</div>
                            <strong id="cockcroftDisplay" class="fs-5 text-success">-</strong>
                            <div class="small text-secondary">mL/min</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-secondary">{{ __('internal_med.mdrd') }}</div>
                            <strong id="mdrdDisplay" class="fs-5 text-info">-</strong>
                            <div class="small text-secondary">mL/min/1.73m²</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success mt-2" id="saveKidneyScore">{{ __('internal_med.save_score') }}</button>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 border-warning">
                <div class="card-header bg-warning text-dark py-2">
                    <h6 class="mb-0 fw-bold">{{ __('internal_med.bmi_bsa') }}</h6>
                </div>
                <div class="card-body">
                    <p class="text-secondary small mb-2">{{ __('internal_med.bmi_desc') }}</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">{{ __('internal_med.weight_kg') }}</label>
                            <input type="number" id="bmiWeight" class="form-control form-control-sm" min="1" max="300" step="0.1">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">{{ __('internal_med.height_cm') }}</label>
                            <input type="number" id="bmiHeight" class="form-control form-control-sm" min="30" max="250" step="0.1">
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="small text-secondary">IMC</div>
                            <strong id="bmiDisplay" class="fs-5 text-warning">-</strong>
                            <div class="small text-secondary">kg/m²</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-secondary">{{ __('internal_med.body_surface_area') }}</div>
                            <strong id="bsaDisplay" class="fs-5 text-orange">-</strong>
                            <div class="small text-secondary">m²</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-warning mt-2" id="saveBmiScore">{{ __('internal_med.save_score') }}</button>
                </div>
            </div>
        </div>
    </div>
</section>
