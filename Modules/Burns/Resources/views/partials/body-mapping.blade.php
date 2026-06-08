@props(['admission' => null, 'latestAssessment' => null, 'selectedPatientId' => 0])

<section id="burn-body-mapping" class="card burns-card" data-care-tab-panel="clinical" data-admission-id="{{ $admission?->id ?? '' }}">
    <div class="section-head">
        <h3 class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-red-500"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            {{ __('burns.body_mapping') }}
        </h3>
        <div class="burns-toolbar">
            <button type="button" class="btn btn-sm btn-outline-red" id="resetBodyMapping">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                {{ __('burns.reset') }}
            </button>
        </div>
    </div>

    <div class="body-mapping-container">
        <div class="body-mapping-svg-wrapper">
            <svg id="bodyMappingSVG" viewBox="0 0 400 800" class="body-mapping-svg">
                <g id="bodyRegions">
                    <path id="region-head_face" class="body-region" data-region="head_face" data-percent="9" d="M200,50 m-40,0 a40,50 0 1,0 80,0 a40,50 0 1,0 -80,0"/>
                    <path id="region-neck" class="body-region" data-region="neck" data-percent="2" d="M180,100 h40 v20 h-40 z"/>
                    <path id="region-anterior_trunk" class="body-region" data-region="anterior_trunk" data-percent="13" d="M140,120 h120 v180 h-120 z"/>
                    <path id="region-posterior_trunk" class="body-region" data-region="posterior_trunk" data-percent="13" d="M140,120 h120 v180 h-120 z" opacity="0.3"/>
                    <path id="region-right_arm" class="body-region" data-region="right_arm" data-percent="4" d="M100,130 h40 v100 h-40 z"/>
                    <path id="region-left_arm" class="body-region" data-region="left_arm" data-percent="4" d="M260,130 h40 v100 h-40 z"/>
                    <path id="region-right_forearm_hand" class="body-region" data-region="right_forearm_hand" data-percent="5.5" d="M80,230 h40 v120 h-40 z"/>
                    <path id="region-left_forearm_hand" class="body-region" data-region="left_forearm_hand" data-percent="5.5" d="M280,230 h40 v120 h-40 z"/>
                    <path id="region-right_thigh" class="body-region" data-region="right_thigh" data-percent="9.5" d="M150,300 h50 v150 h-50 z"/>
                    <path id="region-left_thigh" class="body-region" data-region="left_thigh" data-percent="9.5" d="M200,300 h50 v150 h-50 z"/>
                    <path id="region-right_leg_foot" class="body-region" data-region="right_leg_foot" data-percent="7" d="M150,450 h50 v180 h-50 z"/>
                    <path id="region-left_leg_foot" class="body-region" data-region="left_leg_foot" data-percent="7" d="M200,450 h50 v180 h-50 z"/>
                    <path id="region-genitalia" class="body-region" data-region="genitalia" data-percent="1" d="M180,300 h40 v30 h-40 z"/>
                </g>
            </svg>
        </div>

        <div class="body-mapping-controls">
            <div class="tbsa-display">
                <div class="tbsa-label">{{ __('burns.total_burn_surface_area') }}</div>
                <div class="tbsa-value" id="tbsaValue">0%</div>
                <div class="tbsa-severity" id="tbsaSeverity">-</div>
            </div>

            <div class="region-inputs">
                <h4>{{ __('burns.burned_regions') }}</h4>
                <div class="region-inputs-grid">
                    @php
                        $regions = [
                            'head_face' => ['label' => __('burns.head_face'), 'percent' => 9],
                            'neck' => ['label' => __('burns.neck'), 'percent' => 2],
                            'anterior_trunk' => ['label' => __('burns.anterior_trunk'), 'percent' => 13],
                            'posterior_trunk' => ['label' => __('burns.posterior_trunk'), 'percent' => 13],
                            'right_arm' => ['label' => __('burns.right_arm'), 'percent' => 4],
                            'left_arm' => ['label' => __('burns.left_arm'), 'percent' => 4],
                            'right_forearm_hand' => ['label' => __('burns.right_forearm_hand'), 'percent' => 5.5],
                            'left_forearm_hand' => ['label' => __('burns.left_forearm_hand'), 'percent' => 5.5],
                            'right_thigh' => ['label' => __('burns.right_thigh'), 'percent' => 9.5],
                            'left_thigh' => ['label' => __('burns.left_thigh'), 'percent' => 9.5],
                            'right_leg_foot' => ['label' => __('burns.right_leg_foot'), 'percent' => 7],
                            'left_leg_foot' => ['label' => __('burns.left_leg_foot'), 'percent' => 7],
                            'genitalia' => ['label' => __('burns.genitalia'), 'percent' => 1],
                        ];
                    @endphp

                    @foreach($regions as $key => $region)
                        <div class="region-input-item">
                            <label for="region-{{ $key }}">
                                {{ $region['label'] }}
                                <span class="region-max-percent">({{ $region['percent'] }}%)</span>
                            </label>
                            <input type="number"
                                   id="region-{{ $key }}"
                                   class="region-input"
                                   data-region="{{ $key }}"
                                   data-max-percent="{{ $region['percent'] }}"
                                   min="0"
                                   max="{{ $region['percent'] }}"
                                   step="0.5"
                                   value="{{ $latestAssessment?->{$key . '_percent'} ?? 0 }}"
                                   placeholder="0">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="depth-selection">
                <h4>{{ __('burns.burn_depth') }}</h4>
                <div class="depth-options">
                    <label class="depth-option">
                        <input type="radio" name="depth_dominant" value="superficial">
                        <span class="depth-badge depth-superficial">{{ __('burns.first_degree') }}</span>
                    </label>
                    <label class="depth-option">
                        <input type="radio" name="depth_dominant" value="partial_superficial">
                        <span class="depth-badge depth-partial-superficial">{{ __('burns.second_degree_superficial') }}</span>
                    </label>
                    <label class="depth-option">
                        <input type="radio" name="depth_dominant" value="partial_deep">
                        <span class="depth-badge depth-partial-deep">{{ __('burns.second_degree_deep') }}</span>
                    </label>
                    <label class="depth-option">
                        <input type="radio" name="depth_dominant" value="full_thickness">
                        <span class="depth-badge depth-full-thickness">{{ __('burns.third_degree') }}</span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-red" id="submitBodyMapping">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    {{ __('burns.save_assessment') }}
                </button>
            </div>
        </div>
    </div>
</section>

<style>
.burns-card{padding:14px;background:linear-gradient(180deg,#fef2f2 0%,#fee2e2 100%);border:1px solid #fca5a5}
.burns-toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.btn-outline-red{color:#dc2626;border-color:#fca5a5}
.btn-outline-red:hover{background:#fee2e2;border-color:#dc2626;color:#991b1b}
.btn-red{background:#dc2626;color:#fff;border:none;padding:8px 20px;border-radius:10px;font-weight:700;font-size:.88rem;cursor:pointer;display:flex;align-items:center;gap:6px}
.btn-red:hover{background:#991b1b;color:#fff}
.body-mapping-container{display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start}
.body-mapping-svg-wrapper{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px}
.body-mapping-svg{width:100%;height:auto}
.body-region{fill:#e2e8f0;stroke:#94a3b8;stroke-width:2;cursor:pointer;transition:all .2s ease}
.body-region:hover{fill:#fca5a5;stroke:#dc2626}
.body-region.burned{fill:#ef4444;stroke:#991b1b;stroke-width:3}
.body-mapping-controls{display:grid;gap:16px}
.tbsa-display{background:#fff;border:2px solid #dc2626;border-radius:14px;padding:20px;text-align:center}
.tbsa-label{font-size:.78rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px}
.tbsa-value{font-size:3rem;font-weight:900;color:#dc2626;line-height:1}
.tbsa-severity{font-size:.88rem;font-weight:700;margin-top:8px;padding:4px 12px;border-radius:999px;display:inline-block}
.tbsa-severity.mineure{background:#dcfce7;color:#166534}
.tbsa-severity.modérée{background:#fef3c7;color:#92400e}
.tbsa-severity.sévère{background:#fed7aa;color:#9a3412}
.tbsa-severity.critique{background:#fee2e2;color:#991b1b}
.region-inputs,.depth-selection{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px}
.region-inputs h4,.depth-selection h4{font-size:.9rem;font-weight:800;color:#0f172a;margin:0 0 12px;padding-bottom:8px;border-bottom:1px solid #e2e8f0}
.region-inputs-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
.region-input-item{display:grid;gap:4px}
.region-input-item label{font-size:.78rem;font-weight:700;color:#334155}
.region-max-percent{font-size:.72rem;color:#94a3b8;font-weight:600}
.region-input{border-radius:8px;border:1px solid #e2e8f0;padding:6px 10px;font-size:.88rem;font-weight:600}
.region-input:focus{border-color:#dc2626;outline:none;box-shadow:0 0 0 3px rgba(220,38,38,.1)}
.region-input:invalid{border-color:#dc2626;background:#fef2f2}
.depth-options{display:grid;gap:8px}
.depth-option{display:flex;align-items:center;gap:10px;cursor:pointer}
.depth-option input[type="radio"]{width:18px;height:18px;accent-color:#dc2626}
.depth-badge{padding:6px 14px;border-radius:8px;font-size:.82rem;font-weight:700;border:2px solid transparent}
.depth-superficial{background:#fef3c7;color:#92400e;border-color:#fcd34d}
.depth-partial-superficial{background:#fed7aa;color:#9a3412;border-color:#fb923c}
.depth-partial-deep{background:#fecaca;color:#991b1b;border-color:#f87171}
.depth-full-thickness{background:#7f1d1d;color:#fff;border-color:#450a0a}
.form-actions{display:flex;justify-content:flex-end}
@media (max-width:1200px){.body-mapping-container{grid-template-columns:1fr}.region-inputs-grid{grid-template-columns:1fr}}
</style>
