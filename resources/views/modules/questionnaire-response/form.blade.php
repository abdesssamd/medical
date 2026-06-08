@extends('layouts.admin')

@section('title', 'Questionnaire - ' . $questionnaire->name)
@section('page_title', 'Remplir le Questionnaire')
@section('page_pretitle', $questionnaire->name)

@push('styles')
<style>
    .questionnaire-form-wrapper {
        max-width: 600px;
        margin: 0 auto;
    }

    .form-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
    }

    .questionnaire-header {
        margin-bottom: 32px;
        padding-bottom: 24px;
        border-bottom: 1px solid #e2e8f0;
    }

    .questionnaire-title {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 8px;
    }

    .questionnaire-description {
        font-size: 14px;
        color: #64748b;
        margin: 0;
    }

    .patient-context {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 24px;
        border: 1px solid #bfdbfe;
    }

    .patient-context-label {
        font-size: 12px;
        font-weight: 600;
        color: #1e40af;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .patient-context-value {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        font-size: 14px;
        color: #0f172a;
        margin-bottom: 8px;
    }

    .form-group label .required {
        color: #ef4444;
    }

    .form-group .help-text {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 6px;
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

    .radio-group,
    .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .radio-item,
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .radio-item:hover,
    .checkbox-item:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .radio-item input[type="radio"],
    .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #3b82f6;
    }

    .radio-label,
    .checkbox-label {
        flex: 1;
        font-size: 14px;
        color: #475569;
        cursor: pointer;
        margin: 0;
    }

    .yesno-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .yesno-button {
        padding: 12px;
        border: 2px solid #e2e8f0;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
        text-align: center;
    }

    .yesno-button:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .yesno-button.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .button-group {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .btn-modern {
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

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.4);
    }

    .btn-secondary {
        background: white;
        color: #0f172a;
        border: 1px solid #e2e8f0;
    }

    .btn-secondary:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .progress-section {
        margin-bottom: 24px;
    }

    .progress-bar {
        width: 100%;
        height: 4px;
        background: #e2e8f0;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 8px;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #2563eb);
        width: 0%;
        transition: width 0.3s ease;
    }

    .progress-text {
        font-size: 12px;
        color: #94a3b8;
        text-align: right;
    }

    @media (max-width: 768px) {
        .form-card {
            padding: 20px;
        }

        .yesno-group {
            grid-template-columns: 1fr;
        }

        .button-group {
            flex-direction: column;
        }

        .btn-modern {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

<div class="page-body-modern">
    <div class="questionnaire-form-wrapper">
        <!-- Alert Messages -->
        @if ($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 12px; padding: 16px; margin-bottom: 20px; color: #991b1b;">
                <strong>Erreurs:</strong>
                <ul style="margin: 8px 0 0 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div style="background: #dcfce7; border: 1px solid #86efac; border-radius: 12px; padding: 16px; margin-bottom: 20px; color: #166534;">
                {{ session('success') }}
            </div>
        @endif

        <!-- Form Card -->
        <div class="form-card">
            <!-- Header -->
            <div class="questionnaire-header">
                <h1 class="questionnaire-title">{{ $questionnaire->name }}</h1>
                @if ($questionnaire->description)
                    <p class="questionnaire-description">{{ $questionnaire->description }}</p>
                @endif
            </div>

            <!-- Patient Context -->
            <div class="patient-context">
                <div class="patient-context-label">Patient</div>
                <div class="patient-context-value">{{ $patient->full_name }}</div>
                <div style="font-size: 13px; color: #475569; margin-top: 6px;">
                    MRN {{ $patient->medical_record_number }} • {{ $patient->age }} ans
                </div>
            </div>

            <!-- Progress -->
            <div class="progress-section">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-text">
                    <span id="currentField">0</span> / <span id="totalFields">{{ count($questionnaire->field_schema ?? []) }}</span>
                </div>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('questionnaire-response.store', [$patient->id, $questionnaire->id]) }}" id="questionnaireForm">
                @csrf

                <input type="hidden" name="questionnaire_id" value="{{ $questionnaire->id }}">

                @foreach ($questionnaire->field_schema ?? [] as $index => $field)
                    @php
                        $fieldId = $field['id'] ?? "field_{$index}";
                        $required = $field['required'] ?? false;
                    @endphp

                    <div class="form-group" data-field-index="{{ $index }}">
                        <!-- Text Input -->
                        @if ($field['type'] === 'text')
                            <label for="{{ $fieldId }}">
                                {{ $field['label'] }}
                                @if ($required)
                                    <span class="required">*</span>
                                @endif
                            </label>
                            <input type="text" id="{{ $fieldId }}" name="answers[{{ $fieldId }}]"
                                   placeholder="{{ $field['placeholder'] ?? '' }}"
                                   {{ $required ? 'required' : '' }} class="form-field">
                            @if ($field['helpText'] ?? null)
                                <div class="help-text">{{ $field['helpText'] }}</div>
                            @endif

                        <!-- Email Input -->
                        @elseif ($field['type'] === 'email')
                            <label for="{{ $fieldId }}">
                                {{ $field['label'] }}
                                @if ($required)
                                    <span class="required">*</span>
                                @endif
                            </label>
                            <input type="email" id="{{ $fieldId }}" name="answers[{{ $fieldId }}]"
                                   placeholder="{{ $field['placeholder'] ?? '' }}"
                                   {{ $required ? 'required' : '' }} class="form-field">
                            @if ($field['helpText'] ?? null)
                                <div class="help-text">{{ $field['helpText'] }}</div>
                            @endif

                        <!-- Number Input -->
                        @elseif ($field['type'] === 'number')
                            <label for="{{ $fieldId }}">
                                {{ $field['label'] }}
                                @if ($required)
                                    <span class="required">*</span>
                                @endif
                            </label>
                            <input type="number" id="{{ $fieldId }}" name="answers[{{ $fieldId }}]"
                                   placeholder="{{ $field['placeholder'] ?? '' }}"
                                   {{ $required ? 'required' : '' }} class="form-field">
                            @if ($field['helpText'] ?? null)
                                <div class="help-text">{{ $field['helpText'] }}</div>
                            @endif

                        <!-- Date Input -->
                        @elseif ($field['type'] === 'date')
                            <label for="{{ $fieldId }}">
                                {{ $field['label'] }}
                                @if ($required)
                                    <span class="required">*</span>
                                @endif
                            </label>
                            <input type="date" id="{{ $fieldId }}" name="answers[{{ $fieldId }}]"
                                   {{ $required ? 'required' : '' }} class="form-field">
                            @if ($field['helpText'] ?? null)
                                <div class="help-text">{{ $field['helpText'] }}</div>
                            @endif

                        <!-- Textarea -->
                        @elseif ($field['type'] === 'textarea')
                            <label for="{{ $fieldId }}">
                                {{ $field['label'] }}
                                @if ($required)
                                    <span class="required">*</span>
                                @endif
                            </label>
                            <textarea id="{{ $fieldId }}" name="answers[{{ $fieldId }}]"
                                      placeholder="{{ $field['placeholder'] ?? '' }}"
                                      {{ $required ? 'required' : '' }} class="form-field"></textarea>
                            @if ($field['helpText'] ?? null)
                                <div class="help-text">{{ $field['helpText'] }}</div>
                            @endif

                        <!-- Select Dropdown -->
                        @elseif ($field['type'] === 'select')
                            <label for="{{ $fieldId }}">
                                {{ $field['label'] }}
                                @if ($required)
                                    <span class="required">*</span>
                                @endif
                            </label>
                            <select id="{{ $fieldId }}" name="answers[{{ $fieldId }}]"
                                    {{ $required ? 'required' : '' }} class="form-field">
                                <option value="">{{ $field['placeholder'] ?? '-- Sélectionnez une option --' }}</option>
                                @foreach ($field['options'] ?? [] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                            @if ($field['helpText'] ?? null)
                                <div class="help-text">{{ $field['helpText'] }}</div>
                            @endif

                        <!-- Radio Buttons -->
                        @elseif ($field['type'] === 'radio')
                            <label>
                                {{ $field['label'] }}
                                @if ($required)
                                    <span class="required">*</span>
                                @endif
                            </label>
                            <div class="radio-group">
                                @foreach ($field['options'] ?? [] as $option)
                                    <label class="radio-item">
                                        <input type="radio" name="answers[{{ $fieldId }}]" value="{{ $option }}"
                                               {{ $required ? 'required' : '' }} class="form-field">
                                        <span class="radio-label">{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if ($field['helpText'] ?? null)
                                <div class="help-text">{{ $field['helpText'] }}</div>
                            @endif

                        <!-- Checkboxes -->
                        @elseif ($field['type'] === 'checkbox')
                            <label>
                                {{ $field['label'] }}
                                @if ($required)
                                    <span class="required">*</span>
                                @endif
                            </label>
                            <div class="checkbox-group">
                                @foreach ($field['options'] ?? [] as $option)
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="answers[{{ $fieldId }}][]" value="{{ $option }}" class="form-field">
                                        <span class="checkbox-label">{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if ($field['helpText'] ?? null)
                                <div class="help-text">{{ $field['helpText'] }}</div>
                            @endif

                        <!-- Yes/No -->
                        @elseif ($field['type'] === 'yesno')
                            <label>
                                {{ $field['label'] }}
                                @if ($required)
                                    <span class="required">*</span>
                                @endif
                            </label>
                            <div class="yesno-group">
                                <button type="button" class="yesno-button" data-value="oui" onclick="selectYesNo(this, '{{ $fieldId }}')">Oui</button>
                                <button type="button" class="yesno-button" data-value="non" onclick="selectYesNo(this, '{{ $fieldId }}')">Non</button>
                            </div>
                            <input type="hidden" id="{{ $fieldId }}" name="answers[{{ $fieldId }}]" {{ $required ? 'required' : '' }}>
                            @if ($field['helpText'] ?? null)
                                <div class="help-text">{{ $field['helpText'] }}</div>
                            @endif
                        @endif
                    </div>
                @endforeach

                <!-- Action Buttons -->
                <div class="button-group">
                    <a href="javascript:history.back()" class="btn-modern btn-secondary">
                        <i class="ti ti-arrow-left"></i>
                        Annuler
                    </a>
                    <button type="submit" class="btn-modern btn-primary">
                        <i class="ti ti-check"></i>
                        Soumettre le Questionnaire
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function selectYesNo(button, fieldId) {
        const group = button.parentElement;
        group.querySelectorAll('.yesno-button').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        document.getElementById(fieldId).value = button.dataset.value;
    }

    function updateProgress() {
        const form = document.getElementById('questionnaireForm');
        const fields = form.querySelectorAll('.form-field, input[name*="answers"]');
        const totalFields = form.querySelectorAll('[data-field-index]').length;
        
        let filledCount = 0;
        fields.forEach(field => {
            if (field.value && field.value.trim()) {
                filledCount++;
            }
        });

        const percentage = Math.round((filledCount / totalFields) * 100);
        document.getElementById('progressFill').style.width = percentage + '%';
        document.getElementById('currentField').textContent = filledCount;
        document.getElementById('totalFields').textContent = totalFields;
    }

    // Update progress on input change
    document.getElementById('questionnaireForm')?.addEventListener('change', updateProgress);
    document.getElementById('questionnaireForm')?.addEventListener('input', updateProgress);

    // Initialize progress
    updateProgress();

    // Form submission validation
    document.getElementById('questionnaireForm')?.addEventListener('submit', function(e) {
        const form = this;
        let allValid = true;
        
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value || field.value.trim() === '') {
                allValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!allValid) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires.');
        }
    });
</script>
@endpush
