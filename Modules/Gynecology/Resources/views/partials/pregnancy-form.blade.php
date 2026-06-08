@props(['pregnancy' => null, 'selectedPatientId' => 0])

<div class="modal fade" id="pregnancyFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#fdf2f8,#fce7f3);border-bottom:1px solid #f9a8d4">
                <h5 class="modal-title" style="font-weight:800;color:#be185d">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    Dossier Obstétrical
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="pregnancyForm">
                    @csrf
                    <input type="hidden" name="id" id="pregnancyRecordId" value="{{ $pregnancy?->id }}">

                    <div class="preg-form-section">
                        <h4>Informations générales</h4>
                        <div class="preg-form-grid preg-form-grid-3">
                            <div class="preg-field">
                                <label>N° grossesse</label>
                                <input type="text" name="pregnancy_number" id="pregnancyNumber" class="form-control" placeholder="G1, G2..." value="{{ old('pregnancy_number', $pregnancy?->pregnancy_number) }}">
                            </div>
                            <div class="preg-field">
                                <label>DDR (Date dernières règles) *</label>
                                <input type="date" name="lmp_date" id="lmpDate" class="form-control" required value="{{ old('lmp_date', $pregnancy?->lmp_date?->format('Y-m-d')) }}">
                                <span class="preg-hint">Saisissez la date pour calcul automatique</span>
                            </div>
                            <div class="preg-field">
                                <label>Statut</label>
                                <select name="pregnancy_status" id="pregnancyStatus" class="form-select">
                                    <option value="active" {{ ($pregnancy?->pregnancy_status ?? 'active') === 'active' ? 'selected' : '' }}>En cours</option>
                                    <option value="delivered" {{ ($pregnancy?->pregnancy_status) === 'delivered' ? 'selected' : '' }}>Accouchée</option>
                                    <option value="missed" {{ ($pregnancy?->pregnancy_status) === 'missed' ? 'selected' : '' }}>Fausse couche</option>
                                    <option value="terminated" {{ ($pregnancy?->pregnancy_status) === 'terminated' ? 'selected' : '' }}>Interruption</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="preg-calc-display" id="pregCalcDisplay">
                        <div class="calc-card calc-card-primary">
                            <div class="calc-label">Âge gestationnel</div>
                            <div class="calc-value" id="calcGestationalAge">-- SA + -- j</div>
                            <div class="calc-sub" id="calcTrimester">-</div>
                        </div>
                        <div class="calc-card calc-card-calendar">
                            <div class="calc-label">DPA calculée</div>
                            <div class="calc-value" id="calcEDD">--/--/----</div>
                            <div class="calc-sub" id="calcDaysUntil">-</div>
                        </div>
                        <div class="calc-card calc-card-conception">
                            <div class="calc-label">Date conception estimée</div>
                            <div class="calc-value" id="calcConception">--/--/----</div>
                            <div class="calc-sub">DDR + 14 jours</div>
                        </div>
                        <div class="calc-card calc-card-progress">
                            <div class="calc-label">Progression</div>
                            <div class="calc-value" id="calcProgress">0%</div>
                            <div class="preg-progress-bar">
                                <div class="preg-progress-fill" id="calcProgressFill" style="width:0%"></div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="estimated_delivery_date" id="estimatedDeliveryDate">
                    <input type="hidden" name="conception_date" id="conceptionDate">
                    <input type="hidden" name="gestational_age_weeks" id="gestationalAgeWeeks">
                    <input type="hidden" name="gestational_age_days" id="gestationalAgeDays">
                    <input type="hidden" name="trimester" id="trimester">

                    <div class="preg-form-section">
                        <h4>Groupe sanguin & Compatibilité</h4>
                        <div class="preg-form-grid preg-form-grid-4">
                            <div class="preg-field">
                                <label>Groupe mère</label>
                                <select name="blood_type" class="form-select">
                                    <option value="">-</option>
                                    @foreach(['A','B','AB','O'] as $bt)
                                        <option value="{{ $bt }}" {{ ($pregnancy?->blood_type) === $bt ? 'selected' : '' }}>{{ $bt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="preg-field">
                                <label>Rh mère</label>
                                <select name="rh_factor" class="form-select">
                                    <option value="">-</option>
                                    <option value="positive" {{ ($pregnancy?->rh_factor) === 'positive' ? 'selected' : '' }}>Rh+</option>
                                    <option value="negative" {{ ($pregnancy?->rh_factor) === 'negative' ? 'selected' : '' }}>Rh-</option>
                                </select>
                            </div>
                            <div class="preg-field">
                                <label>Groupe père</label>
                                <select name="partner_blood_type" class="form-select">
                                    <option value="">-</option>
                                    @foreach(['A','B','AB','O'] as $bt)
                                        <option value="{{ $bt }}" {{ ($pregnancy?->partner_blood_type) === $bt ? 'selected' : '' }}>{{ $bt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="preg-field">
                                <label>Rh père</label>
                                <select name="partner_rh_factor" class="form-select">
                                    <option value="">-</option>
                                    <option value="positive" {{ ($pregnancy?->partner_rh_factor) === 'positive' ? 'selected' : '' }}>Rh+</option>
                                    <option value="negative" {{ ($pregnancy?->partner_rh_factor) === 'negative' ? 'selected' : '' }}>Rh-</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="preg-form-section">
                        <h4>Sérologies</h4>
                        <div class="preg-form-grid preg-form-grid-4">
                            @foreach([
                                'serology_hiv' => 'VIH',
                                'serology_hepatitis_b' => 'Hépatite B',
                                'serology_hepatitis_c' => 'Hépatite C',
                                'serology_syphilis' => 'Syphilis',
                                'serology_toxoplasmosis' => 'Toxoplasmose',
                                'serology_rubella' => 'Rubéole',
                                'serology_cmV' => 'CMV',
                            ] as $field => $label)
                                <div class="preg-field">
                                    <label>{{ $label }}</label>
                                    <select name="{{ $field }}" class="form-select">
                                        <option value="">Non fait</option>
                                        @foreach(['negative' => 'Négatif', 'positive' => 'Positif', 'immune' => 'Immunisée'] as $val => $lbl)
                                            <option value="{{ $val }}" {{ ($pregnancy?->{$field}) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                            <div class="preg-field">
                                <label>RAI</label>
                                <input type="text" name="rai_result" class="form-control" placeholder="Négatif, Positif..." value="{{ old('rai_result', $pregnancy?->rai_result) }}">
                            </div>
                        </div>
                    </div>

                    <div class="preg-form-section">
                        <h4>Niveau de risque</h4>
                        <div class="preg-form-grid preg-form-grid-2">
                            <div class="preg-field">
                                <label>Niveau de risque</label>
                                <select name="risk_level" class="form-select">
                                    <option value="low" {{ ($pregnancy?->risk_level ?? 'low') === 'low' ? 'selected' : '' }}>Faible</option>
                                    <option value="moderate" {{ ($pregnancy?->risk_level) === 'moderate' ? 'selected' : '' }}>Modéré</option>
                                    <option value="high" {{ ($pregnancy?->risk_level) === 'high' ? 'selected' : '' }}>Élevé</option>
                                </select>
                            </div>
                            <div class="preg-field">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Facteurs de risque, observations...">{{ old('notes', $pregnancy?->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-pink" id="submitPregnancyForm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.preg-form-section{padding:14px;border:1px solid #f1f5f9;border-radius:14px;background:#fafbfc;margin-bottom:14px}
.preg-form-section h4{font-size:.9rem;font-weight:800;color:#0f172a;margin:0 0 12px;padding-bottom:8px;border-bottom:1px solid #e2e8f0}
.preg-form-grid{display:grid;gap:10px}
.preg-form-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
.preg-form-grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.preg-form-grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
.preg-field label{display:block;font-size:.78rem;font-weight:700;color:#334155;margin-bottom:4px}
.preg-field .form-control,.preg-field .form-select{border-radius:10px;border-color:#e2e8f0;font-size:.88rem}
.preg-field .form-control:focus,.preg-field .form-select:focus{border-color:#ec4899;box-shadow:0 0 0 3px rgba(236,72,153,.12)}
.preg-hint{display:block;font-size:.72rem;color:#94a3b8;margin-top:2px}
.preg-calc-display{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px;padding:14px;background:linear-gradient(135deg,#fdf2f8,#fce7f3);border-radius:14px;border:1px solid #f9a8d4}
.calc-card{padding:12px;border-radius:12px;background:#fff;border:1px solid #e2e8f0;text-align:center}
.calc-card-primary{border-color:#f9a8d4;background:linear-gradient(135deg,#fff,#fdf2f8)}
.calc-card-calendar{border-color:#93c5fd;background:linear-gradient(135deg,#fff,#eff6ff)}
.calc-card-conception{border-color:#c4b5fd;background:linear-gradient(135deg,#fff,#f5f3ff)}
.calc-card-progress{border-color:#86efac;background:linear-gradient(135deg,#fff,#f0fdf4)}
.calc-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#64748b;font-weight:700;margin-bottom:4px}
.calc-value{font-size:1.2rem;font-weight:800;color:#0f172a;line-height:1.2}
.calc-sub{font-size:.78rem;color:#64748b;margin-top:2px}
.preg-progress-bar{height:6px;background:#e2e8f0;border-radius:999px;margin-top:8px;overflow:hidden}
.preg-progress-fill{height:100%;background:linear-gradient(90deg,#ec4899,#db2777);border-radius:999px;transition:width .4s ease}
.btn-pink{background:#ec4899;color:#fff;border:none;padding:8px 20px;border-radius:10px;font-weight:700;font-size:.88rem;cursor:pointer;display:flex;align-items:center;gap:6px}
.btn-pink:hover{background:#db2777;color:#fff}
@media (max-width:1200px){.preg-calc-display{grid-template-columns:repeat(2,1fr)}.preg-form-grid-4{grid-template-columns:repeat(2,1fr)}}
@media (max-width:768px){.preg-calc-display{grid-template-columns:1fr}.preg-form-grid-4,.preg-form-grid-3,.preg-form-grid-2{grid-template-columns:1fr}}
</style>

<script>
(() => {
    const lmpInput = document.getElementById('lmpDate');
    const calcGestationalAge = document.getElementById('calcGestationalAge');
    const calcTrimester = document.getElementById('calcTrimester');
    const calcEDD = document.getElementById('calcEDD');
    const calcDaysUntil = document.getElementById('calcDaysUntil');
    const calcConception = document.getElementById('calcConception');
    const calcProgress = document.getElementById('calcProgress');
    const calcProgressFill = document.getElementById('calcProgressFill');

    const hiddenEDD = document.getElementById('estimatedDeliveryDate');
    const hiddenConception = document.getElementById('conceptionDate');
    const hiddenWeeks = document.getElementById('gestationalAgeWeeks');
    const hiddenDays = document.getElementById('gestationalAgeDays');
    const hiddenTrimester = document.getElementById('trimester');

    function calculatePregnancy(lmpDateStr) {
        if (!lmpDateStr) {
            resetDisplay();
            return;
        }

        const lmp = new Date(lmpDateStr);
        const today = new Date();

        if (lmp > today) {
            calcGestationalAge.textContent = 'DDR future';
            calcGestationalAge.style.color = '#dc2626';
            calcTrimester.textContent = 'Date invalide';
            calcEDD.textContent = '--/--/----';
            calcDaysUntil.textContent = '-';
            calcConception.textContent = '--/--/----';
            calcProgress.textContent = '0%';
            calcProgressFill.style.width = '0%';
            return;
        }

        const diffMs = today - lmp;
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        const weeks = Math.floor(diffDays / 7);
        const days = diffDays % 7;

        const trimester = weeks < 14 ? '1er trimestre' : weeks < 28 ? '2ème trimestre' : '3ème trimestre';

        const edd = new Date(lmp);
        edd.setDate(edd.getDate() + 280);

        const conception = new Date(lmp);
        conception.setDate(conception.getDate() + 14);

        const daysUntilEDD = Math.floor((edd - today) / (1000 * 60 * 60 * 24));

        const progress = Math.min(100, Math.round((weeks / 42) * 100));

        calcGestationalAge.textContent = `${weeks} SA + ${days} j`;
        calcGestationalAge.style.color = '#0f172a';
        calcTrimester.textContent = trimester;
        calcEDD.textContent = formatDate(edd);
        calcConception.textContent = formatDate(conception);
        calcProgress.textContent = `${progress}%`;
        calcProgressFill.style.width = `${progress}%`;

        if (daysUntilEDD > 0) {
            calcDaysUntil.textContent = `Dans ${daysUntilEDD} jours`;
            calcDaysUntil.style.color = '#16a34a';
        } else if (daysUntilEDD === 0) {
            calcDaysUntil.textContent = "Aujourd'hui";
            calcDaysUntil.style.color = '#d97706';
        } else {
            calcDaysUntil.textContent = `Terme dépassé de ${Math.abs(daysUntilEDD)} jours`;
            calcDaysUntil.style.color = '#dc2626';
        }

        hiddenEDD.value = edd.toISOString().slice(0, 10);
        hiddenConception.value = conception.toISOString().slice(0, 10);
        hiddenWeeks.value = weeks;
        hiddenDays.value = days;
        hiddenTrimester.value = trimester === '1er trimestre' ? 'first' : trimester === '2ème trimestre' ? 'second' : 'third';
    }

    function resetDisplay() {
        calcGestationalAge.textContent = '-- SA + -- j';
        calcGestationalAge.style.color = '#0f172a';
        calcTrimester.textContent = '-';
        calcEDD.textContent = '--/--/----';
        calcDaysUntil.textContent = '-';
        calcDaysUntil.style.color = '#64748b';
        calcConception.textContent = '--/--/----';
        calcProgress.textContent = '0%';
        calcProgressFill.style.width = '0%';
        hiddenEDD.value = '';
        hiddenConception.value = '';
        hiddenWeeks.value = '';
        hiddenDays.value = '';
        hiddenTrimester.value = '';
    }

    function formatDate(date) {
        const d = date.getDate().toString().padStart(2, '0');
        const m = (date.getMonth() + 1).toString().padStart(2, '0');
        const y = date.getFullYear();
        return `${d}/${m}/${y}`;
    }

    if (lmpInput) {
        lmpInput.addEventListener('change', () => calculatePregnancy(lmpInput.value));
        lmpInput.addEventListener('input', () => calculatePregnancy(lmpInput.value));

        if (lmpInput.value) {
            calculatePregnancy(lmpInput.value);
        }
    }

    const submitBtn = document.getElementById('submitPregnancyForm');
    if (submitBtn) {
        submitBtn.addEventListener('click', async () => {
            const form = document.getElementById('pregnancyForm');
            if (!form) return;

            const patientId = typeof getCareSelectedPatientId === 'function' ? getCareSelectedPatientId() : 0;
            if (!patientId) {
                alert('Veuillez sélectionner un patient');
                return;
            }

            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Enregistrement...';

            try {
                const fd = new FormData(form);
                const data = {};
                for (const [key, value] of fd.entries()) {
                    data[key] = value;
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const response = await fetch(`/care/gynecology/patients/${patientId}/pregnancy-record`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(data),
                });

                if (!response.ok) {
                    const errors = await response.json().catch(() => ({}));
                    throw new Error(errors.message || `Erreur ${response.status}`);
                }

                const result = await response.json();

                if (window.__careShowToast) {
                    window.__careShowToast(result.message || 'Dossier obstétrical enregistré.', 'success');
                }

                window.dispatchEvent(new Event('care:data-updated'));

                const modal = bootstrap.Modal.getInstance(document.getElementById('pregnancyFormModal'));
                if (modal) modal.hide();

                setTimeout(() => location.reload(), 500);
            } catch (error) {
                if (window.__careShowToast) {
                    window.__careShowToast(error.message, 'error');
                } else {
                    alert(error.message);
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
})();
</script>
