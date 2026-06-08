@props(['prescriptions' => collect(), 'activePrescription' => null, 'stats' => [], 'evaluations' => collect(), 'sessions' => collect(), 'selectedPatientId' => 0])

<section id="rehab-doctor-dashboard" class="card" data-care-tab-panel="rehab" data-prescription-id="{{ $activePrescription?->id }}">
    <div class="section-head">
        <h3 class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-purple-600"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
            {{ __('rehab.doctor_dashboard_title') }}
        </h3>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#rehabPrescriptionModal">
            {{ __('rehab.new_prescription') }}
        </button>
    </div>

    @if($activePrescription)
    <div class="rehab-stats-grid">
        <div class="rehab-stat-card">
            <div class="rehab-stat-label">{{ __('rehab.diagnosis') }}</div>
            <div class="rehab-stat-value">{{ $activePrescription->diagnosis }}</div>
        </div>
        <div class="rehab-stat-card">
            <div class="rehab-stat-label">{{ __('rehab.progress') }}</div>
            <div class="rehab-stat-value">{{ $stats['total_completed'] ?? 0 }} / {{ $stats['total_prescribed'] ?? 0 }}</div>
            <div class="rehab-progress-bar">
                <div class="rehab-progress-fill" style="width: {{ $stats['progress_percentage'] ?? 0 }}%"></div>
            </div>
        </div>
        <div class="rehab-stat-card">
            <div class="rehab-stat-label">{{ __('rehab.remaining_sessions') }}</div>
            <div class="rehab-stat-value text-warning">{{ $stats['remaining'] ?? 0 }}</div>
        </div>
        <div class="rehab-stat-card">
            <div class="rehab-stat-label">{{ __('rehab.average_pain') }}</div>
            <div class="rehab-stat-value">{{ $stats['average_pain'] ?? 'N/A' }} / 10</div>
        </div>
    </div>

    <div class="rehab-evaluations-section">
        <h4>{{ __('rehab.evaluations') }}</h4>
        <div class="d-flex gap-2 mb-3">
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#rehabEvaluationModal" data-eval-type="initial">
                {{ __('rehab.initial_evaluation') }}
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#rehabEvaluationModal" data-eval-type="final">
                {{ __('rehab.final_evaluation') }}
            </button>
        </div>

        @if($evaluations->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>{{ __('rehab.date') }}</th>
                        <th>{{ __('rehab.type') }}</th>
                        <th>{{ __('rehab.notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($evaluations as $eval)
                    <tr>
                        <td>{{ $eval->evaluation_date->format('d/m/Y') }}</td>
                        <td><span class="badge bg-{{ $eval->type === 'initial' ? 'primary' : ($eval->type === 'final' ? 'success' : 'secondary') }}">{{ $eval->type_label }}</span></td>
                        <td>{{ Str::limit($eval->notes, 80) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-muted small">{{ __('rehab.no_evaluations') }}</p>
        @endif
    </div>

    @include('rehab::partials.progress-charts', ['stats' => $stats, 'sessions' => $sessions])
    @else
    <div class="text-center py-4">
        <p class="text-muted">{{ __('rehab.no_active_prescription') }}</p>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rehabPrescriptionModal">
            {{ __('rehab.create_first_prescription') }}
        </button>
    </div>
    @endif

    @if($prescriptions->isNotEmpty())
    <div class="rehab-history-section mt-4">
        <h4>{{ __('rehab.prescription_history') }}</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>{{ __('rehab.date') }}</th>
                        <th>{{ __('rehab.diagnosis') }}</th>
                        <th>{{ __('rehab.sessions') }}</th>
                        <th>{{ __('rehab.status') }}</th>
                        <th>{{ __('rehab.doctor') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescriptions as $p)
                    <tr class="{{ $p->id === $activePrescription?->id ? 'table-active' : '' }}">
                        <td>{{ $p->created_at->format('d/m/Y') }}</td>
                        <td>{{ Str::limit($p->diagnosis, 40) }}</td>
                        <td>{{ $p->completed_sessions_count }} / {{ $p->prescribed_sessions_count }}</td>
                        <td><span class="badge bg-{{ $p->status === 'completed' ? 'success' : ($p->status === 'in_progress' ? 'primary' : 'secondary') }}">{{ $p->status_label }}</span></td>
                        <td>{{ $p->doctor?->full_name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</section>

<div class="modal fade" id="rehabPrescriptionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="rehabPrescriptionForm">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('rehab.new_prescription') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('rehab.diagnosis') }} <span class="text-danger">*</span></label>
                        <textarea name="diagnosis" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('rehab.prescribed_sessions_count') }} <span class="text-danger">*</span></label>
                            <input type="number" name="prescribed_sessions_count" class="form-control" min="1" max="200" value="10" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('rehab.status') }}</label>
                            <select name="status" class="form-select">
                                <option value="pending">{{ __('rehab.pending') }}</option>
                                <option value="in_progress">{{ __('rehab.in_progress') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('rehab.start_date') }}</label>
                            <input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('rehab.end_date') }}</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('rehab.objectives') }}</label>
                        <textarea name="objectives" class="form-control" rows="3" placeholder="{{ __('rehab.objectives_placeholder') }}"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('rehab.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('rehab.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('rehab.save_prescription') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rehabEvaluationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="rehabEvaluationForm">
                <input type="hidden" name="type" id="rehabEvalType" value="initial">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('rehab.evaluation') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('rehab.evaluation_date') }}</label>
                        <input type="date" name="evaluation_date" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('rehab.goniometry') }}</label>
                        <div id="goniometryFields" class="border rounded p-3">
                            <div class="gonio-row mb-2">
                                <div class="row g-2">
                                    <div class="col-md-4"><input type="text" class="form-control form-control-sm" placeholder="{{ __('rehab.joint') }}" name="goniometry[0][joint]"></div>
                                    <div class="col-md-4"><input type="number" class="form-control form-control-sm" placeholder="{{ __('rehab.flexion') }} (°)" name="goniometry[0][flexion]"></div>
                                    <div class="col-md-4"><input type="number" class="form-control form-control-sm" placeholder="{{ __('rehab.extension') }} (°)" name="goniometry[0][extension]"></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="addGonioRow">+ {{ __('rehab.add_joint') }}</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('rehab.muscle_testing') }}</label>
                        <div id="muscleTestingFields" class="border rounded p-3">
                            <div class="muscle-row mb-2">
                                <div class="row g-2">
                                    <div class="col-md-6"><input type="text" class="form-control form-control-sm" placeholder="{{ __('rehab.muscle_group') }}" name="muscle_testing[0][group]"></div>
                                    <div class="col-md-3">
                                        <select class="form-select form-select-sm" name="muscle_testing[0][grade]">
                                            <option value="">{{ __('rehab.grade') }}</option>
                                            <option value="0">0 - Aucune contraction</option>
                                            <option value="1">1 - Contraction palpable</option>
                                            <option value="2">2 - Mouvement sans gravité</option>
                                            <option value="3">3 - Mouvement contre gravité</option>
                                            <option value="4">4 - Mouvement contre résistance</option>
                                            <option value="5">5 - Force normale</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3"><input type="text" class="form-control form-control-sm" placeholder="{{ __('rehab.side') }}" name="muscle_testing[0][side]"></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="addMuscleRow">+ {{ __('rehab.add_muscle') }}</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('rehab.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('rehab.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('rehab.save_evaluation') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
#rehab-doctor-dashboard{background:linear-gradient(180deg,#faf5ff 0%,#f3e8ff 100%);border:1px solid #d8b4fe}
#rehab-doctor-dashboard .section-head h3{color:#7c3aed}
.rehab-stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px}
.rehab-stat-card{background:#fff;border:1px solid #e9d5ff;border-radius:12px;padding:16px}
.rehab-stat-label{font-size:0.75rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px}
.rehab-stat-value{font-size:1.5rem;font-weight:700;color:#1f2937}
.rehab-progress-bar{height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;margin-top:8px}
.rehab-progress-fill{height:100%;background:linear-gradient(90deg,#8b5cf6,#a78bfa);border-radius:4px;transition:width 0.5s ease}
.rehab-evaluations-section,.rehab-history-section{background:#fff;border:1px solid #e9d5ff;border-radius:12px;padding:16px;margin-bottom:16px}
.rehab-evaluations-section h4,.rehab-history-section h4{font-size:0.9rem;font-weight:600;color:#7c3aed;margin-bottom:12px}
</style>
