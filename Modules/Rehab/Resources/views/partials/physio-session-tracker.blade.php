@props(['activePrescription' => null, 'stats' => [], 'sessions' => collect(), 'selectedPatientId' => 0])

<section id="rehab-physio-tracker" class="card" data-care-tab-panel="rehab" data-prescription-id="{{ $activePrescription?->id }}">
    <div class="section-head">
        <h3 class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-indigo-600"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            {{ __('rehab.physio_tracker_title') }}
        </h3>
        @if($activePrescription && $activePrescription->canAddSession())
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#rehabSessionModal">
            {{ __('rehab.validate_session') }}
        </button>
        @endif
    </div>

    @if($activePrescription)
    <div class="physio-info-bar">
        <div class="physio-info-item">
            <span class="physio-info-label">{{ __('rehab.patient') }}</span>
            <span class="physio-info-value">{{ $activePrescription->patient?->full_name ?? '-' }}</span>
        </div>
        <div class="physio-info-item">
            <span class="physio-info-label">{{ __('rehab.diagnosis') }}</span>
            <span class="physio-info-value">{{ $activePrescription->diagnosis }}</span>
        </div>
        <div class="physio-info-item">
            <span class="physio-info-label">{{ __('rehab.session_progress') }}</span>
            <span class="physio-info-value">{{ $stats['total_completed'] ?? 0 }} / {{ $stats['total_prescribed'] ?? 0 }}</span>
        </div>
        <div class="physio-info-item">
            <span class="physio-info-label">{{ __('rehab.remaining_sessions') }}</span>
            <span class="physio-info-value text-warning fw-bold">{{ $stats['remaining'] ?? 0 }}</span>
        </div>
    </div>

    @if(!$activePrescription->canAddSession())
    <div class="alert alert-warning mt-3">
        @if($activePrescription->status !== 'in_progress')
            {{ __('rehab.prescription_not_active') }}
        @else
            {{ __('rehab.max_sessions_reached') }}
        @endif
    </div>
    @endif

    @if($sessions->isNotEmpty())
    <div class="physio-sessions-list mt-3">
        <h4>{{ __('rehab.sessions_history') }}</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('rehab.date') }}</th>
                        <th>{{ __('rehab.pain_score') }}</th>
                        <th>{{ __('rehab.duration') }}</th>
                        <th>{{ __('rehab.status') }}</th>
                        <th>{{ __('rehab.physiotherapist') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sessions as $session)
                    <tr>
                        <td>{{ $session->session_number }}</td>
                        <td>{{ $session->session_date->format('d/m/Y') }}</td>
                        <td>
                            <span class="pain-badge pain-{{ $session->pain_score <= 3 ? 'low' : ($session->pain_score <= 6 ? 'medium' : 'high') }}">
                                {{ $session->pain_score ?? '-' }}/10
                            </span>
                        </td>
                        <td>{{ $session->duration_minutes ? $session->duration_minutes . ' min' : '-' }}</td>
                        <td><span class="badge bg-{{ $session->status === 'completed' ? 'success' : ($session->status === 'cancelled' ? 'danger' : 'secondary') }}">{{ $session->status_label }}</span></td>
                        <td>{{ $session->physiotherapist?->full_name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @include('rehab::partials.progress-charts', ['stats' => $stats, 'sessions' => $sessions])
    @else
    <div class="text-center py-4">
        <p class="text-muted">{{ __('rehab.no_active_prescription_for_physio') }}</p>
    </div>
    @endif
</section>

<div class="modal fade" id="rehabSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rehabSessionForm">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('rehab.validate_session') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('rehab.session_date') }}</label>
                        <input type="date" name="session_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('rehab.pain_score') }} (EVA 0-10)</label>
                        <div class="pain-slider-container">
                            <input type="range" name="pain_score" class="form-range pain-slider" min="0" max="10" value="0" id="painSlider">
                            <div class="pain-slider-labels">
                                <span>0</span><span>5</span><span>10</span>
                            </div>
                            <div class="pain-display text-center mt-2">
                                <span id="painValue" class="badge bg-success fs-4">0</span>
                                <span id="painLabel" class="ms-2 text-muted">{{ __('rehab.no_pain') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('rehab.duration_minutes') }}</label>
                        <input type="number" name="duration_minutes" class="form-control" min="1" max="300" value="45">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('rehab.exercises_performed') }}</label>
                        <div id="exercisesChecklist" class="border rounded p-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="exercises_performed[]" value="Mobilisation passive" id="ex1">
                                <label class="form-check-label" for="ex1">{{ __('rehab.passive_mobilization') }}</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="exercises_performed[]" value="Mobilisation active" id="ex2">
                                <label class="form-check-label" for="ex2">{{ __('rehab.active_mobilization') }}</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="exercises_performed[]" value="Renforcement musculaire" id="ex3">
                                <label class="form-check-label" for="ex3">{{ __('rehab.muscle_strengthening') }}</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="exercises_performed[]" value="Étirements" id="ex4">
                                <label class="form-check-label" for="ex4">{{ __('rehab.stretching') }}</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="exercises_performed[]" value="Proprioception" id="ex5">
                                <label class="form-check-label" for="ex5">{{ __('rehab.proprioception') }}</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="exercises_performed[]" value="Massage thérapeutique" id="ex6">
                                <label class="form-check-label" for="ex6">{{ __('rehab.therapeutic_massage') }}</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="exercises_performed[]" value="Électrothérapie" id="ex7">
                                <label class="form-check-label" for="ex7">{{ __('rehab.electrotherapy') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('rehab.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('rehab.session_notes_placeholder') }}"></textarea>
                    </div>
                    <input type="hidden" name="status" value="completed">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('rehab.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('rehab.validate_session') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
#rehab-physio-tracker{background:linear-gradient(180deg,#eef2ff 0%,#e0e7ff 100%);border:1px solid #a5b4fc}
#rehab-physio-tracker .section-head h3{color:#4338ca}
.physio-info-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;background:#fff;border:1px solid #c7d2fe;border-radius:12px;padding:16px}
.physio-info-item{display:flex;flex-direction:column;gap:2px}
.physio-info-label{font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em}
.physio-info-value{font-size:0.95rem;font-weight:600;color:#1f2937}
.physio-sessions-list{background:#fff;border:1px solid #c7d2fe;border-radius:12px;padding:16px}
.physio-sessions-list h4{font-size:0.9rem;font-weight:600;color:#4338ca;margin-bottom:12px}
.pain-badge{display:inline-block;padding:4px 10px;border-radius:20px;font-weight:600;font-size:0.8rem}
.pain-low{background:#dcfce7;color:#166534}
.pain-medium{background:#fef3c7;color:#92400e}
.pain-high{background:#fee2e2;color:#991b1b}
.pain-slider{-webkit-appearance:none;width:100%;height:12px;border-radius:6px;background:linear-gradient(90deg,#22c55e 0%,#eab308 50%,#ef4444 100%);outline:none}
.pain-slider::-webkit-slider-thumb{-webkit-appearance:none;width:24px;height:24px;border-radius:50%;background:#fff;border:3px solid #4338ca;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,0.2)}
.pain-slider-labels{display:flex;justify-content:space-between;font-size:0.75rem;color:#6b7280;margin-top:4px}
</style>
