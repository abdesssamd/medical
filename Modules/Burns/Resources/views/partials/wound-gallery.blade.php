@props(['admission' => null, 'woundEvolutions' => null, 'selectedPatientId' => 0])

<section id="wound-evolution-gallery" class="card burns-card" data-care-tab-panel="clinical" data-admission-id="{{ $admission?->id ?? '' }}">
    <div class="section-head">
        <h3 class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-red-500"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            {{ __('burns.wound_evolution') }}
        </h3>
        <div class="burns-toolbar">
            <button type="button" class="btn btn-sm btn-outline-red" data-bs-toggle="modal" data-bs-target="#woundEvolutionModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('burns.new_evolution') }}
            </button>
        </div>
    </div>

    @if($woundEvolutions && $woundEvolutions->count() > 0)
        <div class="wound-gallery-grid">
            @foreach($woundEvolutions as $evolution)
                <div class="wound-card wound-status-{{ $evolution->wound_status }}">
                    <div class="wound-card-header">
                        <div class="wound-region-badge">{{ $evolution->body_region }}</div>
                        <div class="wound-status-badge wound-status-{{ $evolution->wound_status }}">{{ $evolution->wound_status_label }}</div>
                    </div>

                    <div class="wound-card-body">
                        <div class="wound-datetime">{{ $evolution->evolution_datetime->format('d/m/Y H:i') }}</div>

                        @if($evolution->depth_current)
                            <div class="wound-depth">
                                <span class="wound-depth-label">{{ __('burns.depth') }}:</span>
                                {{ $evolution->depth_current }}
                            </div>
                        @endif

                        @if($evolution->wound_description)
                            <div class="wound-description">{{ Str::limit($evolution->wound_description, 120) }}</div>
                        @endif

                        @if($evolution->dressing_type)
                            <div class="wound-dressing">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                {{ $evolution->dressing_type }}
                                @if($evolution->dressing_change_frequency_hours)
                                    <span class="wound-dressing-freq">({{ $evolution->dressing_change_frequency_hours }}h)</span>
                                @endif
                            </div>
                        @endif

                        @if($evolution->graft_planned)
                            <div class="wound-graft-info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                <strong>{{ __('burns.graft_planned') }}</strong>
                                @if($evolution->graft_type)
                                    <span class="wound-graft-type">{{ $evolution->graft_type_label }}</span>
                                @endif
                                @if($evolution->graft_planned_date)
                                    <span class="wound-graft-date">{{ $evolution->graft_planned_date->format('d/m/Y') }}</span>
                                @endif
                                @if($evolution->graft_completed)
                                    <span class="wound-graft-completed wound-outcome-{{ $evolution->graft_outcome }}">{{ __('burns.completed') }}: {{ $evolution->graft_outcome }}</span>
                                @endif
                            </div>
                        @endif

                        @if($evolution->flap_planned)
                            <div class="wound-flap-info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                <strong>{{ __('burns.flap_planned') }}</strong>
                                @if($evolution->flap_type)
                                    <span class="wound-flap-type">{{ $evolution->flap_type }}</span>
                                @endif
                            </div>
                        @endif

                        @if($evolution->infection_confirmed)
                            <div class="wound-infection-alert">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                <strong>{{ __('burns.infection_confirmed') }}</strong>
                                @if($evolution->infection_organism)
                                    <span class="wound-organism">{{ $evolution->infection_organism }}</span>
                                @endif
                            </div>
                        @endif

                        @if($evolution->pharmacy_order_needed)
                            <div class="wound-pharmacy-order {{ $evolution->pharmacy_order_sent ? 'sent' : 'pending' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                @if($evolution->pharmacy_order_sent)
                                    {{ __('burns.pharmacy_order_sent') }} ({{ $evolution->pharmacy_order_sent_at->format('d/m H:i') }})
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-red send-pharmacy-order-btn" data-evolution-id="{{ $evolution->id }}">
                                        {{ __('burns.send_pharmacy_order') }}
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if($evolution->notes)
                        <div class="wound-card-footer">
                            <div class="wound-notes">{{ Str::limit($evolution->notes, 100) }}</div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="burns-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-red-300"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            <h4>{{ __('burns.no_wound_evolutions') }}</h4>
            <p>{{ __('burns.click_to_add_evolution') }}</p>
        </div>
    @endif
</section>

<div class="modal fade" id="woundEvolutionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#fef2f2,#fee2e2);border-bottom:1px solid #fca5a5">
                <h5 class="modal-title" style="font-weight:800;color:#991b1b">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    {{ __('burns.new_wound_evolution') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="woundEvolutionForm">
                    <div class="burns-form-grid burns-form-grid-2">
                        <div class="burns-field">
                            <label>{{ __('burns.body_region') }} *</label>
                            <input type="text" name="body_region" class="form-control" required placeholder="{{ __('burns.e.g._anterior_trunk') }}">
                        </div>
                        <div class="burns-field">
                            <label>{{ __('burns.wound_status') }} *</label>
                            <select name="wound_status" class="form-select" required>
                                <option value="healing">{{ __('burns.healing') }}</option>
                                <option value="stable">{{ __('burns.stable') }}</option>
                                <option value="deteriorating">{{ __('burns.deteriorating') }}</option>
                                <option value="infected">{{ __('burns.infected') }}</option>
                                <option value="grafted">{{ __('burns.grafted') }}</option>
                                <option value="closed">{{ __('burns.closed') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="burns-form-grid burns-form-grid-2 mt-3">
                        <div class="burns-field">
                            <label>{{ __('burns.current_depth') }}</label>
                            <select name="depth_current" class="form-select">
                                <option value="">-</option>
                                <option value="superficial">{{ __('burns.first_degree') }}</option>
                                <option value="partial_superficial">{{ __('burns.second_degree_superficial') }}</option>
                                <option value="partial_deep">{{ __('burns.second_degree_deep') }}</option>
                                <option value="full_thickness">{{ __('burns.third_degree') }}</option>
                            </select>
                        </div>
                        <div class="burns-field">
                            <label>{{ __('burns.dressing_type') }}</label>
                            <input type="text" name="dressing_type" class="form-control" placeholder="{{ __('burns.e.g._silver_sulfadiazine') }}">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label>{{ __('burns.wound_description') }}</label>
                        <textarea name="wound_description" class="form-control" rows="3" placeholder="{{ __('burns.describe_wound_appearance') }}"></textarea>
                    </div>

                    <div class="burns-form-section mt-3">
                        <h5>{{ __('burns.surgical_planning') }}</h5>
                        <div class="burns-form-grid burns-form-grid-3">
                            <div class="burns-field">
                                <label class="burns-checkbox-label">
                                    <input type="checkbox" name="graft_planned" value="1">
                                    {{ __('burns.graft_planned') }}
                                </label>
                            </div>
                            <div class="burns-field">
                                <label>{{ __('burns.graft_type') }}</label>
                                <select name="graft_type" class="form-select">
                                    <option value="">-</option>
                                    <option value="split_thickness">{{ __('burns.split_thickness') }}</option>
                                    <option value="full_thickness">{{ __('burns.full_thickness') }}</option>
                                    <option value="cultured_epithelial">{{ __('burns.cultured_epithelial') }}</option>
                                    <option value="dermal_substitute">{{ __('burns.dermal_substitute') }}</option>
                                </select>
                            </div>
                            <div class="burns-field">
                                <label>{{ __('burns.planned_date') }}</label>
                                <input type="date" name="graft_planned_date" class="form-control">
                            </div>
                        </div>
                        <div class="burns-form-grid burns-form-grid-2 mt-2">
                            <div class="burns-field">
                                <label>{{ __('burns.donor_site') }}</label>
                                <input type="text" name="graft_donor_site" class="form-control" placeholder="{{ __('burns.e.g._lateral_thigh') }}">
                            </div>
                            <div class="burns-field">
                                <label class="burns-checkbox-label">
                                    <input type="checkbox" name="flap_planned" value="1">
                                    {{ __('burns.flap_planned') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="burns-form-section mt-3">
                        <h5>{{ __('burns.pharmacy_order') }}</h5>
                        <div class="burns-form-grid burns-form-grid-2">
                            <div class="burns-field">
                                <label class="burns-checkbox-label">
                                    <input type="checkbox" name="pharmacy_order_needed" value="1">
                                    {{ __('burns.pharmacy_order_needed') }}
                                </label>
                            </div>
                            <div class="burns-field">
                                <label>{{ __('burns.order_items') }}</label>
                                <input type="text" name="pharmacy_order_items" class="form-control" placeholder="{{ __('burns.dressings_medications') }}">
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label>{{ __('burns.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('burns.cancel') }}</button>
                <button type="button" class="btn btn-red" id="submitWoundEvolution">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    {{ __('burns.save') }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.wound-gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:14px}
.wound-card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;transition:all .2s ease}
.wound-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.08);transform:translateY(-2px)}
.wound-card.wound-status-healing{border-inline-start:4px solid #16a34a}
.wound-card.wound-status-stable{border-inline-start:4px solid #2563eb}
.wound-card.wound-status-deteriorating{border-inline-start:4px solid #d97706}
.wound-card.wound-status-infected{border-inline-start:4px solid #dc2626}
.wound-card.wound-status-grafted{border-inline-start:4px solid #7c3aed}
.wound-card.wound-status-closed{border-inline-start:4px solid #64748b}
.wound-card-header{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.wound-region-badge{font-size:.82rem;font-weight:800;color:#0f172a;background:#e2e8f0;padding:4px 12px;border-radius:999px}
.wound-status-badge{font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:999px}
.wound-status-healing{background:#dcfce7;color:#166534}
.wound-status-stable{background:#dbeafe;color:#1e40af}
.wound-status-deteriorating{background:#fef3c7;color:#92400e}
.wound-status-infected{background:#fee2e2;color:#991b1b}
.wound-status-grafted{background:#f3e8ff;color:#6b21a8}
.wound-status-closed{background:#f1f5f9;color:#475569}
.wound-card-body{padding:14px;display:grid;gap:8px}
.wound-datetime{font-size:.78rem;font-weight:700;color:#64748b}
.wound-depth{font-size:.85rem;color:#334155}
.wound-depth-label{font-weight:700;color:#64748b}
.wound-description{font-size:.85rem;color:#334155;line-height:1.5}
.wound-dressing,.wound-graft-info,.wound-flap-info,.wound-infection-alert,.wound-pharmacy-order{display:flex;align-items:center;gap:6px;font-size:.82rem;color:#334155;padding:6px 10px;border-radius:8px;background:#f8fafc}
.wound-dressing-freq{color:#94a3b8;font-size:.76rem}
.wound-graft-info{background:#f3e8ff;color:#6b21a8}
.wound-graft-type,.wound-graft-date{font-size:.76rem;background:#e9d5ff;padding:2px 8px;border-radius:999px}
.wound-graft-completed{font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:999px}
.wound-outcome-success{background:#dcfce7;color:#166534}
.wound-outcome-partial_failure{background:#fef3c7;color:#92400e}
.wound-outcome-failure{background:#fee2e2;color:#991b1b}
.wound-flap-info{background:#dbeafe;color:#1e40af}
.wound-infection-alert{background:#fee2e2;color:#991b1b;font-weight:700}
.wound-organism{font-size:.76rem;background:#fecaca;padding:2px 8px;border-radius:999px}
.wound-pharmacy-order{justify-content:space-between}
.wound-pharmacy-order.sent{background:#dcfce7;color:#166534}
.wound-pharmacy-order.pending{background:#fef3c7;color:#92400e}
.wound-card-footer{padding:10px 14px;background:#f8fafc;border-top:1px solid #e2e8f0}
.wound-notes{font-size:.78rem;color:#64748b;font-style:italic}
.burns-form-section{padding:14px;border:1px solid #f1f5f9;border-radius:14px;background:#fafbfc}
.burns-form-section h5{font-size:.88rem;font-weight:800;color:#0f172a;margin:0 0 12px}
.burns-form-grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.burns-checkbox-label{display:flex;align-items:center;gap:8px;font-size:.85rem;font-weight:600;color:#334155;cursor:pointer}
.burns-checkbox-label input[type="checkbox"]{width:18px;height:18px;accent-color:#dc2626}
@media (max-width:768px){.wound-gallery-grid{grid-template-columns:1fr}.burns-form-grid-3{grid-template-columns:1fr}}
</style>
