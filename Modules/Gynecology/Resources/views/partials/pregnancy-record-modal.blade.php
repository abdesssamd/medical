<div class="modal fade" id="pregnancyRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#fdf2f8,#fce7f3);border-bottom:1px solid #f9a8d4">
                <h5 class="modal-title" style="font-weight:800;color:#be185d">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    Dossier Obstétrical
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="pregnancyRecordForm">
                    @csrf
                    <input type="hidden" name="id" id="pregnancyRecordId">

                    <div class="gyneco-form-section">
                        <h4>Informations générales</h4>
                        <div class="gyneco-form-grid gyneco-form-grid-3">
                            <div class="gyneco-field">
                                <label>N° grossesse</label>
                                <input type="text" name="pregnancy_number" id="pregnancyNumber" class="form-control" placeholder="G1, G2...">
                            </div>
                            <div class="gyneco-field">
                                <label>DDR (Date dernières règles) *</label>
                                <input type="date" name="lmp_date" id="lmpDate" class="form-control" required>
                            </div>
                            <div class="gyneco-field">
                                <label>DPA calculée</label>
                                <input type="date" name="estimated_delivery_date" id="estimatedDeliveryDate" class="form-control" readonly style="background:#f8fafc">
                                <span class="gyneco-hint">Calculée automatiquement (DDR + 280j)</span>
                            </div>
                        </div>
                        <div class="gyneco-form-grid gyneco-form-grid-3 mt-2">
                            <div class="gyneco-field">
                                <label>DPA corrigée</label>
                                <input type="date" name="corrected_delivery_date" id="correctedDeliveryDate" class="form-control">
                            </div>
                            <div class="gyneco-field">
                                <label>Statut</label>
                                <select name="pregnancy_status" id="pregnancyStatus" class="form-select">
                                    <option value="active">En cours</option>
                                    <option value="delivered">Accouchée</option>
                                    <option value="missed">Fausse couche</option>
                                    <option value="terminated">Interruption</option>
                                </select>
                            </div>
                            <div class="gyneco-field">
                                <label>Niveau de risque</label>
                                <select name="risk_level" id="riskLevel" class="form-select">
                                    <option value="low">Faible</option>
                                    <option value="moderate">Modéré</option>
                                    <option value="high">Élevé</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="gyneco-form-section">
                        <h4>Groupe sanguin & Compatibilité</h4>
                        <div class="gyneco-form-grid gyneco-form-grid-4">
                            <div class="gyneco-field">
                                <label>Groupe mère</label>
                                <select name="blood_type" class="form-select">
                                    <option value="">-</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="AB">AB</option>
                                    <option value="O">O</option>
                                </select>
                            </div>
                            <div class="gyneco-field">
                                <label>Rh mère</label>
                                <select name="rh_factor" class="form-select">
                                    <option value="">-</option>
                                    <option value="positive">Rh+</option>
                                    <option value="negative">Rh-</option>
                                </select>
                            </div>
                            <div class="gyneco-field">
                                <label>Groupe père</label>
                                <select name="partner_blood_type" class="form-select">
                                    <option value="">-</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="AB">AB</option>
                                    <option value="O">O</option>
                                </select>
                            </div>
                            <div class="gyneco-field">
                                <label>Rh père</label>
                                <select name="partner_rh_factor" class="form-select">
                                    <option value="">-</option>
                                    <option value="positive">Rh+</option>
                                    <option value="negative">Rh-</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="gyneco-form-section">
                        <h4>Sérologies</h4>
                        <div class="gyneco-form-grid gyneco-form-grid-4">
                            @foreach([
                                'serology_hiv' => 'VIH',
                                'serology_hepatitis_b' => 'Hépatite B',
                                'serology_hepatitis_c' => 'Hépatite C',
                                'serology_syphilis' => 'Syphilis',
                                'serology_toxoplasmosis' => 'Toxoplasmose',
                                'serology_rubella' => 'Rubéole',
                                'serology_cmV' => 'CMV',
                            ] as $field => $label)
                                <div class="gyneco-field">
                                    <label>{{ $label }}</label>
                                    <select name="{{ $field }}" class="form-select">
                                        <option value="">Non fait</option>
                                        <option value="negative">Négatif</option>
                                        <option value="positive">Positif</option>
                                        <option value="immune">Immunisée</option>
                                    </select>
                                </div>
                            @endforeach
                            <div class="gyneco-field">
                                <label>RAI</label>
                                <input type="text" name="rai_result" class="form-control" placeholder="Négatif, Positif...">
                            </div>
                        </div>
                    </div>

                    <div class="gyneco-form-section">
                        <h4>Notes</h4>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Facteurs de risque, observations..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-pink" id="submitPregnancyRecord">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>
