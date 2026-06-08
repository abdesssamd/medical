@props(['chronicConditions' => null, 'selectedPatientId' => 0])

<section id="chronic-diseases" class="card mb-3" data-care-tab-panel="internal-medicine">
    <div class="section-head">
        <h3 class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-danger"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            {{ __('internal_med.chronic_conditions') }}
        </h3>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#chronicConditionModal">
                {{ __('internal_med.add_condition') }}
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>{{ __('internal_med.diagnosis') }}</th>
                    <th>{{ __('internal_med.icd10') }}</th>
                    <th>{{ __('internal_med.discovery_date') }}</th>
                    <th>{{ __('internal_med.status') }}</th>
                    <th>{{ __('internal_med.notes') }}</th>
                    <th>{{ __('internal_med.actions') }}</th>
                </tr>
            </thead>
            <tbody id="chronicConditionsBody">
                @forelse(($chronicConditions ?? collect()) as $condition)
                    <tr data-condition-id="{{ $condition->id }}">
                        <td class="fw-semibold">{{ $condition->diagnosis_name }}</td>
                        <td><code>{{ $condition->icd10_code ?? '-' }}</code></td>
                        <td>{{ $condition->discovery_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            @php $statusColors = ['active' => 'bg-danger', 'controlled' => 'bg-warning', 'uncontrolled' => 'bg-danger text-white', 'resolved' => 'bg-success']; @endphp
                            <span class="badge {{ $statusColors[$condition->status] ?? 'bg-secondary' }}">
                                {{ __("internal_med.status_{$condition->status}") }}
                            </span>
                        </td>
                        <td class="text-secondary small">{{ Str::limit($condition->notes, 60) }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-condition-btn"
                                data-id="{{ $condition->id }}"
                                data-icd10="{{ $condition->icd10_code }}"
                                data-name="{{ $condition->diagnosis_name }}"
                                data-date="{{ $condition->discovery_date?->format('Y-m-d') }}"
                                data-status="{{ $condition->status }}"
                                data-notes="{{ $condition->notes }}">
                                {{ __('internal_med.edit') }}
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-condition-btn" data-id="{{ $condition->id }}">
                                {{ __('internal_med.delete') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-secondary text-center py-4">{{ __('internal_med.no_conditions') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="modal fade" id="chronicConditionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="chronicConditionForm">
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" id="conditionId" value="">
                <div class="modal-header" style="background:linear-gradient(135deg,#fef2f2,#fee2e2);border-bottom:1px solid #fca5a5">
                    <h5 class="modal-title" id="chronicConditionModalTitle">{{ __('internal_med.add_condition') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('internal_med.diagnosis') }} *</label>
                        <input type="text" name="diagnosis_name" id="condNameInput" class="form-control" required maxlength="255" placeholder="{{ __('internal_med.diagnosis_placeholder') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('internal_med.icd10') }}</label>
                        <input type="text" name="icd10_code" id="condIcd10Input" class="form-control" maxlength="20" placeholder="E10, I10...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('internal_med.discovery_date') }}</label>
                        <input type="date" name="discovery_date" id="condDateInput" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('internal_med.status') }}</label>
                        <select name="status" id="condStatusInput" class="form-select">
                            <option value="active">{{ __('internal_med.status_active') }}</option>
                            <option value="controlled">{{ __('internal_med.status_controlled') }}</option>
                            <option value="uncontrolled">{{ __('internal_med.status_uncontrolled') }}</option>
                            <option value="resolved">{{ __('internal_med.status_resolved') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('internal_med.notes') }}</label>
                        <textarea name="notes" id="condNotesInput" class="form-control" rows="3"></textarea>
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
