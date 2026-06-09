<script>
(() => {
    if (window.QuestionnaireEngine) return;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function createFieldKey(label, index) {
        const key = String(label || 'field')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
        return key || `field_${index + 1}`;
    }

    function normalizeField(field, index) {
        const type = ['text', 'date', 'number', 'select', 'checkbox', 'textarea', 'radio', 'yesno', 'email'].includes(field?.type)
            ? field.type
            : 'text';
        return {
            key: field?.key || field?.id || createFieldKey(field?.label, index),
            label: field?.label || `Champ ${index + 1}`,
            type,
            required: !!field?.required,
            ...(['select', 'radio', 'checkbox'].includes(type) ? { options: Array.isArray(field?.options) ? field.options.filter(Boolean) : [] } : {}),
            helpText: field?.helpText || '',
        };
    }

    function renderDynamicForm(container, fields, answersPrefix = 'answers') {
        if (!container) return [];
        const normalized = Array.isArray(fields) ? fields.map((field, index) => normalizeField(field, index)) : [];

        if (!normalized.length) {
            container.innerHTML = '<div class="text-secondary">Aucun champ configure pour ce template.</div>';
            return normalized;
        }

        container.innerHTML = normalized.map((field, index) => {
            const key = field.key || `field_${index + 1}`;
            const label = `${escapeHtml(field.label || key)}${field.required ? ' *' : ''}`;
            const required = field.required ? 'required' : '';
            const name = `${answersPrefix}[${escapeHtml(key)}]`;
            const help = field.helpText ? `<div class="questionnaire-help">${escapeHtml(field.helpText)}</div>` : '';

            if (field.type === 'textarea') {
                return `<div class="questionnaire-dynamic-field"><div class="field-label"><span>${label}</span></div><textarea class="form-control" name="${name}" ${required}></textarea>${help}</div>`;
            }
            if (field.type === 'select') {
                const options = (field.options || []).map((option) => {
                    const safe = escapeHtml(option);
                    return `<option value="${safe}">${safe}</option>`;
                }).join('');
                return `<div class="questionnaire-dynamic-field"><div class="field-label"><span>${label}</span></div><select class="form-select" name="${name}" ${required}><option value="">-- Choisir --</option>${options}</select>${help}</div>`;
            }
            if (field.type === 'checkbox') {
                const options = (field.options || []).map((option, optionIndex) => {
                    const safe = escapeHtml(option);
                    const optionId = `${escapeHtml(key)}_${optionIndex}`;
                    return `<label class="form-check questionnaire-choice"><input class="form-check-input" type="checkbox" name="${name}[]" value="${safe}" id="${optionId}"><span class="form-check-label" for="${optionId}">${safe}</span></label>`;
                }).join('');
                const checkboxBody = options || `<label class="form-check questionnaire-choice"><input class="form-check-input" type="checkbox" name="${name}" value="1"><span class="form-check-label">${label}</span></label>`;
                return `<div class="questionnaire-dynamic-field"><div class="field-label"><span>${label}</span></div><div class="questionnaire-choice-group">${checkboxBody}</div>${help}</div>`;
            }
            if (field.type === 'number') {
                return `<div class="questionnaire-dynamic-field"><div class="field-label"><span>${label}</span></div><input class="form-control" type="number" name="${name}" step="1" ${required}>${help}</div>`;
            }
            if (field.type === 'date') {
                return `<div class="questionnaire-dynamic-field"><div class="field-label"><span>${label}</span></div><input class="form-control" type="date" name="${name}" ${required}>${help}</div>`;
            }
            if (field.type === 'email') {
                return `<div class="questionnaire-dynamic-field"><div class="field-label"><span>${label}</span></div><input class="form-control" type="email" name="${name}" ${required}>${help}</div>`;
            }
            if (field.type === 'radio') {
                const radios = (field.options || []).map((option, optionIndex) => {
                    const safe = escapeHtml(option);
                    const optionId = `${escapeHtml(key)}_${optionIndex}`;
                    return `<label class="form-check questionnaire-choice"><input class="form-check-input" type="radio" name="${name}" value="${safe}" id="${optionId}" ${required}><span class="form-check-label" for="${optionId}">${safe}</span></label>`;
                }).join('');
                return `<div class="questionnaire-dynamic-field"><div class="field-label"><span>${label}</span></div><div class="questionnaire-choice-group">${radios}</div>${help}</div>`;
            }
            if (field.type === 'yesno') {
                return `<div class="questionnaire-dynamic-field"><div class="field-label"><span>${label}</span></div><div class="questionnaire-choice-group questionnaire-yesno-group"><label class="form-check questionnaire-choice"><input class="form-check-input" type="radio" name="${name}" value="oui" ${required}><span class="form-check-label">Oui</span></label><label class="form-check questionnaire-choice"><input class="form-check-input" type="radio" name="${name}" value="non" ${required}><span class="form-check-label">Non</span></label></div>${help}</div>`;
            }
            return `<div class="questionnaire-dynamic-field"><div class="field-label"><span>${label}</span></div><input class="form-control" type="text" name="${name}" ${required}>${help}</div>`;
        }).join('');

        return normalized;
    }

    function createTemplateBuilder(config) {
        const container = config?.container;
        const schemaInput = config?.schemaInput;
        const addButton = config?.addButton;
        let fields = Array.isArray(config?.initialFields) && config.initialFields.length
            ? config.initialFields.map((field, index) => normalizeField(field, index))
            : [{ key: '', label: '', type: 'text', required: false }];

        function sync() {
            fields = fields.map((field, index) => normalizeField(field, index));
            if (schemaInput) {
                schemaInput.value = JSON.stringify(fields, null, 2);
            }
            return fields;
        }

        function render() {
            if (!container) return;
            container.innerHTML = '';

            fields.forEach((field, index) => {
                const node = document.createElement('div');
                node.className = 'questionnaire-field';
                node.innerHTML = `
                    <div class="questionnaire-field-grid">
                        <div>
                            <label class="form-label">Libelle</label>
                            <input class="form-control" data-role="label" value="${escapeHtml(field.label || '')}" placeholder="Ex: Date d apparition">
                        </div>
                        <div>
                            <label class="form-label">Key</label>
                            <input class="form-control" data-role="key" value="${escapeHtml(field.key || '')}" placeholder="symptom_onset_date">
                        </div>
                        <div>
                            <label class="form-label">Type</label>
                            <select class="form-select" data-role="type">
                                ${['text', 'date', 'number', 'select', 'checkbox', 'textarea'].map((type) => `<option value="${type}" ${field.type === type ? 'selected' : ''}>${type}</option>`).join('')}
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <label class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" data-role="required" ${field.required ? 'checked' : ''}>
                                <span class="form-check-label">Requis</span>
                            </label>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-role="remove">Supprimer</button>
                        </div>
                    </div>
                    <div class="questionnaire-field-options ${field.type === 'select' ? '' : 'd-none'}" data-role="options-wrap">
                        <label class="form-label mb-0">Options (1 par ligne)</label>
                        <textarea class="form-control" rows="3" data-role="options">${escapeHtml(Array.isArray(field.options) ? field.options.join('\n') : '')}</textarea>
                    </div>
                `;

                node.querySelector('[data-role="label"]')?.addEventListener('input', (event) => {
                    fields[index].label = event.target.value;
                    if (!fields[index].key) {
                        fields[index].key = createFieldKey(fields[index].label, index);
                    }
                    render();
                });
                node.querySelector('[data-role="key"]')?.addEventListener('input', (event) => {
                    fields[index].key = event.target.value;
                    sync();
                });
                node.querySelector('[data-role="type"]')?.addEventListener('change', (event) => {
                    fields[index].type = event.target.value;
                    if (fields[index].type !== 'select') {
                        delete fields[index].options;
                    } else if (!Array.isArray(fields[index].options)) {
                        fields[index].options = ['Option 1', 'Option 2'];
                    }
                    render();
                });
                node.querySelector('[data-role="required"]')?.addEventListener('change', (event) => {
                    fields[index].required = event.target.checked;
                    sync();
                });
                node.querySelector('[data-role="options"]')?.addEventListener('input', (event) => {
                    fields[index].options = event.target.value.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
                    sync();
                });
                node.querySelector('[data-role="remove"]')?.addEventListener('click', () => {
                    fields.splice(index, 1);
                    if (!fields.length) {
                        fields.push({ key: '', label: '', type: 'text', required: false });
                    }
                    render();
                });

                container.appendChild(node);
            });

            sync();
        }

        addButton?.addEventListener('click', () => {
            fields.push({ key: '', label: '', type: 'text', required: false });
            render();
        });

        render();

        return {
            sync,
            getFields: () => fields.map((field, index) => normalizeField(field, index)),
            setFields: (nextFields) => {
                fields = Array.isArray(nextFields) && nextFields.length
                    ? nextFields.map((field, index) => normalizeField(field, index))
                    : [{ key: '', label: '', type: 'text', required: false }];
                render();
            },
        };
    }

    window.QuestionnaireEngine = {
        renderDynamicForm,
        createTemplateBuilder,
    };
})();
</script>
<?php /**PATH D:\xampp8.2\htdocs\fils_attente\resources\views/modules/partials/questionnaire-engine.blade.php ENDPATH**/ ?>