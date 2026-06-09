@extends('layouts.admin')

@section('title', 'Paramétrage des plannings')

@section('content')
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h1 class="page-title">Paramétrage des plannings</h1>
            <p class="text-secondary mb-0">Configurer les modes de planification par spécialiste.</p>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('admin.settings') }}">Paramètres généraux</a>
        </div>
    </section>

    <section class="card p-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-lg-4">
                <label class="form-label">Organisation</label>
                <select name="organization_id" class="form-select" onchange="this.form.submit()">
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" @selected($organizationId == $org->id)>{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-6">
                <label class="form-label">Professionnel</label>
                <select name="professional_id" class="form-select" onchange="this.form.submit()">
                    @foreach($professionals as $pro)
                        <option value="{{ $pro->id }}" @selected($selectedProfessionalId == $pro->id)>
                            {{ $pro->display_name }} ({{ $pro->specialty?->name ?? 'Généraliste' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <button type="submit" class="btn btn-primary w-100">Charger</button>
            </div>
        </form>
    </section>

    @if($selectedProfessional)
        <section class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="section-title mb-0">
                    Planning de <strong>{{ $selectedProfessional->display_name }}</strong>
                    <span class="badge bg-secondary ms-2">{{ $selectedProfessional->specialty?->name ?? 'Général' }}</span>
                </h2>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                    + Ajouter un créneau
                </button>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jour</th>
                            <th>Horaire</th>
                            <th>Mode</th>
                            <th>Type d'acte</th>
                            <th>Durée</th>
                            <th>Max/jour</th>
                            <th>Actif</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grid as $day)
                            @foreach($day['plannings'] as $p)
                                <tr>
                                    <td><strong>{{ $day['day_name'] }}</strong></td>
                                    <td>{{ substr($p['start_time'], 0, 5) }} — {{ substr($p['end_time'], 0, 5) }}</td>
                                    <td>
                                        <span class="badge @switch($p['planning_mode'])
                                            @case('by_act') bg-info @break
                                            @case('mixed') bg-warning @break
                                            @default bg-secondary @endswitch
                                        ">
                                            {{ $planningModes[$p['planning_mode']] ?? $p['planning_mode'] }}
                                        </span>
                                    </td>
                                    <td>{{ $p['appointment_type'] ?? '—' }}</td>
                                    <td>{{ $p['consultation_minutes'] }} min</td>
                                    <td>{{ $p['max_patients'] ?? '∞' }}</td>
                                    <td>
                                        <span class="badge {{ $p['is_active'] ? 'bg-success' : 'bg-danger' }}">
                                            {{ $p['is_active'] ? 'Oui' : 'Non' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-secondary edit-btn"
                                                data-id="{{ $p['id'] }}"
                                                data-start="{{ substr($p['start_time'], 0, 5) }}"
                                                data-end="{{ substr($p['end_time'], 0, 5) }}"
                                                data-mode="{{ $p['planning_mode'] }}"
                                                data-acte="{{ $p['appointment_type_id'] }}"
                                                data-duration="{{ $p['consultation_minutes'] }}"
                                                data-max="{{ $p['max_patients'] }}"
                                                data-active="{{ $p['is_active'] ? '1' : '0' }}"
                                                data-bs-toggle="modal" data-bs-target="#editModal">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            <form method="POST" action="{{ route('admin.planning.destroy', $p['id']) }}" onsubmit="return confirm('Supprimer ce créneau ?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            @if(count($day['plannings']) === 0)
                                <tr><td colspan="8" class="text-muted small">{{ $day['day_name'] }} — Aucun créneau configuré</td></tr>
                            @endif
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">Sélectionnez un professionnel pour voir son planning</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>

{{-- Modal Ajout --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.planning.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="professional_id" value="{{ $selectedProfessionalId }}">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un créneau</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label">Jour</label>
                        <select name="day_of_week" class="form-select" required>
                            @foreach(['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'] as $i => $d)
                                <option value="{{ $i }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Début</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Fin</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Mode de planification</label>
                        <select name="planning_mode" class="form-select" id="planningMode" required>
                            @foreach($planningModes as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6" id="acteField">
                        <label class="form-label">Type d'acte</label>
                        <select name="appointment_type_id" class="form-select">
                            <option value="">— Sélectionner —</option>
                            @foreach($appointmentTypes as $specialty => $types)
                                <optgroup label="{{ $specialty }}">
                                    @foreach($types as $at)
                                        <option value="{{ $at->id }}">
                                            {{ $at->name }} ({{ $at->duration_minutes }} min)
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Durée consultation (min)</label>
                        <input type="number" name="consultation_minutes" class="form-control"
                               value="{{ config('appointment.default_consultation_minutes', 20) }}" min="5">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Max patients / jour</label>
                        <input type="number" name="max_patients_per_day" class="form-control" placeholder="Illimité">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Édition --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="" class="modal-content" id="editForm">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Modifier le créneau</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label">Début</label>
                        <input type="time" name="start_time" id="edit_start" class="form-control" required>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Fin</label>
                        <input type="time" name="end_time" id="edit_end" class="form-control" required>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Mode</label>
                        <select name="planning_mode" class="form-select" id="edit_mode" required>
                            @foreach($planningModes as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6" id="editActeField">
                        <label class="form-label">Type d'acte</label>
                        <select name="appointment_type_id" id="edit_acte" class="form-select">
                            <option value="">— Sélectionner —</option>
                            @foreach($appointmentTypes as $specialty => $types)
                                <optgroup label="{{ $specialty }}">
                                    @foreach($types as $at)
                                        <option value="{{ $at->id }}">{{ $at->name }} ({{ $at->duration_minutes }} min)</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Durée (min)</label>
                        <input type="number" name="consultation_minutes" id="edit_duration" class="form-control" min="5">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Max/jour</label>
                        <input type="number" name="max_patients_per_day" id="edit_max" class="form-control" placeholder="Illimité">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Actif</label>
                        <select name="is_active" id="edit_active" class="form-select">
                            <option value="1">Oui</option>
                            <option value="0">Non</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Mettre à jour</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('head')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modeSelect = document.getElementById('planningMode');
    const acteField = document.getElementById('acteField');
    function toggleActeField() {
        acteField.style.display = modeSelect.value === 'by_specialist' ? 'none' : 'block';
    }
    if (modeSelect) {
        modeSelect.addEventListener('change', toggleActeField);
        toggleActeField();
    }

    const editMode = document.getElementById('edit_mode');
    const editActeField = document.getElementById('editActeField');
    function toggleEditActeField() {
        editActeField.style.display = editMode.value === 'by_specialist' ? 'none' : 'block';
    }
    if (editMode) {
        editMode.addEventListener('change', toggleEditActeField);
        toggleEditActeField();
    }

    // Pre-fill edit modal
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const mode = this.dataset.mode;
            document.getElementById('edit_start').value = this.dataset.start;
            document.getElementById('edit_end').value = this.dataset.end;
            document.getElementById('edit_mode').value = mode;
            document.getElementById('edit_acte').value = this.dataset.acte || '';
            document.getElementById('edit_duration').value = this.dataset.duration;
            document.getElementById('edit_max').value = this.dataset.max || '';
            document.getElementById('edit_active').value = this.dataset.active;
            document.getElementById('editForm').action = '{{ url('admin/planning-settings/planning') }}/' + this.dataset.id;
            toggleEditActeField();
        });
    });
});
</script>
@endpush
