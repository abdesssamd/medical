

<style>
/* ============================================
   DESIGN SYSTEM - MediOffice Medical Theme
   ============================================ */
:root {
    --mo-primary: #1e40af;
    --mo-primary-light: #3b82f6;
    --mo-primary-dark: #1e3a8a;
    --mo-success: #059669;
    --mo-success-light: #10b981;
    --mo-warning: #d97706;
    --mo-danger: #dc2626;
    --mo-bg: #f8fafc;
    --mo-card-bg: #ffffff;
    --mo-border: #e2e8f0;
    --mo-text: #0f172a;
    --mo-text-muted: #64748b;
    --mo-radius: 12px;
    --mo-radius-lg: 16px;
    --mo-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
    --mo-shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
}

/* ============================================
   SIDE-SHEET / DRAWER
   ============================================ */
.mo-side-sheet-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    z-index: 1040;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}
.mo-side-sheet-overlay.active {
    opacity: 1;
    visibility: visible;
}
.mo-side-sheet {
    position: fixed;
    top: 0;
    right: 0;
    width: 480px;
    max-width: 90vw;
    height: 100vh;
    background: var(--mo-bg);
    box-shadow: -10px 0 30px rgba(0,0,0,0.15);
    z-index: 1050;
    transform: translateX(100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.mo-side-sheet.active {
    transform: translateX(0);
}
.mo-side-sheet-header {
    padding: 20px 24px;
    background: linear-gradient(135deg, var(--mo-primary) 0%, var(--mo-primary-dark) 100%);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}
.mo-side-sheet-header h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}
.mo-side-sheet-close {
    width: 36px;
    height: 36px;
    border: none;
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}
.mo-side-sheet-close:hover {
    background: rgba(255,255,255,0.3);
}
.mo-side-sheet-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
}
.mo-side-sheet-body::-webkit-scrollbar {
    width: 6px;
}
.mo-side-sheet-body::-webkit-scrollbar-track {
    background: transparent;
}
.mo-side-sheet-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}
.mo-side-sheet-footer {
    padding: 16px 24px;
    background: white;
    border-top: 1px solid var(--mo-border);
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    flex-shrink: 0;
}

/* ============================================
   PATIENT INFO BAR
   ============================================ */
.mo-patient-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: white;
    border-radius: var(--mo-radius);
    border: 1px solid var(--mo-border);
    margin-bottom: 20px;
}
.mo-patient-avatar {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--mo-primary-light) 0%, var(--mo-primary) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1rem;
    flex-shrink: 0;
}
.mo-patient-info {
    flex: 1;
    min-width: 0;
}
.mo-patient-name {
    font-weight: 700;
    color: var(--mo-text);
    font-size: 0.95rem;
}
.mo-patient-meta {
    font-size: 0.8rem;
    color: var(--mo-text-muted);
}

/* ============================================
   SECTION CARDS
   ============================================ */
.mo-section-card {
    background: var(--mo-card-bg);
    border-radius: var(--mo-radius-lg);
    border: 1px solid var(--mo-border);
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: var(--mo-shadow);
}
.mo-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--mo-border);
}
.mo-section-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mo-section-icon.symptoms { background: #eff6ff; color: var(--mo-primary-light); }
.mo-section-icon.vitals { background: #ecfdf5; color: var(--mo-success-light); }
.mo-section-icon.history { background: #fef3c7; color: var(--mo-warning); }
.mo-section-icon.treatment { background: #fce7f3; color: #ec4899; }
.mo-section-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--mo-text);
}
.mo-section-subtitle {
    margin: 0;
    font-size: 0.8rem;
    color: var(--mo-text-muted);
}

/* ============================================
   CHIPS / BADGES SELECTABLES
   ============================================ */
.mo-chips-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.mo-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 999px;
    border: 2px solid var(--mo-border);
    background: white;
    color: var(--mo-text-muted);
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}
.mo-chip:hover {
    border-color: var(--mo-primary-light);
    color: var(--mo-primary);
    background: #eff6ff;
}
.mo-chip.selected {
    border-color: var(--mo-primary);
    background: var(--mo-primary);
    color: white;
}
.mo-chip.selected:hover {
    background: var(--mo-primary-dark);
}
.mo-chip input[type="checkbox"],
.mo-chip input[type="radio"] {
    display: none;
}
.mo-chip-icon {
    width: 16px;
    height: 16px;
}

/* ============================================
   NUMERIC SELECTOR WITH +/-
   ============================================ */
.mo-numeric-selector {
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    border: 2px solid var(--mo-border);
    border-radius: var(--mo-radius);
    padding: 8px 12px;
    transition: all 0.2s ease;
}
.mo-numeric-selector:focus-within {
    border-color: var(--mo-primary-light);
    box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
}
.mo-numeric-btn {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 8px;
    background: var(--mo-bg);
    color: var(--mo-text);
    font-size: 1.2rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}
.mo-numeric-btn:hover {
    background: var(--mo-primary-light);
    color: white;
}
.mo-numeric-btn:active {
    transform: scale(0.95);
}
.mo-numeric-input {
    flex: 1;
    border: none;
    text-align: center;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--mo-text);
    background: transparent;
    outline: none;
    width: 60px;
}
.mo-numeric-unit {
    font-size: 0.8rem;
    color: var(--mo-text-muted);
    font-weight: 600;
    min-width: 30px;
}

/* ============================================
   VITAL SIGNS CARD WITH SPARKLINE
   ============================================ */
.mo-vital-card {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 16px;
    align-items: center;
    padding: 16px;
    background: white;
    border-radius: var(--mo-radius);
    border: 1px solid var(--mo-border);
    margin-bottom: 12px;
}
.mo-vital-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.mo-vital-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--mo-text-muted);
    display: flex;
    align-items: center;
    gap: 6px;
}
.mo-vital-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--mo-text);
}
.mo-vital-value .unit {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--mo-text-muted);
}
.mo-vital-sparkline {
    width: 120px;
    height: 40px;
}
.mo-vital-sparkline svg {
    width: 100%;
    height: 100%;
}
.mo-vital-trend {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}
.mo-vital-trend.up { color: var(--mo-danger); }
.mo-vital-trend.down { color: var(--mo-success); }
.mo-vital-trend.stable { color: var(--mo-text-muted); }

/* ============================================
   TIMELINE
   ============================================ */
.mo-timeline {
    position: relative;
    padding-left: 24px;
}
.mo-timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--mo-border);
}
.mo-timeline-item {
    position: relative;
    padding-bottom: 16px;
}
.mo-timeline-item:last-child {
    padding-bottom: 0;
}
.mo-timeline-dot {
    position: absolute;
    left: -20px;
    top: 4px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--mo-primary-light);
    border: 2px solid white;
    box-shadow: 0 0 0 2px var(--mo-primary-light);
}
.mo-timeline-date {
    font-size: 0.75rem;
    color: var(--mo-text-muted);
    font-weight: 600;
}
.mo-timeline-content {
    font-size: 0.85rem;
    color: var(--mo-text);
    margin-top: 2px;
}

/* ============================================
   FORM ELEMENTS
   ============================================ */
.mo-form-group {
    margin-bottom: 16px;
}
.mo-form-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--mo-text);
    margin-bottom: 6px;
}
.mo-form-label .required {
    color: var(--mo-danger);
}
.mo-form-input {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid var(--mo-border);
    border-radius: var(--mo-radius);
    font-size: 0.9rem;
    color: var(--mo-text);
    background: white;
    transition: all 0.2s ease;
}
.mo-form-input:focus {
    outline: none;
    border-color: var(--mo-primary-light);
    box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
}
.mo-form-textarea {
    min-height: 80px;
    resize: vertical;
}

/* ============================================
   BUTTONS
   ============================================ */
.mo-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: var(--mo-radius);
    font-size: 0.9rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}
.mo-btn-primary {
    background: linear-gradient(135deg, var(--mo-primary-light) 0%, var(--mo-primary) 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(37,99,235,0.25);
}
.mo-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(37,99,235,0.3);
}
.mo-btn-secondary {
    background: white;
    color: var(--mo-text);
    border: 2px solid var(--mo-border);
}
.mo-btn-secondary:hover {
    border-color: var(--mo-primary-light);
    color: var(--mo-primary);
}
.mo-btn-success {
    background: linear-gradient(135deg, var(--mo-success-light) 0%, var(--mo-success) 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(5,150,105,0.25);
}
.mo-btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(5,150,105,0.3);
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 640px) {
    .mo-side-sheet {
        width: 100vw;
        max-width: 100vw;
    }
    .mo-vital-card {
        grid-template-columns: 1fr;
    }
    .mo-vital-sparkline {
        width: 100%;
        height: 30px;
    }
}
</style>


<div class="mo-side-sheet-overlay" id="questionnaireOverlay" onclick="closeQuestionnaireSheet()"></div>


<div class="mo-side-sheet" id="questionnaireSheet">
    
    <div class="mo-side-sheet-header">
        <h4>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Questionnaire & Constantes
        </h4>
        <button class="mo-side-sheet-close" onclick="closeQuestionnaireSheet()">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    
    <div class="mo-side-sheet-body">
        
        <div class="mo-patient-bar">
            <div class="mo-patient-avatar" id="moPatientAvatar">HF</div>
            <div class="mo-patient-info">
                <div class="mo-patient-name" id="moPatientName">Hadjer Ferhi</div>
                <div class="mo-patient-meta" id="moPatientMeta">30 ans • MRN-2026-0003</div>
            </div>
            <div class="mo-patient-date" style="font-size: 0.8rem; color: var(--mo-text-muted); text-align: right;">
                <div id="moCurrentDate"></div>
                <div style="font-size: 0.7rem;">Consultation</div>
            </div>
        </div>

        
        <div class="mo-section-card">
            <div class="mo-section-header">
                <div class="mo-section-icon symptoms">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </div>
                <div>
                    <h5 class="mo-section-title">Symptômes</h5>
                    <p class="mo-section-subtitle">Sélectionnez les symptômes présents</p>
                </div>
            </div>
            
            <div class="mo-form-group">
                <label class="mo-form-label">Type de douleur <span class="required">*</span></label>
                <div class="mo-chips-group" id="painTypeChips">
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="symptoms[pain_type][]" value="aigue">
                        <svg class="mo-chip-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        Aiguë
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="symptoms[pain_type][]" value="chronique">
                        <svg class="mo-chip-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                        Chronique
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="symptoms[pain_type][]" value="pulsatile">
                        <svg class="mo-chip-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        Pulsatile
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="symptoms[pain_type][]" value="brulure">
                        <svg class="mo-chip-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2c-4 4-8 8-8 12a8 8 0 0 0 16 0c0-4-4-8-8-12z"/></svg>
                        Brûlure
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="symptoms[pain_type][]" value="lancinante">
                        <svg class="mo-chip-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        Lancinante
                    </label>
                </div>
            </div>

            <div class="mo-form-group">
                <label class="mo-form-label">Localisation</label>
                <div class="mo-chips-group" id="locationChips">
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="symptoms[location][]" value="superieur">
                        Arcade sup.
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="symptoms[location][]" value="inferieur">
                        Arcade inf.
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="symptoms[location][]" value="droit">
                        Côté droit
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="symptoms[location][]" value="gauche">
                        Côté gauche
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="symptoms[location][]" value="diffuse">
                        Diffuse
                    </label>
                </div>
            </div>

            <div class="mo-form-group">
                <label class="mo-form-label">Intensité (0-10)</label>
                <div class="mo-chips-group" id="intensityChips">
                    <?php for($i = 0; $i <= 10; $i++): ?>
                        <label class="mo-chip" onclick="selectSingleChip(this, 'intensityChips')">
                            <input type="radio" name="symptoms[intensity]" value="<?php echo e($i); ?>">
                            <?php echo e($i); ?>

                        </label>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        
        <div class="mo-section-card">
            <div class="mo-section-header">
                <div class="mo-section-icon vitals">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </div>
                <div>
                    <h5 class="mo-section-title">Constantes Vitales</h5>
                    <p class="mo-section-subtitle">Mesures actuelles du patient</p>
                </div>
            </div>

            
            <div class="mo-vital-card">
                <div class="mo-vital-info">
                    <div class="mo-vital-label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/></svg>
                        Température
                    </div>
                    <div class="mo-vital-value">
                        <span id="tempValue">37.0</span>
                        <span class="unit">°C</span>
                    </div>
                    <div class="mo-vital-trend stable" id="tempTrend">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Stable
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                    <div class="mo-numeric-selector">
                        <button class="mo-numeric-btn" onclick="adjustValue('temp', -0.1)">−</button>
                        <input type="number" class="mo-numeric-input" id="tempInput" value="37.0" step="0.1" min="35" max="42" onchange="updateVital('temp', this.value)">
                        <button class="mo-numeric-btn" onclick="adjustValue('temp', 0.1)">+</button>
                    </div>
                    <div class="mo-vital-sparkline" id="tempSparkline"></div>
                </div>
            </div>

            
            <div class="mo-vital-card">
                <div class="mo-vital-info">
                    <div class="mo-vital-label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                        Tension Artérielle
                    </div>
                    <div class="mo-vital-value">
                        <span id="bpValue">120/80</span>
                        <span class="unit">mmHg</span>
                    </div>
                    <div class="mo-vital-trend stable" id="bpTrend">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Normale
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                    <div style="display: flex; gap: 8px;">
                        <div class="mo-numeric-selector" style="width: 100px;">
                            <button class="mo-numeric-btn" onclick="adjustValue('bpSys', -5)">−</button>
                            <input type="number" class="mo-numeric-input" id="bpSysInput" value="120" step="5" min="70" max="200" onchange="updateBP()">
                            <button class="mo-numeric-btn" onclick="adjustValue('bpSys', 5)">+</button>
                        </div>
                        <div class="mo-numeric-selector" style="width: 100px;">
                            <button class="mo-numeric-btn" onclick="adjustValue('bpDia', -5)">−</button>
                            <input type="number" class="mo-numeric-input" id="bpDiaInput" value="80" step="5" min="40" max="130" onchange="updateBP()">
                            <button class="mo-numeric-btn" onclick="adjustValue('bpDia', 5)">+</button>
                        </div>
                    </div>
                    <div class="mo-vital-sparkline" id="bpSparkline"></div>
                </div>
            </div>

            
            <div class="mo-vital-card">
                <div class="mo-vital-info">
                    <div class="mo-vital-label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        Pouls
                    </div>
                    <div class="mo-vital-value">
                        <span id="pulseValue">72</span>
                        <span class="unit">bpm</span>
                    </div>
                    <div class="mo-vital-trend stable" id="pulseTrend">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Normal
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                    <div class="mo-numeric-selector">
                        <button class="mo-numeric-btn" onclick="adjustValue('pulse', -5)">−</button>
                        <input type="number" class="mo-numeric-input" id="pulseInput" value="72" step="1" min="30" max="200" onchange="updateVital('pulse', this.value)">
                        <button class="mo-numeric-btn" onclick="adjustValue('pulse', 5)">+</button>
                    </div>
                    <div class="mo-vital-sparkline" id="pulseSparkline"></div>
                </div>
            </div>

            
            <div class="mo-vital-card">
                <div class="mo-vital-info">
                    <div class="mo-vital-label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="3"/><line x1="12" y1="22" x2="12" y2="8"/><path d="M5 12H2a10 10 0 0 0 20 0h-3"/></svg>
                        Poids
                    </div>
                    <div class="mo-vital-value">
                        <span id="weightValue">70</span>
                        <span class="unit">kg</span>
                    </div>
                </div>
                <div class="mo-numeric-selector" style="width: 140px;">
                    <button class="mo-numeric-btn" onclick="adjustValue('weight', -1)">−</button>
                    <input type="number" class="mo-numeric-input" id="weightInput" value="70" step="0.5" min="20" max="300" onchange="updateVital('weight', this.value)">
                    <button class="mo-numeric-btn" onclick="adjustValue('weight', 1)">+</button>
                </div>
            </div>
        </div>

        
        <div class="mo-section-card">
            <div class="mo-section-header">
                <div class="mo-section-icon history">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                </div>
                <div>
                    <h5 class="mo-section-title">Antécédents</h5>
                    <p class="mo-section-subtitle">Historique médical pertinent</p>
                </div>
            </div>

            <div class="mo-form-group">
                <label class="mo-form-label">Allergies connues</label>
                <div class="mo-chips-group" id="allergyChips">
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="history[allergies][]" value="penicilline">
                        Pénicilline
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="history[allergies][]" value="ibuprofene">
                        Ibuprofène
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="history[allergies][]" value="latex">
                        Latex
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="history[allergies][]" value="anesthesique">
                        Anesthésique
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="history[allergies][]" value="aucune">
                        Aucune
                    </label>
                </div>
            </div>

            <div class="mo-form-group">
                <label class="mo-form-label">Conditions médicales</label>
                <div class="mo-chips-group" id="conditionChips">
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="history[conditions][]" value="diabete">
                        Diabète
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="history[conditions][]" value="hypertension">
                        Hypertension
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="history[conditions][]" value="cardiaque">
                        Cardiaque
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="history[conditions][]" value="asthme">
                        Asthme
                    </label>
                    <label class="mo-chip" onclick="toggleChip(this)">
                        <input type="checkbox" name="history[conditions][]" value="grossesse">
                        Grossesse
                    </label>
                </div>
            </div>

            <div class="mo-form-group">
                <label class="mo-form-label">Médicaments actuels</label>
                <textarea class="mo-form-input mo-form-textarea" name="history[medications]" placeholder="Lister les médicaments en cours..."></textarea>
            </div>
        </div>

        
        <div class="mo-section-card">
            <div class="mo-section-header">
                <div class="mo-section-icon treatment">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                </div>
                <div>
                    <h5 class="mo-section-title">Historique récent</h5>
                    <p class="mo-section-subtitle">Dernières consultations</p>
                </div>
            </div>

            <div class="mo-timeline" id="consultationTimeline">
                <div class="mo-timeline-item">
                    <div class="mo-timeline-dot"></div>
                    <div class="mo-timeline-date">24 Mai 2026</div>
                    <div class="mo-timeline-content">Consultation - Douleur dentaire</div>
                </div>
                <div class="mo-timeline-item">
                    <div class="mo-timeline-dot" style="background: #cbd5e1; box-shadow: 0 0 0 2px #cbd5e1;"></div>
                    <div class="mo-timeline-date">10 Mai 2026</div>
                    <div class="mo-timeline-content">Contrôle post-opératoire</div>
                </div>
                <div class="mo-timeline-item">
                    <div class="mo-timeline-dot" style="background: #cbd5e1; box-shadow: 0 0 0 2px #cbd5e1;"></div>
                    <div class="mo-timeline-date">28 Avril 2026</div>
                    <div class="mo-timeline-content">Extraction dent 36</div>
                </div>
            </div>
        </div>

        
        <div class="mo-section-card">
            <div class="mo-form-group" style="margin-bottom: 0;">
                <label class="mo-form-label">Notes complémentaires</label>
                <textarea class="mo-form-input mo-form-textarea" name="notes" rows="3" placeholder="Observations supplémentaires..."></textarea>
            </div>
        </div>
    </div>

    
    <div class="mo-side-sheet-footer">
        <button type="button" class="mo-btn mo-btn-secondary" onclick="closeQuestionnaireSheet()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Annuler
        </button>
        <button type="button" class="mo-btn mo-btn-success" onclick="saveQuestionnaire()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/></svg>
            Enregistrer
        </button>
    </div>
</div>

<script>
// ============================================
// MediOffice Questionnaire Engine v2.0
// ============================================

// State management
const questionnaireState = {
    vitals: {
        temp: { value: 37.0, history: [36.8, 37.0, 36.9, 37.1, 37.0] },
        bpSys: { value: 120, history: [118, 122, 120, 119, 120] },
        bpDia: { value: 80, history: [78, 82, 80, 79, 80] },
        pulse: { value: 72, history: [70, 74, 72, 71, 72] },
        weight: { value: 70, history: [71, 70.5, 70, 69.5, 70] }
    },
    symptoms: {},
    history: {}
};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Set current date
    const now = new Date();
    document.getElementById('moCurrentDate').textContent = now.toLocaleDateString('fr-FR', { 
        day: 'numeric', 
        month: 'short', 
        year: 'numeric' 
    });
    
    // Render sparklines
    renderSparkline('tempSparkline', questionnaireState.vitals.temp.history, '#3b82f6');
    renderSparkline('bpSparkline', questionnaireState.vitals.bpSys.history, '#059669');
    renderSparkline('pulseSparkline', questionnaireState.vitals.pulse.history, '#d97706');
});

// Chip interactions
function toggleChip(chip) {
    chip.classList.toggle('selected');
    const input = chip.querySelector('input');
    if (input) {
        input.checked = !input.checked;
    }
}

function selectSingleChip(chip, groupId) {
    const group = document.getElementById(groupId);
    group.querySelectorAll('.mo-chip').forEach(c => c.classList.remove('selected'));
    chip.classList.add('selected');
    const input = chip.querySelector('input');
    if (input) input.checked = true;
}

// Numeric value adjustments
function adjustValue(type, delta) {
    const input = document.getElementById(type + 'Input');
    if (!input) return;
    
    let value = parseFloat(input.value) || 0;
    value = Math.max(parseFloat(input.min), Math.min(parseFloat(input.max), value + delta));
    
    // Round to appropriate precision
    if (type === 'temp') {
        value = Math.round(value * 10) / 10;
    } else {
        value = Math.round(value);
    }
    
    input.value = value;
    updateVital(type, value);
}

function updateVital(type, value) {
    value = parseFloat(value);
    questionnaireState.vitals[type].value = value;
    
    // Update display
    const displayMap = {
        temp: { el: 'tempValue', unit: '°C' },
        pulse: { el: 'pulseValue', unit: 'bpm' },
        weight: { el: 'weightValue', unit: 'kg' }
    };
    
    if (displayMap[type]) {
        document.getElementById(displayMap[type].el).textContent = value;
    }
    
    // Update trend
    updateTrend(type, value);
}

function updateBP() {
    const sys = parseInt(document.getElementById('bpSysInput').value) || 120;
    const dia = parseInt(document.getElementById('bpDiaInput').value) || 80;
    
    questionnaireState.vitals.bpSys.value = sys;
    questionnaireState.vitals.bpDia.value = dia;
    
    document.getElementById('bpValue').textContent = `${sys}/${dia}`;
    
    // Update trend based on BP category
    const trendEl = document.getElementById('bpTrend');
    if (sys > 140 || dia > 90) {
        trendEl.className = 'mo-vital-trend up';
        trendEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,6 13.5,15.5 8.5,10.5 1,18"/></svg> Élevée';
    } else if (sys < 90 || dia < 60) {
        trendEl.className = 'mo-vital-trend down';
        trendEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,18 13.5,8.5 8.5,13.5 1,6"/></svg> Basse';
    } else {
        trendEl.className = 'mo-vital-trend stable';
        trendEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg> Normale';
    }
}

function updateTrend(type, value) {
    const history = questionnaireState.vitals[type].history;
    const prevValue = history[history.length - 1] || value;
    const trendEl = document.getElementById(type + 'Trend');
    
    if (!trendEl) return;
    
    if (value > prevValue) {
        trendEl.className = 'mo-vital-trend up';
        trendEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,6 13.5,15.5 8.5,10.5 1,18"/></svg> En hausse';
    } else if (value < prevValue) {
        trendEl.className = 'mo-vital-trend down';
        trendEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,18 13.5,8.5 8.5,13.5 1,6"/></svg> En baisse';
    } else {
        trendEl.className = 'mo-vital-trend stable';
        trendEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg> Stable';
    }
}

// Sparkline rendering
function renderSparkline(containerId, data, color) {
    const container = document.getElementById(containerId);
    if (!container || !data.length) return;
    
    const width = 120;
    const height = 40;
    const padding = 2;
    
    const min = Math.min(...data);
    const max = Math.max(...data);
    const range = max - min || 1;
    
    const points = data.map((value, index) => {
        const x = padding + (index / (data.length - 1)) * (width - 2 * padding);
        const y = height - padding - ((value - min) / range) * (height - 2 * padding);
        return `${x},${y}`;
    }).join(' ');
    
    container.innerHTML = `
        <svg viewBox="0 0 ${width} ${height}" preserveAspectRatio="none">
            <polyline 
                points="${points}" 
                fill="none" 
                stroke="${color}" 
                stroke-width="2" 
                stroke-linecap="round" 
                stroke-linejoin="round"
            />
            <circle 
                cx="${padding + ((data.length - 1) / (data.length - 1)) * (width - 2 * padding)}" 
                cy="${height - padding - ((data[data.length - 1] - min) / range) * (height - 2 * padding)}" 
                r="3" 
                fill="${color}"
            />
        </svg>
    `;
}

// Side-sheet controls
function openQuestionnaireSheet() {
    document.getElementById('questionnaireOverlay').classList.add('active');
    document.getElementById('questionnaireSheet').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeQuestionnaireSheet() {
    document.getElementById('questionnaireOverlay').classList.remove('active');
    document.getElementById('questionnaireSheet').classList.remove('active');
    document.body.style.overflow = '';
}

// Save questionnaire data
function saveQuestionnaire() {
    // Collect all form data
    const formData = new FormData();
    
    // Collect symptoms
    document.querySelectorAll('#painTypeChips input:checked').forEach(input => {
        formData.append('symptoms[pain_type][]', input.value);
    });
    document.querySelectorAll('#locationChips input:checked').forEach(input => {
        formData.append('symptoms[location][]', input.value);
    });
    const intensityInput = document.querySelector('#intensityChips input:checked');
    if (intensityInput) {
        formData.append('symptoms[intensity]', intensityInput.value);
    }
    
    // Collect vitals
    formData.append('vitals[temperature]', document.getElementById('tempInput').value);
    formData.append('vitals[bp_systolic]', document.getElementById('bpSysInput').value);
    formData.append('vitals[bp_diastolic]', document.getElementById('bpDiaInput').value);
    formData.append('vitals[pulse]', document.getElementById('pulseInput').value);
    formData.append('vitals[weight]', document.getElementById('weightInput').value);
    
    // Collect history
    document.querySelectorAll('#allergyChips input:checked').forEach(input => {
        formData.append('history[allergies][]', input.value);
    });
    document.querySelectorAll('#conditionChips input:checked').forEach(input => {
        formData.append('history[conditions][]', input.value);
    });
    formData.append('history[medications]', document.querySelector('[name="history[medications]"]').value);
    formData.append('notes', document.querySelector('[name="notes"]').value);
    
    // Show loading state
    const saveBtn = document.querySelector('.mo-btn-success');
    const originalContent = saveBtn.innerHTML;
    saveBtn.innerHTML = '<svg class="animate-spin" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Enregistrement...';
    saveBtn.disabled = true;
    
    // Simulate API call (replace with actual endpoint)
    setTimeout(() => {
        // Success feedback
        saveBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg> Enregistré !';
        saveBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
        
        setTimeout(() => {
            closeQuestionnaireSheet();
            saveBtn.innerHTML = originalContent;
            saveBtn.disabled = false;
            saveBtn.style.background = '';
        }, 1000);
    }, 1500);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeQuestionnaireSheet();
    }
});
</script>
<?php /**PATH D:\xampp8.2\htdocs\fils_attente\resources\views/modules/partials/questionnaire-modern.blade.php ENDPATH**/ ?>