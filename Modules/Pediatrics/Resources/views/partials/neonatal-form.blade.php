@props(['birthHistory' => null, 'selectedPatientId' => 0])

<section id="neonatal-history" class="card pedia-card" data-care-tab-panel="clinical">
    <div class="section-head">
        <h3 class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-cyan-500"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/><circle cx="12" cy="10" r="3"/><path d="M12 13v4"/></svg>
            {{ __('pediatrics.neonatal_history') }}
        </h3>
        <div class="pedia-toolbar">
            <button type="button" class="btn btn-sm btn-outline-cyan" id="pediaRefreshBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                {{ __('pediatrics.refresh') }}
            </button>
        </div>
    </div>

    @if($birthHistory)
        <div class="neonatal-summary">
            <div class="neonatal-card neonatal-card-primary">
                <div class="neonatal-label">{{ __('pediatrics.delivery_type') }}</div>
                <div class="neonatal-value">
                    @switch($birthHistory->delivery_type)
                        @case('spontaneous') {{ __('pediatrics.spontaneous') }} @break
                        @case('assisted') {{ __('pediatrics.assisted') }} @break
                        @case('cesarean') {{ __('pediatrics.cesarean') }} @break
                        @case('breech') {{ __('pediatrics.breech') }} @break
                        @default -
                    @endswitch
                </div>
            </div>

            <div class="neonatal-card neonatal-card-apgar">
                <div class="neonatal-label">{{ __('pediatrics.apgar_score') }}</div>
                <div class="neonatal-value">{{ $birthHistory->apgar_score }}</div>
                <div class="neonatal-sub">1min / 5min / 10min</div>
            </div>

            <div class="neonatal-card neonatal-card-measurements">
                <div class="neonatal-label">{{ __('pediatrics.birth_measurements') }}</div>
                <div class="neonatal-value">{{ $birthHistory->birth_measurements }}</div>
            </div>

            <div class="neonatal-card neonatal-card-status">
                <div class="neonatal-label">{{ __('pediatrics.neonatal_status') }}</div>
                <div class="neonatal-badges">
                    @if($birthHistory->neonatal_resuscitation)
                        <span class="neonatal-badge neonatal-badge-warning">{{ __('pediatrics.resuscitation') }}</span>
                    @endif
                    @if($birthHistory->nicu_admission)
                        <span class="neonatal-badge neonatal-badge-danger">NICU ({{ $birthHistory->nicu_days ?? '?' }}j)</span>
                    @endif
                    @if($birthHistory->jaundice)
                        <span class="neonatal-badge neonatal-badge-info">{{ __('pediatrics.jaundice') }}</span>
                    @endif
                    @if($birthHistory->breastfeeding)
                        <span class="neonatal-badge neonatal-badge-success">{{ __('pediatrics.breastfeeding') }}</span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <form id="neonatalHistoryForm" class="pedia-form">
        @csrf
        <input type="hidden" name="id" value="{{ $birthHistory?->id }}">

        <div class="pedia-form-section">
            <h4>{{ __('pediatrics.delivery_information') }}</h4>
            <div class="pedia-form-grid pedia-form-grid-4">
                <div class="pedia-field">
                    <label>{{ __('pediatrics.delivery_type') }}</label>
                    <select name="delivery_type" class="form-select">
                        <option value="">-</option>
                        <option value="spontaneous" {{ ($birthHistory?->delivery_type) === 'spontaneous' ? 'selected' : '' }}>{{ __('pediatrics.spontaneous') }}</option>
                        <option value="assisted" {{ ($birthHistory?->delivery_type) === 'assisted' ? 'selected' : '' }}>{{ __('pediatrics.assisted') }}</option>
                        <option value="cesarean" {{ ($birthHistory?->delivery_type) === 'cesarean' ? 'selected' : '' }}>{{ __('pediatrics.cesarean') }}</option>
                        <option value="breech" {{ ($birthHistory?->delivery_type) === 'breech' ? 'selected' : '' }}>{{ __('pediatrics.breech') }}</option>
                    </select>
                </div>
                <div class="pedia-field">
                    <label>{{ __('pediatrics.delivery_place') }}</label>
                    <input type="text" name="delivery_place" value="{{ old('delivery_place', $birthHistory?->delivery_place) }}" class="form-control" placeholder="{{ __('pediatrics.hospital_clinic') }}">
                </div>
                <div class="pedia-field">
                    <label>{{ __('pediatrics.gestational_age_weeks') }}</label>
                    <input type="number" name="gestational_age_weeks" min="22" max="45" value="{{ old('gestational_age_weeks', $birthHistory?->gestational_age_weeks) }}" class="form-control" placeholder="SA">
                </div>
                <div class="pedia-field">
                    <label>{{ __('pediatrics.presentation') }}</label>
                    <select name="presentation_at_birth" class="form-select">
                        <option value="">-</option>
                        <option value="cephalic" {{ ($birthHistory?->presentation_at_birth) === 'cephalic' ? 'selected' : '' }}>{{ __('pediatrics.cephalic') }}</option>
                        <option value="breech" {{ ($birthHistory?->presentation_at_birth) === 'breech' ? 'selected' : '' }}>{{ __('pediatrics.breech') }}</option>
                        <option value="transverse" {{ ($birthHistory?->presentation_at_birth) === 'transverse' ? 'selected' : '' }}>{{ __('pediatrics.transverse') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="pedia-form-section">
            <h4>{{ __('pediatrics.apgar_scores') }}</h4>
            <div class="pedia-form-grid pedia-form-grid-3">
                <div class="pedia-field">
                    <label>{{ __('pediatrics.apgar_1min') }}</label>
                    <input type="number" name="apgar_1min" min="0" max="10" value="{{ old('apgar_1min', $birthHistory?->apgar_1min) }}" class="form-control apgar-input">
                </div>
                <div class="pedia-field">
                    <label>{{ __('pediatrics.apgar_5min') }}</label>
                    <input type="number" name="apgar_5min" min="0" max="10" value="{{ old('apgar_5min', $birthHistory?->apgar_5min) }}" class="form-control apgar-input">
                </div>
                <div class="pedia-field">
                    <label>{{ __('pediatrics.apgar_10min') }}</label>
                    <input type="number" name="apgar_10min" min="0" max="10" value="{{ old('apgar_10min', $birthHistory?->apgar_10min) }}" class="form-control apgar-input">
                </div>
            </div>
        </div>

        <div class="pedia-form-section">
            <h4>{{ __('pediatrics.birth_measurements') }}</h4>
            <div class="pedia-form-grid pedia-form-grid-3">
                <div class="pedia-field pedia-field-highlight">
                    <label>{{ __('pediatrics.birth_weight') }}</label>
                    <input type="number" name="birth_weight_grams" min="300" max="7000" value="{{ old('birth_weight_grams', $birthHistory?->birth_weight_grams) }}" class="form-control" placeholder="grammes">
                    <span class="pedia-hint">g</span>
                </div>
                <div class="pedia-field pedia-field-highlight">
                    <label>{{ __('pediatrics.birth_length') }}</label>
                    <input type="number" name="birth_length_cm" step="0.1" min="20" max="70" value="{{ old('birth_length_cm', $birthHistory?->birth_length_cm) }}" class="form-control" placeholder="cm">
                    <span class="pedia-hint">cm</span>
                </div>
                <div class="pedia-field pedia-field-highlight">
                    <label>{{ __('pediatrics.head_circumference') }}</label>
                    <input type="number" name="birth_head_circumference_cm" step="0.1" min="20" max="50" value="{{ old('birth_head_circumference_cm', $birthHistory?->birth_head_circumference_cm) }}" class="form-control" placeholder="cm">
                    <span class="pedia-hint">cm</span>
                </div>
            </div>
        </div>

        <div class="pedia-form-section">
            <h4>{{ __('pediatrics.neonatal_conditions') }}</h4>
            <div class="pedia-form-grid pedia-form-grid-2">
                <div class="pedia-field">
                    <label class="pedia-checkbox-label">
                        <input type="checkbox" name="neonatal_resuscitation" value="1" {{ $birthHistory?->neonatal_resuscitation ? 'checked' : '' }}>
                        {{ __('pediatrics.neonatal_resuscitation') }}
                    </label>
                </div>
                <div class="pedia-field">
                    <label class="pedia-checkbox-label">
                        <input type="checkbox" name="nicu_admission" value="1" {{ $birthHistory?->nicu_admission ? 'checked' : '' }}>
                        {{ __('pediatrics.nicu_admission') }}
                    </label>
                </div>
                <div class="pedia-field">
                    <label class="pedia-checkbox-label">
                        <input type="checkbox" name="jaundice" value="1" {{ $birthHistory?->jaundice ? 'checked' : '' }}>
                        {{ __('pediatrics.jaundice') }}
                    </label>
                </div>
                <div class="pedia-field">
                    <label class="pedia-checkbox-label">
                        <input type="checkbox" name="breastfeeding" value="1" {{ $birthHistory?->breastfeeding ? 'checked' : '' }}>
                        {{ __('pediatrics.breastfeeding') }}
                    </label>
                </div>
            </div>
            <div class="pedia-form-grid pedia-form-grid-3 mt-2">
                <div class="pedia-field">
                    <label>{{ __('pediatrics.nicu_days') }}</label>
                    <input type="number" name="nicu_days" min="0" max="365" value="{{ old('nicu_days', $birthHistory?->nicu_days) }}" class="form-control" placeholder="jours">
                </div>
                <div class="pedia-field">
                    <label>{{ __('pediatrics.jaundice_type') }}</label>
                    <input type="text" name="jaundice_type" value="{{ old('jaundice_type', $birthHistory?->jaundice_type) }}" class="form-control" placeholder="{{ __('pediatrics.physiological_pathological') }}">
                </div>
                <div class="pedia-field">
                    <label>{{ __('pediatrics.jaundice_treatment') }}</label>
                    <input type="text" name="jaundice_treatment" value="{{ old('jaundice_treatment', $birthHistory?->jaundice_treatment) }}" class="form-control" placeholder="{{ __('pediatrics.phototherapy_exchange') }}">
                </div>
            </div>
        </div>

        <div class="pedia-form-section">
            <h4>{{ __('pediatrics.newborn_care') }}</h4>
            <div class="pedia-form-grid pedia-form-grid-3">
                <div class="pedia-field">
                    <label>{{ __('pediatrics.vitamin_k') }}</label>
                    <select name="vitamin_k_given" class="form-select">
                        <option value="">-</option>
                        <option value="yes" {{ ($birthHistory?->vitamin_k_given) === 'yes' ? 'selected' : '' }}>{{ __('pediatrics.yes') }}</option>
                        <option value="no" {{ ($birthHistory?->vitamin_k_given) === 'no' ? 'selected' : '' }}>{{ __('pediatrics.no') }}</option>
                    </select>
                </div>
                <div class="pedia-field">
                    <label class="pedia-checkbox-label">
                        <input type="checkbox" name="hepatitis_b_birth_dose" value="1" {{ $birthHistory?->hepatitis_b_birth_dose ? 'checked' : '' }}>
                        {{ __('pediatrics.hepatitis_b_birth_dose') }}
                    </label>
                </div>
                <div class="pedia-field">
                    <label class="pedia-checkbox-label">
                        <input type="checkbox" name="newborn_screening_done" value="1" {{ $birthHistory?->newborn_screening_done ? 'checked' : '' }}>
                        {{ __('pediatrics.newborn_screening') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="pedia-form-section">
            <h4>{{ __('pediatrics.notes') }}</h4>
            <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('pediatrics.additional_notes') }}">{{ old('notes', $birthHistory?->notes) }}</textarea>
        </div>

        <div class="pedia-form-actions">
            <button type="submit" class="btn btn-cyan">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                {{ __('pediatrics.save_neonatal_history') }}
            </button>
        </div>
    </form>
</section>

<style>
.pedia-card{padding:14px;background:linear-gradient(180deg,#ecfeff 0%,#cffafe 100%);border:1px solid #67e8f9}
.pedia-toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.btn-outline-cyan{color:#0891b2;border-color:#67e8f9}
.btn-outline-cyan:hover{background:#cffafe;border-color:#0891b2;color:#0e7490}
.btn-cyan{background:#06b6d4;color:#fff;border:none;padding:8px 20px;border-radius:10px;font-weight:700;font-size:.88rem;cursor:pointer;display:flex;align-items:center;gap:6px}
.btn-cyan:hover{background:#0891b2;color:#fff}
.neonatal-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}
.neonatal-card{padding:14px;border-radius:14px;border:1px solid #e2e8f0;background:#fff}
.neonatal-card-primary{border-color:#67e8f9;background:linear-gradient(135deg,#ecfeff,#fff)}
.neonatal-card-apgar{border-color:#a78bfa;background:linear-gradient(135deg,#f5f3ff,#fff)}
.neonatal-card-measurements{border-color:#86efac;background:linear-gradient(135deg,#f0fdf4,#fff)}
.neonatal-card-status{border-color:#fcd34d;background:linear-gradient(135deg,#fffbeb,#fff)}
.neonatal-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#64748b;font-weight:700;margin-bottom:4px}
.neonatal-value{font-size:1.1rem;font-weight:800;color:#0f172a;line-height:1.3}
.neonatal-sub{font-size:.78rem;color:#64748b;margin-top:2px}
.neonatal-badges{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
.neonatal-badge{font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:999px}
.neonatal-badge-success{background:#dcfce7;color:#166534}
.neonatal-badge-warning{background:#fef3c7;color:#92400e}
.neonatal-badge-danger{background:#fee2e2;color:#991b1b}
.neonatal-badge-info{background:#dbeafe;color:#1e40af}
.pedia-form{display:grid;gap:16px}
.pedia-form-section{padding:14px;border:1px solid #f1f5f9;border-radius:14px;background:#fafbfc}
.pedia-form-section h4{font-size:.9rem;font-weight:800;color:#0f172a;margin:0 0 12px;padding-bottom:8px;border-bottom:1px solid #e2e8f0}
.pedia-form-grid{display:grid;gap:10px}
.pedia-form-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
.pedia-form-grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.pedia-form-grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
.pedia-field label{display:block;font-size:.78rem;font-weight:700;color:#334155;margin-bottom:4px}
.pedia-field .form-control,.pedia-field .form-select{border-radius:10px;border-color:#e2e8f0;font-size:.88rem}
.pedia-field .form-control:focus,.pedia-field .form-select:focus{border-color:#06b6d4;box-shadow:0 0 0 3px rgba(6,182,212,.12)}
.pedia-field-highlight .form-control{background:linear-gradient(135deg,#ecfeff,#fff);border-color:#67e8f9;font-weight:700;font-size:1rem}
.pedia-hint{position:absolute;inset-inline-end:12px;top:32px;font-size:.72rem;color:#64748b;font-weight:600}
.pedia-checkbox-label{display:flex;align-items:center;gap:8px;font-size:.85rem;font-weight:600;color:#334155;cursor:pointer}
.pedia-checkbox-label input[type="checkbox"]{width:18px;height:18px;accent-color:#06b6d4}
.pedia-form-actions{display:flex;justify-content:flex-end;gap:8px}
@media (max-width:1200px){.neonatal-summary{grid-template-columns:repeat(2,1fr)}.pedia-form-grid-4{grid-template-columns:repeat(2,1fr)}}
@media (max-width:768px){.neonatal-summary{grid-template-columns:1fr}.pedia-form-grid-4,.pedia-form-grid-3,.pedia-form-grid-2{grid-template-columns:1fr}}
</style>
