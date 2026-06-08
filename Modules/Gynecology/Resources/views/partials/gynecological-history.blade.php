@props(['history' => null, 'selectedPatientId' => 0])

<section id="gynecological-history" class="card gyneco-card" data-care-tab-panel="clinical">
    <div class="section-head d-flex justify-content-between align-items-center">
        <h3 class="mb-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-pink-500"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
            Antécédents Gynéco-Obstétriques
        </h3>
        <button type="button" class="btn btn-sm btn-outline-pink" data-bs-toggle="modal" data-bs-target="#gynecologicalHistoryModal">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ $history ? 'Modifier' : '+ Nouveaux Antécédents' }}
        </button>
    </div>

    @if($history)
        @php
            $cancers = $history->family_history_cancers ?? [];
            $conditions = $history->gynecological_conditions ?? [];
            $cancerLabels = ['breast' => 'Sein', 'ovarian' => 'Ovaire', 'endometrial' => 'Endomètre', 'cervical' => 'Col utérin', 'colorectal' => 'Colorectal'];
            $conditionLabels = ['endometriosis' => 'Endométriose', 'pcos' => 'SOPK', 'fibroids' => 'Myomes', 'adenomyosis' => 'Adénomyose', 'pid' => 'MST/Infection pelvienne'];
            $regularityLabel = match($history->cycle_regularity) {
                'regular' => 'Régulier',
                'irregular' => 'Irrégulier',
                'amenorrhea' => 'Aménorrhée',
                default => '-',
            };
        @endphp
        <div class="table-responsive">
            <table class="table table-sm gyneco-summary-table align-middle mb-0">
                <tbody>
                    <tr>
                        <td class="gyneco-cat fw-bold" style="width:180px">Profil GPA</td>
                        <td>
                            <strong>G:</strong> {{ $history->gestity ?? 0 }}
                            &nbsp;/&nbsp;
                            <strong>P:</strong> {{ $history->parity ?? 0 }}
                            &nbsp;/&nbsp;
                            <strong>A:</strong> {{ $history->abortions ?? 0 }}
                            &nbsp;/&nbsp;
                            <strong>Enfants vivants:</strong> {{ $history->living_children ?? 0 }}
                            @if($history->cesarean_sections)
                                &nbsp;/&nbsp;
                                <strong>Césarienne:</strong> {{ $history->cesarean_sections }}
                            @endif
                            @if($history->ectopic_pregnancies)
                                &nbsp;/&nbsp;
                                <strong>GEU:</strong> {{ $history->ectopic_pregnancies }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="gyneco-cat fw-bold">Menstruations</td>
                        <td>
                            Cycle <span class="badge {{ $history->cycle_regularity === 'regular' ? 'bg-green-lt' : ($history->cycle_regularity === 'irregular' ? 'bg-yellow-lt' : 'bg-red-lt') }}">{{ $regularityLabel }}</span>
                            @if($history->cycle_duration_days)
                                (Durée : <strong>{{ $history->cycle_duration_days }}j</strong>
                                @if($history->menstruation_duration_days)
                                    , Règles : <strong>{{ $history->menstruation_duration_days }}j</strong>
                                @endif
                                )
                            @endif
                            @if($history->menarche_age)
                                . Ménarche : <strong>{{ $history->menarche_age }} ans</strong>
                            @endif
                            @if($history->last_menstrual_period)
                                . DDR : <strong>{{ $history->last_menstrual_period->format('d/m/Y') }}</strong>
                            @endif
                            @if($history->contraception_method)
                                . Contraception : <em>{{ $history->contraception_method }}</em>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="gyneco-cat fw-bold">Suivi FCV</td>
                        <td>
                            @if($history->last_fcv_date)
                                FCV effectué le <strong>{{ $history->last_fcv_date->format('d/m/Y') }}</strong>
                                @if($history->last_fcv_result)
                                    — Résultat : <em>{{ $history->last_fcv_result }}</em>
                                @endif
                            @else
                                <span class="text-secondary">Aucun FCV enregistré</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="gyneco-cat fw-bold">Pathologies & Cancers</td>
                        <td>
                            @if(count($conditions) > 0)
                                @foreach($conditions as $code)
                                    <span class="badge bg-pink-lt me-1">{{ $conditionLabels[$code] ?? $code }}</span>
                                @endforeach
                            @endif
                            @if(count($conditions) > 0 && count($cancers) > 0)
                                <span class="mx-1 text-secondary">|</span>
                            @endif
                            @if(count($cancers) > 0)
                                @foreach($cancers as $code)
                                    <span class="badge bg-purple-lt me-1">{{ $cancerLabels[$code] ?? $code }}</span>
                                @endforeach
                            @endif
                            @if(count($conditions) === 0 && count($cancers) === 0)
                                <span class="text-secondary">Aucune pathologie ni antécédent familial</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="gyneco-cat fw-bold">Notes</td>
                        <td>{{ $history->notes ?: 'Aucune note' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <div class="gyneco-empty-state text-center py-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-pink-300 mb-2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
            <div class="text-secondary">Aucun antécédent renseigné. Cliquez sur <strong>+ Nouveaux Antécédents</strong> pour les ajouter.</div>
        </div>
    @endif
</section>

<div class="modal fade" id="gynecologicalHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#fdf2f8,#fce7f3);border-bottom:1px solid #f9a8d4">
                <h5 class="modal-title" style="font-weight:800;color:#be185d">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                    Dossier Gynéco-Obstétrique : Antécédents
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="gynecologicalHistoryForm" class="gyneco-form">
                    @csrf
                    <input type="hidden" name="id" value="{{ $history?->id }}">

                    <div class="gyneco-form-section">
                        <h4>Formule GPA</h4>
                        <div class="gyneco-form-grid gyneco-form-grid-4">
                            <div class="gyneco-field">
                                <label>Gestité (G)</label>
                                <input type="number" name="gestity" min="0" max="30" value="{{ old('gestity', $history?->gestity ?? 0) }}" class="form-control" required>
                                <span class="gyneco-hint">Nombre total de grossesses</span>
                            </div>
                            <div class="gyneco-field">
                                <label>Parité (P)</label>
                                <input type="number" name="parity" min="0" max="30" value="{{ old('parity', $history?->parity ?? 0) }}" class="form-control" required>
                                <span class="gyneco-hint">Accouchements ≥ 22 SA</span>
                            </div>
                            <div class="gyneco-field">
                                <label>Avortements (A)</label>
                                <input type="number" name="abortions" min="0" max="30" value="{{ old('abortions', $history?->abortions ?? 0) }}" class="form-control" required>
                                <span class="gyneco-hint">Spontanés ou provoqués</span>
                            </div>
                            <div class="gyneco-field">
                                <label>Enfants vivants (V)</label>
                                <input type="number" name="living_children" min="0" max="30" value="{{ old('living_children', $history?->living_children ?? 0) }}" class="form-control" required>
                            </div>
                        </div>
                        <div class="gyneco-form-grid gyneco-form-grid-2 mt-2">
                            <div class="gyneco-field">
                                <label>Césariennes</label>
                                <input type="number" name="cesarean_sections" min="0" max="30" value="{{ old('cesarean_sections', $history?->cesarean_sections ?? 0) }}" class="form-control">
                            </div>
                            <div class="gyneco-field">
                                <label>Grossesses extra-utérines</label>
                                <input type="number" name="ectopic_pregnancies" min="0" max="10" value="{{ old('ectopic_pregnancies', $history?->ectopic_pregnancies ?? 0) }}" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="gyneco-form-section">
                        <h4>Cycle menstruel</h4>
                        <div class="gyneco-form-grid gyneco-form-grid-4">
                            <div class="gyneco-field">
                                <label>Âge ménarche</label>
                                <input type="number" name="menarche_age" min="8" max="20" value="{{ old('menarche_age', $history?->menarche_age) }}" class="form-control" placeholder="ans">
                            </div>
                            <div class="gyneco-field">
                                <label>Durée cycle (jours)</label>
                                <input type="number" name="cycle_duration_days" min="15" max="60" value="{{ old('cycle_duration_days', $history?->cycle_duration_days) }}" class="form-control">
                            </div>
                            <div class="gyneco-field">
                                <label>Durée règles (jours)</label>
                                <input type="number" name="menstruation_duration_days" min="1" max="15" value="{{ old('menstruation_duration_days', $history?->menstruation_duration_days) }}" class="form-control">
                            </div>
                            <div class="gyneco-field">
                                <label>Régularité</label>
                                <select name="cycle_regularity" class="form-select">
                                    <option value="regular" {{ ($history?->cycle_regularity ?? 'regular') === 'regular' ? 'selected' : '' }}>Régulier</option>
                                    <option value="irregular" {{ ($history?->cycle_regularity) === 'irregular' ? 'selected' : '' }}>Irrégulier</option>
                                    <option value="amenorrhea" {{ ($history?->cycle_regularity) === 'amenorrhea' ? 'selected' : '' }}>Aménorrhée</option>
                                </select>
                            </div>
                        </div>
                        <div class="gyneco-form-grid gyneco-form-grid-3 mt-2">
                            <div class="gyneco-field">
                                <label>Méthode contraceptive</label>
                                <input type="text" name="contraception_method" value="{{ old('contraception_method', $history?->contraception_method) }}" class="form-control" placeholder="Pilule, DIU, aucune...">
                            </div>
                            <div class="gyneco-field">
                                <label>Dernières règles (DDR)</label>
                                <input type="date" name="last_menstrual_period" value="{{ old('last_menstrual_period', $history?->last_menstrual_period?->format('Y-m-d')) }}" class="form-control">
                            </div>
                            <div class="gyneco-field">
                                <label>Âge ménopause</label>
                                <input type="number" name="menopause_age" min="30" max="65" value="{{ old('menopause_age', $history?->menopause_age) }}" class="form-control" placeholder="ans">
                            </div>
                        </div>
                    </div>

                    <div class="gyneco-form-section">
                        <h4>Frottis cervico-vaginal</h4>
                        <div class="gyneco-form-grid gyneco-form-grid-2">
                            <div class="gyneco-field">
                                <label>Date dernier FCV</label>
                                <input type="date" name="last_fcv_date" value="{{ old('last_fcv_date', $history?->last_fcv_date?->format('Y-m-d')) }}" class="form-control">
                            </div>
                            <div class="gyneco-field">
                                <label>Résultat</label>
                                <input type="text" name="last_fcv_result" value="{{ old('last_fcv_result', $history?->last_fcv_result) }}" class="form-control" placeholder="Normal, ASC-US, LSIL...">
                            </div>
                        </div>
                    </div>

                    <div class="gyneco-form-section">
                        <h4>Antécédents familiaux de cancers</h4>
                        <div class="gyneco-chips" id="familyCancerChips">
                            @php $cancers = $history?->family_history_cancers ?? []; @endphp
                            @foreach(['breast' => 'Sein', 'ovarian' => 'Ovaire', 'endometrial' => 'Endomètre', 'cervical' => 'Col utérin', 'colorectal' => 'Colorectal'] as $code => $label)
                                <label class="gyneco-chip {{ in_array($code, $cancers) ? 'active' : '' }}">
                                    <input type="checkbox" name="family_history_cancers[]" value="{{ $code }}" {{ in_array($code, $cancers) ? 'checked' : '' }} hidden>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="gyneco-form-section">
                        <h4>Pathologies gynécologiques</h4>
                        <div class="gyneco-chips" id="gynecoConditionsChips">
                            @php $conditions = $history?->gynecological_conditions ?? []; @endphp
                            @foreach(['endometriosis' => 'Endométriose', 'pcos' => 'SOPK', 'fibroids' => 'Myomes', 'adenomyosis' => 'Adénomyose', 'pid' => 'MST/Infection pelvienne'] as $code => $label)
                                <label class="gyneco-chip {{ in_array($code, $conditions) ? 'active' : '' }}">
                                    <input type="checkbox" name="gynecological_conditions[]" value="{{ $code }}" {{ in_array($code, $conditions) ? 'checked' : '' }} hidden>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="gyneco-form-section">
                        <h4>Notes</h4>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Notes complémentaires...">{{ old('notes', $history?->notes) }}</textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-pink" id="submitGynecologicalHistory">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.gyneco-summary-table{font-size:.88rem}
.gyneco-summary-table td{padding:10px 14px;border-bottom:1px solid #f1f5f9;vertical-align:top}
.gyneco-summary-table tr:last-child td{border-bottom:none}
.gyneco-cat{color:#be185d;background:linear-gradient(90deg,#fdf2f8 0%,#fff 100%);white-space:nowrap}
.gyneco-empty-state{color:#64748b}
.gyneco-empty-state strong{color:#be185d}
.gyneco-form{display:grid;gap:16px}
.gyneco-form-section{padding:14px;border:1px solid #f1f5f9;border-radius:14px;background:#fafbfc}
.gyneco-form-section h4{font-size:.9rem;font-weight:800;color:#0f172a;margin:0 0 12px;padding-bottom:8px;border-bottom:1px solid #e2e8f0}
.gyneco-form-grid{display:grid;gap:10px}
.gyneco-form-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
.gyneco-form-grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.gyneco-form-grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
.gyneco-field label{display:block;font-size:.78rem;font-weight:700;color:#334155;margin-bottom:4px}
.gyneco-field .form-control,.gyneco-field .form-select{border-radius:10px;border-color:#e2e8f0;font-size:.88rem}
.gyneco-field .form-control:focus,.gyneco-field .form-select:focus{border-color:#ec4899;box-shadow:0 0 0 3px rgba(236,72,153,.12)}
.gyneco-hint{display:block;font-size:.72rem;color:#94a3b8;margin-top:2px}
.gyneco-chips{display:flex;gap:8px;flex-wrap:wrap}
.gyneco-chip{padding:6px 14px;border-radius:999px;border:1px solid #e2e8f0;background:#fff;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .15s ease;user-select:none}
.gyneco-chip:hover{border-color:#f9a8d4;background:#fdf2f8}
.gyneco-chip.active{border-color:#ec4899;background:#fce7f3;color:#be185d}
.btn-pink{background:#ec4899;color:#fff;border:none;padding:8px 20px;border-radius:10px;font-weight:700;font-size:.88rem;cursor:pointer;display:flex;align-items:center;gap:6px}
.btn-pink:hover{background:#db2777;color:#fff}
.btn-outline-pink{color:#ec4899;border-color:#f9a8d4}
.btn-outline-pink:hover{background:#fce7f3;border-color:#ec4899;color:#be185d}
@media (max-width:768px){.gyneco-form-grid-4,.gyneco-form-grid-3,.gyneco-form-grid-2{grid-template-columns:1fr}}
</style>
