@props(['admission' => null, 'fluidResuscitation' => null, 'fluidStatus' => [], 'selectedPatientId' => 0])

<section id="fluid-resuscitation-dashboard" class="card burns-card" data-care-tab-panel="clinical" data-admission-id="{{ $admission?->id ?? '' }}">
    <div class="section-head">
        <h3 class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-red-500"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
            {{ __('burns.fluid_resuscitation') }}
        </h3>
        <div class="burns-toolbar">
            <button type="button" class="btn btn-sm btn-outline-red" data-bs-toggle="modal" data-bs-target="#parklandCalcModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/></svg>
                {{ __('burns.calculate_parkland') }}
            </button>
        </div>
    </div>

    @if($fluidResuscitation)
        <div class="parkland-alerts">
            @foreach(($fluidStatus['alerts'] ?? []) as $alert)
                <div class="parkland-alert parkland-alert-{{ $alert['type'] }}">
                    <strong>{{ $alert['message'] }}</strong>
                </div>
            @endforeach
        </div>

        <div class="parkland-grid">
            <div class="parkland-card parkland-card-total">
                <div class="parkland-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                </div>
                <div class="parkland-card-body">
                    <div class="parkland-label">{{ __('burns.total_volume') }}</div>
                    <div class="parkland-value">{{ number_format($fluidResuscitation->total_volume_ml, 0, ',', ' ') }} ml</div>
                    <div class="parkland-sub">{{ $fluidResuscitation->fluid_type_label }}</div>
                </div>
            </div>

            <div class="parkland-card parkland-card-phase1 {{ ($fluidStatus['phase'] ?? '') === 'first_8h' ? 'parkland-card-active' : '' }}">
                <div class="parkland-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="parkland-card-body">
                    <div class="parkland-label">{{ __('burns.phase_1') }}</div>
                    <div class="parkland-value">{{ number_format($fluidResuscitation->first_8h_volume_ml, 0, ',', ' ') }} ml</div>
                    <div class="parkland-sub">{{ number_format($fluidResuscitation->first_8h_rate_ml_per_hour, 0, ',', ' ') }} ml/h</div>
                    <div class="parkland-time">{{ $fluidResuscitation->resuscitation_start_time->format('H:i') }} → {{ $fluidResuscitation->first_8h_end_time->format('H:i') }}</div>
                </div>
                @if(($fluidStatus['phase'] ?? '') === 'first_8h')
                    <div class="parkland-active-badge">{{ __('burns.active') }}</div>
                @endif
            </div>

            <div class="parkland-card parkland-card-phase2 {{ ($fluidStatus['phase'] ?? '') === 'next_16h' ? 'parkland-card-active' : '' }}">
                <div class="parkland-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="parkland-card-body">
                    <div class="parkland-label">{{ __('burns.phase_2') }}</div>
                    <div class="parkland-value">{{ number_format($fluidResuscitation->next_16h_volume_ml, 0, ',', ' ') }} ml</div>
                    <div class="parkland-sub">{{ number_format($fluidResuscitation->next_16h_rate_ml_per_hour, 0, ',', ' ') }} ml/h</div>
                    <div class="parkland-time">{{ $fluidResuscitation->first_8h_end_time->format('H:i') }} → {{ $fluidResuscitation->next_16h_end_time->format('H:i') }}</div>
                </div>
                @if(($fluidStatus['phase'] ?? '') === 'next_16h')
                    <div class="parkland-active-badge">{{ __('burns.active') }}</div>
                @endif
            </div>

            <div class="parkland-card parkland-card-urine">
                <div class="parkland-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div class="parkland-card-body">
                    <div class="parkland-label">{{ __('burns.urine_output_target') }}</div>
                    <div class="parkland-value">{{ number_format($fluidResuscitation->urine_output_target_ml_per_hour, 0, ',', ' ') }} ml/h</div>
                    <div class="parkland-sub">{{ __('burns.monitor_hourly') }}</div>
                </div>
            </div>
        </div>

        @if(($fluidStatus['status'] ?? '') === 'active')
            <div class="parkland-timer">
                <div class="parkland-timer-phase">{{ $fluidStatus['phase_label'] ?? '-' }}</div>
                <div class="parkland-timer-countdown" id="parklandCountdown">
                    {{ $fluidStatus['time_remaining_display'] ?? '-' }}
                </div>
                <div class="parkland-timer-rate">
                    {{ __('burns.current_rate') }}: <strong>{{ number_format($fluidStatus['current_rate_ml_per_hour'] ?? 0, 0, ',', ' ') }} ml/h</strong>
                </div>
            </div>
        @endif

        <div class="parkland-formula-info">
            <h4>{{ __('burns.parkland_formula') }}</h4>
            <div class="formula-display">
                <code>Volume total = 4 × {{ $fluidResuscitation->patient_weight_kg }} kg × {{ $fluidResuscitation->burn_surface_area_percent }}% = {{ number_format($fluidResuscitation->total_volume_ml, 0, ',', ' ') }} ml</code>
            </div>
            <div class="formula-protocol">
                <div><strong>50%</strong> {{ __('burns.in_first_8h') }} ({{ number_format($fluidResuscitation->first_8h_volume_ml, 0, ',', ' ') }} ml)</div>
                <div><strong>50%</strong> {{ __('burns.in_next_16h') }} ({{ number_format($fluidResuscitation->next_16h_volume_ml, 0, ',', ' ') }} ml)</div>
            </div>
        </div>
    @else
        <div class="burns-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-red-300"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
            <h4>{{ __('burns.no_resuscitation_data') }}</h4>
            <p>{{ __('burns.click_to_calculate_parkland') }}</p>
        </div>
    @endif
</section>

<div class="modal fade" id="parklandCalcModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#fef2f2,#fee2e2);border-bottom:1px solid #fca5a5">
                <h5 class="modal-title" style="font-weight:800;color:#991b1b">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                    {{ __('burns.calculate_parkland') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="parklandCalcForm">
                    <div class="burns-form-grid burns-form-grid-2">
                        <div class="burns-field">
                            <label>{{ __('burns.patient_weight') }} *</label>
                            <input type="number" name="patient_weight_kg" step="0.1" min="1" max="300" class="form-control" required value="{{ $admission?->admission_weight_kg ?? '' }}" placeholder="kg">
                        </div>
                        <div class="burns-field">
                            <label>{{ __('burns.burn_surface_area') }} *</label>
                            <input type="number" name="burn_surface_area_percent" step="0.5" min="1" max="100" class="form-control" required placeholder="% SCB">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label>{{ __('burns.formula') }}</label>
                        <select name="formula_used" class="form-select">
                            <option value="parkland">Parkland (4 ml/kg/%SCB)</option>
                            <option value="modified_brooke">Modified Brooke (2 ml/kg/%SCB)</option>
                            <option value="consensus">Consensus (3 ml/kg/%SCB)</option>
                        </select>
                    </div>
                    <div class="parkland-preview" id="parklandPreview" style="display:none">
                        <h5>{{ __('burns.preview') }}</h5>
                        <div class="preview-grid">
                            <div class="preview-item">
                                <span class="preview-label">{{ __('burns.total_volume') }}</span>
                                <span class="preview-value" id="previewTotal">-</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">{{ __('burns.phase_1_rate') }}</span>
                                <span class="preview-value" id="previewPhase1">-</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">{{ __('burns.phase_2_rate') }}</span>
                                <span class="preview-value" id="previewPhase2">-</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('burns.cancel') }}</button>
                <button type="button" class="btn btn-red" id="submitParklandCalc">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    {{ __('burns.calculate_and_start') }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.parkland-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}
.parkland-card{padding:16px;border-radius:14px;border:1px solid #e2e8f0;background:#fff;display:grid;gap:6px;position:relative}
.parkland-card-icon{color:#64748b;margin-bottom:4px}
.parkland-card-total{border-color:#fca5a5;background:linear-gradient(135deg,#fef2f2,#fff)}
.parkland-card-total .parkland-card-icon{color:#dc2626}
.parkland-card-phase1{border-color:#fbbf24;background:linear-gradient(135deg,#fffbeb,#fff)}
.parkland-card-phase1 .parkland-card-icon{color:#d97706}
.parkland-card-phase2{border-color:#60a5fa;background:linear-gradient(135deg,#eff6ff,#fff)}
.parkland-card-phase2 .parkland-card-icon{color:#2563eb}
.parkland-card-urine{border-color:#a78bfa;background:linear-gradient(135deg,#f5f3ff,#fff)}
.parkland-card-urine .parkland-card-icon{color:#7c3aed}
.parkland-card-active{border-width:3px;box-shadow:0 4px 12px rgba(0,0,0,.08)}
.parkland-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#64748b;font-weight:700}
.parkland-value{font-size:1.5rem;font-weight:900;color:#0f172a;line-height:1.2}
.parkland-sub{font-size:.82rem;color:#64748b;font-weight:600}
.parkland-time{font-size:.72rem;color:#94a3b8;margin-top:4px}
.parkland-active-badge{position:absolute;top:8px;inset-inline-end:8px;font-size:.65rem;font-weight:800;color:#fff;background:#dc2626;padding:3px 8px;border-radius:999px;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.7}}
.parkland-timer{background:linear-gradient(135deg,#fef2f2,#fee2e2);border:2px solid #dc2626;border-radius:14px;padding:20px;text-align:center;margin-bottom:16px}
.parkland-timer-phase{font-size:.88rem;font-weight:700;color:#991b1b;margin-bottom:8px}
.parkland-timer-countdown{font-size:2.5rem;font-weight:900;color:#dc2626;line-height:1;margin-bottom:8px}
.parkland-timer-rate{font-size:.92rem;color:#334155}
.parkland-formula-info{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px}
.parkland-formula-info h4{font-size:.9rem;font-weight:800;color:#0f172a;margin:0 0 12px}
.formula-display{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;margin-bottom:12px}
.formula-display code{font-size:.88rem;font-weight:700;color:#334155;font-family:'Courier New',monospace}
.formula-protocol{display:grid;gap:6px;font-size:.85rem;color:#64748b}
.parkland-alerts{display:grid;gap:8px;margin-bottom:14px}
.parkland-alert{padding:10px 14px;border-radius:10px;font-size:.88rem}
.parkland-alert-critical{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b}
.parkland-alert-warning{background:#fffbeb;border:1px solid #fcd34d;color:#92400e}
.parkland-alert-info{background:#eff6ff;border:1px solid #93c5fd;color:#1e40af}
.burns-form-grid{display:grid;gap:10px}
.burns-form-grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
.burns-field label{display:block;font-size:.78rem;font-weight:700;color:#334155;margin-bottom:4px}
.burns-field .form-control,.burns-field .form-select{border-radius:10px;border-color:#e2e8f0;font-size:.88rem}
.burns-field .form-control:focus,.burns-field .form-select:focus{border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.12)}
.parkland-preview{margin-top:16px;padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px}
.parkland-preview h5{font-size:.85rem;font-weight:800;color:#0f172a;margin:0 0 10px}
.preview-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.preview-item{text-align:center}
.preview-label{display:block;font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px}
.preview-value{font-size:1.1rem;font-weight:800;color:#dc2626}
.burns-empty-state{text-align:center;padding:48px 24px;color:#64748b}
.burns-empty-state h4{color:#0f172a;margin:12px 0 6px}
.burns-empty-state p{font-size:.9rem;max-width:400px;margin:0 auto}
@media (max-width:1200px){.parkland-grid{grid-template-columns:repeat(2,1fr)}.preview-grid{grid-template-columns:1fr}}
@media (max-width:768px){.parkland-grid{grid-template-columns:1fr}}
</style>
