// Inline Edit System for Medical History (Personal & Family Antecedents)
(() => {
    const SUGGESTIONS = {
        personal: ['Diabète', 'Hypertension', 'Asthme', 'BPCO', 'Tabagisme', 'Obésité', 'Allergies', 'Cardiopathie', 'Arthrite', 'Thyroïde'],
        family: ['Diabète familial', 'Cardiopathie familiale', 'Cancer familial', 'AVC familial', 'Hypertension familiale', 'Asthme familial', 'Alzheimer familial']
    };
    
    const patientId = document.getElementById('module3-patient-context')?.dataset.patientId;
    let toastZone = document.querySelector('.clinical-toast-zone');
    
    function createToastZone() {
        const zone = document.createElement('div');
        zone.className = 'clinical-toast-zone';
        document.body.appendChild(zone);
        return zone;
    }
    
    if (!toastZone) toastZone = createToastZone();
    
    function showToast(message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `clinical-toast ${type}`;
        toast.innerHTML = message;
        toastZone.appendChild(toast);
        
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 200);
        }, duration);
    }
    
    // Initialize inline edit for history sections
    document.addEventListener('DOMContentLoaded', () => {
        ['personal', 'family'].forEach(type => {
            initializeHistorySection(type);
        });
    });
    
    function initializeHistorySection(type) {
        const card = document.querySelector(`[data-history-type="${type}"]`);
        if (!card) return;
        
        const addBtn = card.querySelector('.inline-add-btn');
        const editForm = card.querySelector('.inline-edit-form');
        const input = editForm?.querySelector('.inline-input');
        const placeholder = card.querySelector('.inline-placeholder');
        const itemsList = card.querySelector('.history-items-list');
        
        // Click to add button
        if (addBtn) {
            addBtn.addEventListener('click', (e) => {
                e.preventDefault();
                showEditForm(card);
                input?.focus();
            });
        }
        
        // Click on placeholder link
        if (placeholder) {
            const link = placeholder.querySelector('a');
            if (link) {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    showEditForm(card);
                    input?.focus();
                });
            }
        }
        
        // Input handling
        if (input) {
            let suggestionsTimeout;
            
            input.addEventListener('input', (e) => {
                clearTimeout(suggestionsTimeout);
                const value = e.target.value.trim().toLowerCase();
                
                if (value.length >= 2) {
                    suggestionsTimeout = setTimeout(() => {
                        showSuggestions(type, value, card);
                    }, 200);
                } else {
                    hideSuggestions(card);
                }
            });
            
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addHistoryItem(type, input.value.trim(), card);
                } else if (e.key === 'Escape') {
                    hideEditForm(card);
                    input.value = '';
                }
            });
            
            input.addEventListener('blur', () => {
                setTimeout(() => {
                    if (!editForm.classList.contains('d-none') && !input.value.trim()) {
                        hideEditForm(card);
                    }
                }, 100);
            });
        }
        
        // Delete buttons for existing items
        if (itemsList) {
            itemsList.querySelectorAll('.btn-close-inline').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const item = btn.dataset.item;
                    deleteHistoryItem(type, item, card);
                });
            });
        }
    }
    
    function showEditForm(card) {
        const form = card.querySelector('.inline-edit-form');
        const placeholder = card.querySelector('.inline-placeholder');
        
        if (form) {
            form.classList.remove('d-none');
            form.querySelector('.inline-input').focus();
        }
        if (placeholder) {
            placeholder.classList.add('d-none');
        }
    }
    
    function hideEditForm(card) {
        const form = card.querySelector('.inline-edit-form');
        const placeholder = card.querySelector('.inline-placeholder');
        const input = form?.querySelector('.inline-input');
        
        if (form) {
            form.classList.add('d-none');
            if (input) input.value = '';
            hideSuggestions(card);
        }
        
        const itemsList = card.querySelector('.history-items-list');
        if (placeholder && (!itemsList || itemsList.children.length === 0)) {
            placeholder.classList.remove('d-none');
        }
    }
    
    function showSuggestions(type, value, card) {
        const form = card.querySelector('.inline-edit-form');
        let suggestionsList = form?.querySelector('.suggestions-list');
        
        if (!suggestionsList) {
            suggestionsList = document.createElement('div');
            suggestionsList.className = 'suggestions-list';
            form?.appendChild(suggestionsList);
        }
        
        const filtered = SUGGESTIONS[type].filter(s => s.toLowerCase().includes(value));
        
        if (filtered.length === 0) {
            suggestionsList.classList.add('d-none');
            return;
        }
        
        suggestionsList.innerHTML = filtered.map(s => `
            <div class="suggestion-item" data-value="${s}">${s}</div>
        `).join('');
        
        suggestionsList.classList.remove('d-none');
        
        suggestionsList.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                const input = form.querySelector('.inline-input');
                input.value = item.dataset.value;
                addHistoryItem(type, item.dataset.value, card);
            });
        });
    }
    
    function hideSuggestions(card) {
        const form = card.querySelector('.inline-edit-form');
        const suggestionsList = form?.querySelector('.suggestions-list');
        if (suggestionsList) {
            suggestionsList.classList.add('d-none');
        }
    }
    
    async function addHistoryItem(type, value, card) {
        if (!value || !patientId) return;
        
        const input = card.querySelector('.inline-input');
        let itemsList = card.querySelector('.history-items-list');
        
        try {
            input?.classList.add('disabled');
            
            const response = await fetch(`/care/module-3/patients/${patientId}/history`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    type: type,
                    value: value
                })
            });
            
            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message || `Erreur serveur (${response.status})`);
            }
            
            // Create items list if doesn't exist
            if (!itemsList) {
                const placeholder = card.querySelector('.inline-placeholder');
                itemsList = document.createElement('div');
                itemsList.className = 'history-items-list';
                placeholder?.replaceWith(itemsList);
            }
            
            // Add item to list
            const item = document.createElement('div');
            item.className = 'history-item history-item-tag';
            item.dataset.item = value;
            item.dataset.historyType = type;
            item.innerHTML = `
                <span>${value}</span>
                <button type="button" class="btn-close-inline" data-history-type="${type}" data-item="${value}" title="Supprimer">✕</button>
            `;
            itemsList.appendChild(item);
            
            // Add delete handler
            item.querySelector('.btn-close-inline').addEventListener('click', (e) => {
                e.preventDefault();
                deleteHistoryItem(type, value, card);
            });
            
            // Clear input and show success
            if (input) input.value = '';
            input?.classList.remove('disabled');
            input?.classList.add('success');
            setTimeout(() => input?.classList.remove('success'), 800);
            
            showToast(`✓ ${value} ajouté`, 'success', 2000);
            
            // Hide form
            hideEditForm(card);
        } catch (err) {
            console.error('Error adding history item:', err);
            if (input) {
                input.classList.add('error');
                setTimeout(() => input.classList.remove('error'), 1500);
            }
            showToast('Erreur lors de l\'ajout', 'error', 3000);
        }
    }
    
    async function deleteHistoryItem(type, value, card) {
        if (!patientId) return;
        
        try {
            const response = await fetch(`/care/module-3/patients/${patientId}/history`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    type: type,
                    value: value
                })
            });
            
            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message || `Erreur serveur (${response.status})`);
            }
            
            // Remove item from DOM
            const item = card.querySelector(`.history-item-tag[data-item="${value}"]`);
            if (item) {
                item.style.animation = 'slideInRight .2s ease reverse';
                setTimeout(() => item.remove(), 200);
            }
            
            showToast(`✓ ${value} supprimé`, 'success', 2000);
            
            // Show placeholder if no items left
            const itemsList = card.querySelector('.history-items-list');
            if (itemsList && itemsList.children.length === 0) {
                const placeholder = document.createElement('div');
                placeholder.className = 'small text-secondary inline-placeholder';
                placeholder.innerHTML = `Non renseigné • <a href="#" class="inline-edit-trigger" data-history-type="${type}">Ajouter</a>`;
                itemsList.replaceWith(placeholder);
                
                placeholder.querySelector('a')?.addEventListener('click', (e) => {
                    e.preventDefault();
                    showEditForm(card);
                    card.querySelector('.inline-input')?.focus();
                });
            }
        } catch (err) {
            console.error('Error deleting history item:', err);
            showToast('Erreur lors de la suppression', 'error', 3000);
        }
    }
})();
