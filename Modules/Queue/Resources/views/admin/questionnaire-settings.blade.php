@extends('layouts.admin')

@section('title', 'Parametres Questionnaires')

@section('content')
<div class="settings-shell">
    <section class="card settings-hero">
        <div>
            <h1 class="page-title">Templates Questionnaires Dynamiques</h1>
            <p class="text-secondary mb-0">Configuration des templates JSON decouplee du dossier patient.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('admin.settings') }}">Retour parametres</a>
    </section>

    <section class="card">
        <div class="questionnaire-admin-layout">
            <div class="questionnaire-admin-builder">
                <div class="fw-semibold mb-2">Constructeur de template JSON</div>
                <form method="POST" action="{{ route('admin.settings.questionnaires.store') }}" id="questionnaireTemplateForm" class="d-grid gap-3">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Nom du modele</label><input class="form-control" name="name" required placeholder="Formulaire de Grippe"></div>
                        <div class="col-md-6"><label class="form-label">Specialite</label><select class="form-select" name="specialty_id"><option value="">Toutes</option>@foreach($specialtiesList as $spec)<option value="{{ $spec->id }}">{{ $spec->name }} ({{ $spec->code }})</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">Medecin</label><select class="form-select" name="practitioner_id"><option value="">Tous</option>@foreach($practitioners as $practitioner)<option value="{{ $practitioner->id }}">{{ $practitioner->name }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">Groupe de medecins</label><input class="form-control" name="group_name" placeholder="Clinique Nord / Chirurgie"></div>
                        <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <strong>Champs</strong>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addQuestionnaireField">Ajouter un champ</button>
                        </div>
                        <div id="questionnaireBuilder" class="questionnaire-builder"></div>
                        <textarea class="d-none" name="field_schema_json" id="questionnaireFieldSchema"></textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <label class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><span class="form-check-label">Actif</span></label>
                        <button class="btn btn-success" type="submit">Enregistrer le template</button>
                    </div>
                </form>
            </div>

            <div class="questionnaire-admin-templates">
                <div class="fw-semibold mb-2">Templates disponibles</div>
                <div class="d-grid gap-2">
                    @forelse($questionnaires as $template)
                        <article class="questionnaire-template-item">
                            <div class="d-flex justify-content-between gap-2 align-items-start">
                                <div>
                                    <div class="fw-semibold">{{ $template->name }}</div>
                                    <div class="small text-secondary">{{ $template->description ?: 'Sans description' }}</div>
                                    <div class="small text-secondary">{{ $template->specialty?->name ?: 'Tous' }} | {{ $template->practitioner?->name ?: 'Tous medecins' }} | Groupe: {{ $template->group_name ?: '-' }}</div>
                                </div>
                                <span class="badge {{ $template->is_active ? 'bg-green-lt' : 'bg-secondary-lt' }}">{{ $template->is_active ? 'Actif' : 'Inactif' }}</span>
                            </div>
                            <div class="small mt-2 text-secondary">{{ count($template->field_schema ?? []) }} champs</div>
                        </article>
                    @empty
                        <div class="text-secondary">Aucun template configure.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('head')
<style>
.settings-shell{display:grid;gap:14px}
.settings-hero{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:18px}
.questionnaire-admin-layout{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:14px}
.questionnaire-admin-builder,.questionnaire-admin-templates{padding:12px;border:1px solid #e2e8f0;border-radius:12px;background:#fff}
.questionnaire-builder{display:grid;gap:10px}
.questionnaire-field{border:1px solid #dbeafe;border-radius:12px;background:#f8fbff;padding:10px}
.questionnaire-field-grid{display:grid;grid-template-columns:1.3fr 1fr 1fr auto;gap:8px;align-items:end}
.questionnaire-field-options{display:grid;gap:6px;margin-top:8px}
.questionnaire-template-item{padding:10px;border-radius:12px;border:1px solid #e2e8f0;background:#fff}
@media (max-width: 1100px){.questionnaire-admin-layout{grid-template-columns:1fr}.questionnaire-field-grid{grid-template-columns:1fr}}
</style>
@endpush

@push('scripts')
@include('modules.partials.questionnaire-engine')
<script>
(() => {
    const questionnaireBuilder = document.getElementById('questionnaireBuilder');
    const questionnaireSchema = document.getElementById('questionnaireFieldSchema');
    const addFieldBtn = document.getElementById('addQuestionnaireField');
    const templateForm = document.getElementById('questionnaireTemplateForm');

    if (!questionnaireBuilder || !questionnaireSchema || !window.QuestionnaireEngine) return;

    const initialSchema = @json(old('field_schema_json') ? json_decode((string) old('field_schema_json'), true) : [['key' => '', 'label' => '', 'type' => 'text', 'required' => false]]);
    const builder = window.QuestionnaireEngine.createTemplateBuilder({
        container: questionnaireBuilder,
        schemaInput: questionnaireSchema,
        addButton: addFieldBtn,
        initialFields: Array.isArray(initialSchema) && initialSchema.length ? initialSchema : [{ key: '', label: '', type: 'text', required: false }],
    });

    templateForm?.addEventListener('submit', () => builder.sync());
})();
</script>
@endpush
