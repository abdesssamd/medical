@extends('layouts.admin')

@section('title', 'Créer un Questionnaire')
@section('page_title', 'Créer un Modèle de Questionnaire')
@section('page_pretitle', 'Configuration Clinique')

@push('styles')
<style>
    .form-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .form-section {
        background: white;
        border-radius: 16px;
        padding: 28px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .section-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .section-title i {
        color: #3b82f6;
        font-size: 24px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    label {
        display: block;
        font-weight: 600;
        font-size: 14px;
        color: #0f172a;
        margin-bottom: 8px;
    }

    label .required {
        color: #ef4444;
    }

    input[type="text"],
    input[type="email"],
    input[type="number"],
    input[type="date"],
    textarea,
    select {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        transition: all 0.2s;
        background: white;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="number"]:focus,
    input[type="date"]:focus,
    textarea:focus,
    select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    textarea {
        resize: vertical;
        min-height: 100px;
    }

    .help-text {
        font-size: 13px;
        color: #64748b;
        margin-top: 6px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    /* Form Builder */
    .form-builder {
        background: #f8fafc;
        border-radius: 12px;
        padding: 20px;
        border: 2px dashed #cbd5e1;
    }

    .builder-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .builder-header h3 {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .fields-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .field-item {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
        transition: all 0.2s;
    }

    .field-item:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .field-item.sortable-ghost {
        opacity: 0.5;
        background: #f1f5f9;
    }

    .field-drag-handle {
        color: #94a3b8;
        cursor: grab;
        padding: 4px 8px;
        font-size: 18px;
        margin-top: 4px;
    }

    .field-drag-handle:active {
        cursor: grabbing;
    }

    .field-content {
        flex: 1;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        width: 100%;
    }

    .field-content > div {
        display: flex;
        flex-direction: column;
    }

    .field-content input,
    .field-content select,
    .field-content textarea {
        padding: 10px 12px;
        font-size: 13px;
    }

    .field-type-badge {
        display: inline-block;
        padding: 4px 10px;
        background: #dbeafe;
        color: #1e40af;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 4px;
    }

    .field-options {
        grid-column: 1 / -1;
    }

    .field-options-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 8px;
    }

    .option-input {
        display: flex;
        gap: 8px;
    }

    .option-input input {
        flex: 1;
        padding: 8px 12px;
        font-size: 13px;
    }

    .btn-remove-option {
        padding: 8px 12px;
        background: #fef2f2;
        border: 1px solid #fca5a5;
        color: #dc2626;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s;
    }

    .btn-remove-option:hover {
        background: #fee2e2;
    }

    .btn-add-option {
        padding: 8px 12px;
        background: #dbeafe;
        border: 1px solid #93c5fd;
        color: #1e40af;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.2s;
        margin-top: 8px;
    }

    .btn-add-option:hover {
        background: #bfdbfe;
    }

    .field-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-remove-field {
        padding: 8px 12px;
        background: #fef2f2;
        border: 1px solid #fca5a5;
        color: #dc2626;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
    }

    .btn-remove-field:hover {
        background: #fee2e2;
    }

    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 0;
    }

    .checkbox-wrapper input[type="checkbox"] {
        width: auto;
        margin: 0;
    }

    .checkbox-wrapper label {
        margin: 0;
        font-weight: 500;
    }

    .field-types-selector {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .field-type-btn {
        padding: 12px;
        border: 2px solid #e2e8f0;
        background: white;
        border-radius: 10px;
        cursor: pointer;
        text-align: center;
        transition: all 0.2s;
        font-size: 12px;
        font-weight: 500;
    }

    .field-type-btn:hover {
        border-color: #3b82f6;
        background: #eff6ff;
    }

    .field-type-btn i {
        display: block;
        font-size: 24px;
        margin-bottom: 4px;
        color: #3b82f6;
    }

    /* Buttons */
    .btn-primary-modern,
    .btn-secondary-modern,
    .btn-danger-modern {
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary-modern {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .btn-primary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.4);
    }

    .btn-secondary-modern {
        background: white;
        color: #0f172a;
        border: 1px solid #e2e8f0;
    }

    .btn-secondary-modern:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .btn-add-field {
        padding: 12px 20px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        margin-bottom: 20px;
    }

    .btn-add-field:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .button-group {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        justify-content: flex-end;
    }

    .no-fields-message {
        text-align: center;
        padding: 40px;
        color: #94a3b8;
        font-size: 14px;
    }

    .no-fields-message i {
        font-size: 48px;
        display: block;
        margin-bottom: 12px;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .field-content {
            grid-template-columns: 1fr;
        }

        .field-types-selector {
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        }

        .button-group {
            flex-direction: column;
        }

        .btn-primary-modern,
        .btn-secondary-modern {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

@section('content')
<div class="page-body-modern">
    <div class="form-container">
        <form method="POST" action="{{ route('clinical.questionnaire-templates.store') }}">
            @csrf

            <!-- Section 1: Basic Information -->
            <div class="form-section">
                <div class="section-title">
                    <i class="ti ti-info-circle"></i>
                    Informations Générales
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">
                            Nom du Modèle
                            <span class="required">*</span>
                        </label>
                        <input type="text" id="name" name="name" placeholder="Ex: Questionnaire Pré-Opératoire" 
                               value="{{ old('name') }}" required>
                        @error('name')
                            <div class="help-text" style="color: #ef4444;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="specialty_id">Spécialité</label>
                        <select id="specialty_id" name="specialty_id">
                            <option value="">-- Tous les patients --</option>
                            @foreach ($specialties as $specialty)
                                <option value="{{ $specialty->id }}" 
                                    {{ old('specialty_id') == $specialty->id ? 'selected' : '' }}>
                                    {{ $specialty->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="help-text">Laisser vide pour appliquer à tous les patients</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Description facultative du questionnaire...">{{ old('description') }}</textarea>
                    <div class="help-text">Décrivez l'objectif et l'utilisation de ce questionnaire</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="practitioner_id">Praticien Responsable</label>
                        <select id="practitioner_id" name="practitioner_id">
                            <option value="">-- Aucun --</option>
                            @foreach ($practitioners as $practitioner)
                                <option value="{{ $practitioner->id }}"
                                    {{ old('practitioner_id') == $practitioner->id ? 'selected' : '' }}>
                                    {{ $practitioner->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="group_name">Catégorie</label>
                        <input type="text" id="group_name" name="group_name" 
                               placeholder="Ex: Bilan de Santé" value="{{ old('group_name') }}">
                        <div class="help-text">Permet de grouper les questionnaires</div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Form Builder -->
            <div class="form-section">
                <div class="section-title">
                    <i class="ti ti-list-check"></i>
                    Constructeur de Formulaire
                </div>

                <div class="form-group">
                    <label>Types de Champs Disponibles</label>
                    <div class="field-types-selector">
                        @foreach ($fieldTypes as $type => $config)
                            <button type="button" class="field-type-btn" onclick="addField('{{ $type }}', '{{ $config['label'] }}')">
                                <i class="ti {{ $config['icon'] }}"></i>
                                {{ $config['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="form-builder">
                    <div class="builder-header">
                        <h3>Champs du Questionnaire</h3>
                        <span style="font-size: 13px; color: #94a3b8;" id="fieldCount">0 champ</span>
                    </div>

                    <div id="fieldsList" class="fields-list">
                        <div class="no-fields-message">
                            <i class="ti ti-plus"></i>
                            Ajoutez des champs en cliquant sur un type ci-dessus
                        </div>
                    </div>
                </div>

                <input type="hidden" id="fieldsInput" name="fields" value="[]">
            </div>

            <!-- Section 3: Options -->
            <div class="form-section">
                <div class="section-title">
                    <i class="ti ti-settings"></i>
                    Paramètres
                </div>

                <div class="form-group">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                        <label for="is_active">Activer immédiatement ce modèle</label>
                    </div>
                    <div class="help-text">Les modèles actifs sont disponibles pour les dossiers patients</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="button-group">
                <a href="{{ route('clinical.questionnaire-templates.index') }}" class="btn-secondary-modern">
                    <i class="ti ti-x"></i>
                    Annuler
                </a>
                <button type="submit" class="btn-primary-modern">
                    <i class="ti ti-check"></i>
                    Créer le Modèle
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    let fieldCounter = 0;
    const fieldTypes = @json($fieldTypes);

    function addField(type, label) {
        const fieldsList = document.getElementById('fieldsList');
        
        // Remove "no fields" message if present
        const noFieldsMsg = fieldsList.querySelector('.no-fields-message');
        if (noFieldsMsg) noFieldsMsg.remove();

        const fieldId = `field_${++fieldCounter}`;
        const fieldHtml = createFieldHTML(fieldId, type, label);
        
        const fieldItem = document.createElement('div');
        fieldItem.innerHTML = fieldHtml;
        fieldItem.classList.add('field-item');
        fieldItem.dataset.fieldId = fieldId;
        fieldItem.dataset.type = type;

        fieldsList.appendChild(fieldItem);
        setupFieldEventListeners(fieldItem);
        updateFieldsInput();
        updateFieldCount();
    }

    function createFieldHTML(fieldId, type, typeLabel) {
        const isSelectType = ['select', 'radio', 'checkbox'].includes(type);
        
        return `
            <div class="field-drag-handle" draggable="true" title="Glisser pour réordonner">
                <i class="ti ti-grip-vertical"></i>
            </div>
            <div class="field-content">
                <div>
                    <label style="margin-bottom: 4px; font-size: 12px;">Label <span class="required">*</span></label>
                    <input type="text" class="field-label" placeholder="Ex: Avez-vous des allergies ?" value="">
                </div>
                <div>
                    <label style="margin-bottom: 4px; font-size: 12px;">Placeholder</label>
                    <input type="text" class="field-placeholder" placeholder="Texte d'aide" value="">
                </div>
                ${type === 'yesno' ? '' : `
                <div>
                    <label style="margin-bottom: 4px; font-size: 12px;">Texte d'aide</label>
                    <input type="text" class="field-helpText" placeholder="Ex: Description supplémentaire" value="">
                </div>
                `}
                ${isSelectType ? `
                <div class="field-options">
                    <label style="font-size: 12px;">Options ${type === 'select' || type === 'radio' ? '(une par ligne)' : ''}</label>
                    <div class="field-options-list">
                        <div class="option-input">
                            <input type="text" class="field-option" placeholder="Option 1">
                            <button type="button" class="btn-remove-option" onclick="this.parentElement.remove(); updateFieldsInput();">Retirer</button>
                        </div>
                    </div>
                    <button type="button" class="btn-add-option" onclick="addOptionToField(this.parentElement);">+ Ajouter option</button>
                </div>
                ` : ''}
                <div style="grid-column: 1 / -1; display: flex; gap: 12px; align-items: center;">
                    <span class="field-type-badge">${typeLabel}</span>
                    <div class="checkbox-wrapper" style="margin-bottom: 0;">
                        <input type="checkbox" class="field-required" id="required_${fieldId}">
                        <label for="required_${fieldId}" style="margin: 0; font-weight: 500; font-size: 12px;">Obligatoire</label>
                    </div>
                    <button type="button" class="btn-remove-field" onclick="removeField(this);">
                        <i class="ti ti-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        `;
    }

    function setupFieldEventListeners(fieldItem) {
        fieldItem.addEventListener('change', updateFieldsInput);
        fieldItem.addEventListener('input', updateFieldsInput);
    }

    function addOptionToField(optionsContainer) {
        const optionsList = optionsContainer.querySelector('.field-options-list');
        const optionCount = optionsList.children.length + 1;
        
        const optionInput = document.createElement('div');
        optionInput.classList.add('option-input');
        optionInput.innerHTML = `
            <input type="text" class="field-option" placeholder="Option ${optionCount}">
            <button type="button" class="btn-remove-option" onclick="this.parentElement.remove(); updateFieldsInput();">Retirer</button>
        `;
        
        optionsList.appendChild(optionInput);
    }

    function removeField(btn) {
        const fieldItem = btn.closest('.field-item');
        fieldItem.remove();
        updateFieldsInput();
        updateFieldCount();

        const fieldsList = document.getElementById('fieldsList');
        if (fieldsList.children.length === 0) {
            fieldsList.innerHTML = `
                <div class="no-fields-message">
                    <i class="ti ti-plus"></i>
                    Ajoutez des champs en cliquant sur un type ci-dessus
                </div>
            `;
        }
    }

    function updateFieldsInput() {
        const fieldsList = document.getElementById('fieldsList');
        const fields = [];

        fieldsList.querySelectorAll('.field-item').forEach((item, index) => {
            const label = item.querySelector('.field-label').value;
            const type = item.dataset.type;
            const placeholder = item.querySelector('.field-placeholder').value;
            const required = item.querySelector('.field-required').checked;
            const helpText = item.querySelector('.field-helpText')?.value || '';
            
            const field = {
                label,
                type,
                placeholder,
                required,
                helpText,
                options: []
            };

            const optionInputs = item.querySelectorAll('.field-option');
            if (optionInputs.length > 0) {
                field.options = Array.from(optionInputs).map(input => input.value).filter(v => v.trim());
            }

            fields.push(field);
        });

        document.getElementById('fieldsInput').value = JSON.stringify(fields);
    }

    function updateFieldCount() {
        const fieldsList = document.getElementById('fieldsList');
        const count = fieldsList.querySelectorAll('.field-item').length;
        const countEl = document.getElementById('fieldCount');
        countEl.textContent = count + (count > 1 ? ' champs' : ' champ');
    }

    // Setup Sortable.js for drag and drop
    const fieldsList = document.getElementById('fieldsList');
    new Sortable(fieldsList, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        handle: '.field-drag-handle',
        onEnd: updateFieldsInput
    });

    // Prevent form submission if no fields
    document.querySelector('form').addEventListener('submit', function(e) {
        const fields = JSON.parse(document.getElementById('fieldsInput').value);
        if (fields.length === 0) {
            e.preventDefault();
            alert('Veuillez ajouter au moins un champ au formulaire.');
        }
    });
</script>
@endpush
