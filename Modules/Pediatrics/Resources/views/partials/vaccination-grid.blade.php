@props(['vaccinationSchedule' => [], 'vaccinationSummary' => [], 'selectedPatientId' => 0])

<section id="vaccination-dashboard" class="card pedia-card" data-care-tab-panel="clinical">
    <div class="section-head">
        <h3 class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-cyan-500"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="m12 5 7 7"/></svg>
            {{ __('pediatrics.vaccination_schedule') }}
        </h3>
        <div class="pedia-toolbar">
            <button type="button" class="btn btn-sm btn-outline-cyan" data-bs-toggle="modal" data-bs-target="#vaccinationRecordModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('pediatrics.record_vaccination') }}
            </button>
        </div>
    </div>

    @if(!empty($vaccinationSummary))
        <div class="vaccination-summary">
            <div class="vacc-stat vacc-stat-total">
                <div class="vacc-stat-value">{{ $vaccinationSummary['total'] ?? 0 }}</div>
                <div class="vacc-stat-label">{{ __('pediatrics.total_vaccines') }}</div>
            </div>
            <div class="vacc-stat vacc-stat-done">
                <div class="vacc-stat-value">{{ $vaccinationSummary['administered'] ?? 0 }}</div>
                <div class="vacc-stat-label">{{ __('pediatrics.administered') }}</div>
            </div>
            <div class="vacc-stat vacc-stat-pending">
                <div class="vacc-stat-value">{{ $vaccinationSummary['pending'] ?? 0 }}</div>
                <div class="vacc-stat-label">{{ __('pediatrics.pending') }}</div>
            </div>
            <div class="vacc-stat vacc-stat-overdue">
                <div class="vacc-stat-value">{{ $vaccinationSummary['overdue'] ?? 0 }}</div>
                <div class="vacc-stat-label">{{ __('pediatrics.overdue') }}</div>
            </div>
            <div class="vacc-stat vacc-stat-coverage">
                <div class="vacc-stat-value">{{ $vaccinationSummary['coverage_rate'] ?? 0 }}%</div>
                <div class="vacc-stat-label">{{ __('pediatrics.coverage') }}</div>
                <div class="vacc-coverage-bar">
                    <div class="vacc-coverage-fill" style="width: {{ $vaccinationSummary['coverage_rate'] ?? 0 }}%"></div>
                </div>
            </div>
        </div>
    @endif

    @if(!empty($vaccinationSchedule))
        <div class="vaccination-grid">
            @php
                $currentAgeGroup = null;
                $ageGroups = [
                    0 => __('pediatrics.birth'),
                    2 => '2 ' . __('pediatrics.months'),
                    4 => '4 ' . __('pediatrics.months'),
                    6 => '6 ' . __('pediatrics.months'),
                    9 => '9 ' . __('pediatrics.months'),
                    12 => '12 ' . __('pediatrics.months'),
                    18 => '18 ' . __('pediatrics.months'),
                    108 => '9 ' . __('pediatrics.years'),
                    114 => '9.5 ' . __('pediatrics.years'),
                ];
            @endphp

            @foreach($vaccinationSchedule as $vaccine)
                @if($currentAgeGroup !== $vaccine['recommended_age_months'])
                    @php $currentAgeGroup = $vaccine['recommended_age_months']; @endphp
                    <div class="vacc-age-header">
                        <span class="vacc-age-badge">{{ $ageGroups[$vaccine['recommended_age_months']] ?? $vaccine['recommended_age_label'] }}</span>
                    </div>
                @endif

                <div class="vacc-item vacc-item-{{ $vaccine['status'] }}" data-vaccine-id="{{ $vaccine['vaccine_id'] }}" data-record-id="{{ $vaccine['record_id'] }}">
                    <div class="vacc-item-status">
                        @if($vaccine['status'] === 'administered')
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        @elseif($vaccine['status'] === 'overdue')
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                        @endif
                    </div>
                    <div class="vacc-item-info">
                        <div class="vacc-item-name">
                            {{ $vaccine['display_name'] }}
                            @if(!$vaccine['is_mandatory'])
                                <span class="vacc-optional">{{ __('pediatrics.optional') }}</span>
                            @endif
                        </div>
                        <div class="vacc-item-disease">{{ $vaccine['disease'] }}</div>
                    </div>
                    <div class="vacc-item-dates">
                        <div class="vacc-item-scheduled">{{ $vaccine['scheduled_date'] ? \Carbon\Carbon::parse($vaccine['scheduled_date'])->format('d/m/Y') : '-' }}</div>
                        @if($vaccine['administered_date'])
                            <div class="vacc-item-admin">{{ __('pediatrics.done') }}: {{ \Carbon\Carbon::parse($vaccine['administered_date'])->format('d/m/Y') }}</div>
                        @endif
                    </div>
                    <div class="vacc-item-badge">
                        <span class="vacc-status-badge vacc-status-{{ $vaccine['status'] }}">{{ $vaccine['status_label'] }}</span>
                        @if($vaccine['status'] === 'overdue' && $vaccine['days_overdue'] > 0)
                            <span class="vacc-overdue-days">+{{ $vaccine['days_overdue'] }}j</span>
                        @endif
                    </div>
                    <div class="vacc-item-actions">
                        @if($vaccine['status'] !== 'administered')
                            <button type="button" class="btn btn-sm btn-cyan vacc-administer-btn" data-vaccine-id="{{ $vaccine['vaccine_id'] }}" data-scheduled-date="{{ $vaccine['scheduled_date'] }}">
                                {{ __('pediatrics.administer') }}
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="pedia-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-cyan-300"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
            <h4>{{ __('pediatrics.no_vaccination_data') }}</h4>
            <p>{{ __('pediatrics.vaccination_data_requires_birthdate') }}</p>
        </div>
    @endif
</section>

<div class="modal fade" id="vaccinationRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#ecfeff,#cffafe);border-bottom:1px solid #67e8f9">
                <h5 class="modal-title" style="font-weight:800;color:#0e7490">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                    {{ __('pediatrics.record_vaccination') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="vaccinationRecordForm">
                    @csrf
                    <input type="hidden" name="id" id="vaccinationRecordId">
                    <div class="pedia-field mb-3">
                        <label>{{ __('pediatrics.vaccine') }} *</label>
                        <select name="vaccine_id" id="vaccineSelect" class="form-select" required>
                            <option value="">{{ __('pediatrics.select_vaccine') }}</option>
                            @foreach(\Modules\Pediatrics\Models\Vaccine::where('is_active', true)->orderBy('sort_order')->get() as $v)
                                <option value="{{ $v->id }}">{{ $v->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pedia-form-grid pedia-form-grid-2">
                        <div class="pedia-field">
                            <label>{{ __('pediatrics.scheduled_date') }}</label>
                            <input type="date" name="scheduled_date" id="vaccScheduledDate" class="form-control">
                        </div>
                        <div class="pedia-field">
                            <label>{{ __('pediatrics.administered_date') }}</label>
                            <input type="date" name="administered_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="pedia-form-grid pedia-form-grid-2 mt-3">
                        <div class="pedia-field">
                            <label>{{ __('pediatrics.batch_number') }}</label>
                            <input type="text" name="batch_number" class="form-control" placeholder="Lot n°">
                        </div>
                        <div class="pedia-field">
                            <label>{{ __('pediatrics.manufacturer') }}</label>
                            <input type="text" name="manufacturer" class="form-control" placeholder="{{ __('pediatrics.laboratory') }}">
                        </div>
                    </div>
                    <div class="pedia-form-grid pedia-form-grid-2 mt-3">
                        <div class="pedia-field">
                            <label>{{ __('pediatrics.expiry_date') }}</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                        <div class="pedia-field">
                            <label>{{ __('pediatrics.injection_site') }}</label>
                            <select name="injection_site" class="form-select">
                                <option value="">-</option>
                                <option value="left_thigh">{{ __('pediatrics.left_thigh') }}</option>
                                <option value="right_thigh">{{ __('pediatrics.right_thigh') }}</option>
                                <option value="left_arm">{{ __('pediatrics.left_arm') }}</option>
                                <option value="right_arm">{{ __('pediatrics.right_arm') }}</option>
                                <option value="oral">{{ __('pediatrics.oral') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label>{{ __('pediatrics.adverse_reaction') }}</label>
                        <textarea name="adverse_reaction" class="form-control" rows="2" placeholder="{{ __('pediatrics.if_any') }}"></textarea>
                    </div>
                    <div class="mt-3">
                        <label>{{ __('pediatrics.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('pediatrics.cancel') }}</button>
                <button type="button" class="btn btn-cyan" id="submitVaccinationRecord">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    {{ __('pediatrics.save') }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.vaccination-summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:16px}
.vacc-stat{padding:12px;border-radius:14px;border:1px solid #e2e8f0;background:#fff;text-align:center}
.vacc-stat-total{border-color:#e2e8f0}
.vacc-stat-done{border-color:#86efac;background:linear-gradient(135deg,#f0fdf4,#fff)}
.vacc-stat-pending{border-color:#fcd34d;background:linear-gradient(135deg,#fffbeb,#fff)}
.vacc-stat-overdue{border-color:#fca5a5;background:linear-gradient(135deg,#fef2f2,#fff)}
.vacc-stat-coverage{border-color:#a78bfa;background:linear-gradient(135deg,#f5f3ff,#fff)}
.vacc-stat-value{font-size:1.5rem;font-weight:800;color:#0f172a;line-height:1.2}
.vacc-stat-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:#64748b;font-weight:700;margin-top:2px}
.vacc-coverage-bar{height:6px;background:#e2e8f0;border-radius:999px;margin-top:8px;overflow:hidden}
.vacc-coverage-fill{height:100%;background:linear-gradient(90deg,#8b5cf6,#7c3aed);border-radius:999px;transition:width .4s ease}
.vacc-age-header{padding:8px 0;margin-top:8px}
.vacc-age-badge{display:inline-block;font-size:.78rem;font-weight:800;color:#0e7490;background:#cffafe;padding:4px 14px;border-radius:999px;border:1px solid #67e8f9}
.vacc-item{display:grid;grid-template-columns:36px 1fr auto auto auto;gap:12px;align-items:center;padding:10px 14px;border-radius:12px;border:1px solid #e2e8f0;background:#fff;margin-bottom:6px;transition:all .15s ease}
.vacc-item:hover{border-color:#67e8f9;box-shadow:0 2px 8px rgba(6,182,212,.08)}
.vacc-item-administered{background:#f0fdf4;border-color:#bbf7d0}
.vacc-item-administered .vacc-item-status{color:#16a34a}
.vacc-item-overdue{background:#fef2f2;border-color:#fecaca}
.vacc-item-overdue .vacc-item-status{color:#dc2626}
.vacc-item-pending .vacc-item-status{color:#d97706}
.vacc-item-name{font-size:.88rem;font-weight:700;color:#0f172a}
.vacc-item-disease{font-size:.76rem;color:#64748b;margin-top:1px}
.vacc-optional{font-size:.65rem;font-weight:600;color:#94a3b8;background:#f1f5f9;padding:1px 6px;border-radius:999px;margin-inline-start:6px}
.vacc-item-dates{text-align:end}
.vacc-item-scheduled{font-size:.82rem;font-weight:600;color:#334155}
.vacc-item-admin{font-size:.72rem;color:#16a34a;font-weight:600}
.vacc-status-badge{font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:999px;white-space:nowrap}
.vacc-status-administered{background:#dcfce7;color:#166534}
.vacc-status-pending{background:#fef3c7;color:#92400e}
.vacc-status-overdue{background:#fee2e2;color:#991b1b}
.vacc-status-refused{background:#f1f5f9;color:#475569}
.vacc-status-contraindicated{background:#f1f5f9;color:#475569}
.vacc-overdue-days{display:block;font-size:.65rem;color:#dc2626;font-weight:800;margin-top:2px}
.vacc-administer-btn{font-size:.75rem;padding:4px 12px}
@media (max-width:1200px){.vaccination-summary{grid-template-columns:repeat(3,1fr)}.vacc-item{grid-template-columns:36px 1fr auto}}
@media (max-width:768px){.vaccination-summary{grid-template-columns:repeat(2,1fr)}.vacc-item{grid-template-columns:36px 1fr}.vacc-item-dates,.vacc-item-actions{display:none}}
</style>
