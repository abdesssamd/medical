@extends('layouts.admin')

@section('title', 'Examen RIS')
@section('page-title', 'Fiche examen RIS')

@php
    $statusPillClass = match ($order->status) {
        \Modules\RIS\Models\RisOrder::STATUS_ORDONNE => 'risx-pill-status-ordonne',
        \Modules\RIS\Models\RisOrder::STATUS_EN_ATTENTE => 'risx-pill-status-en_attente',
        \Modules\RIS\Models\RisOrder::STATUS_IMAGES_RECUES => 'risx-pill-status-images_recues',
        \Modules\RIS\Models\RisOrder::STATUS_TERMINE => 'risx-pill-status-termine',
        \Modules\RIS\Models\RisOrder::STATUS_ANNULE => 'risx-pill-status-annule',
        default => 'risx-pill-status-ordonne',
    };

    $isLocked = $order->status === \Modules\RIS\Models\RisOrder::STATUS_TERMINE;
    $payload = (array) ($order->orthanc_payload ?? []);

    $orthancStudyId = data_get($payload, 'orthanc_study_id')
        ?? data_get($payload, 'reconciliation.matched_study.study_id')
        ?? data_get($payload, 'webhook_orthanc_study_id')
        ?? null;

    $studyUuid = data_get($payload, 'study_uid')
        ?? data_get($payload, 'reconciliation.matched_study.study_instance_uid')
        ?? data_get($payload, 'webhook_tags.StudyInstanceUID')
        ?? data_get($payload, 'webhook_tags.0020,000D')
        ?? data_get($payload, 'webhook_last_body.study_uid')
        ?? data_get($payload, 'webhook_last_body.StudyInstanceUID')
        ?? null;

    $viewerStudyId = $studyUuid ?: $orthancStudyId;
    $orthancViewerBaseUrl = rtrim((string) config('ris.orthanc.viewer_base_url', config('ris.orthanc.base_url', config('services.orthanc.base_url', 'http://127.0.0.1:8042'))), '/');
    $viewerUrl = $viewerStudyId ? $orthancViewerBaseUrl.'/ohif/viewer?StudyInstanceUIDs='.urlencode((string) $viewerStudyId) : null;

    $timelineSteps = [
        ['label' => 'Demande', 'done' => true, 'value' => optional($order->requested_at)->format('d/m/Y H:i') ?: 'Non renseigne'],
        ['label' => 'Debut workflow', 'done' => (bool) $order->started_at || in_array($order->status, [\Modules\RIS\Models\RisOrder::STATUS_EN_ATTENTE, \Modules\RIS\Models\RisOrder::STATUS_IMAGES_RECUES, \Modules\RIS\Models\RisOrder::STATUS_TERMINE], true), 'value' => optional($order->started_at)->format('d/m/Y H:i') ?: 'Non demarre'],
        ['label' => 'Reception images', 'done' => (bool) $order->received_at || in_array($order->status, [\Modules\RIS\Models\RisOrder::STATUS_IMAGES_RECUES, \Modules\RIS\Models\RisOrder::STATUS_TERMINE], true), 'value' => optional($order->received_at)->format('d/m/Y H:i') ?: 'Aucune image recue'],
        ['label' => 'Cloture', 'done' => (bool) $order->completed_at || $order->status === \Modules\RIS\Models\RisOrder::STATUS_TERMINE, 'value' => optional($order->completed_at)->format('d/m/Y H:i') ?: 'Non termine'],
    ];

    $reportEditorInitialHtml = old('report_text', $order->report?->content ?? '');
@endphp

@section('content')
<div class="risx-page">
    @if($errors->any())
        <div class="risx-alert risx-alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @foreach(['success', 'warning', 'error'] as $flashType)
        @if(session($flashType))
            <div class="risx-alert risx-alert-{{ $flashType }}">{{ session($flashType) }}</div>
        @endif
    @endforeach

    <div class="risx-workbench">
        <aside class="risx-sidebar">
            <section class="risx-card risx-side-card">
                <div class="risx-patient-head">
                    <h3>{{ $order->patient?->full_name ?? 'Patient' }}</h3>
                    <div class="risx-side-meta"><strong>MRN</strong> {{ $order->patient?->medical_record_number ?? '-' }}</div>
                    <div class="risx-side-meta"><strong>Telephone:</strong> {{ $order->patient?->phone ?? '-' }}</div>
                    <div class="risx-side-meta"><strong>Modalite:</strong> {{ $order->modality?->name ?? '-' }}</div>
                    <div class="risx-side-meta"><strong>Planning:</strong> {{ optional($order->scheduled_at)->format('d/m/Y H:i') ?: 'Non planifie' }}</div>
                    <div class="risx-side-meta"><strong>Demandeur:</strong> {{ $order->requestedBy?->name ?? '-' }}</div>
                </div>

                @if($order->patient_id)
                    <a href="{{ route('clinical.patient.show', $order->patient_id) }}" class="risx-btn risx-btn-wide">Ouvrir dossier clinique</a>
                @else
                    <button type="button" class="risx-btn risx-btn-wide" disabled>Ouvrir dossier clinique</button>
                @endif

                <div class="risx-side-divider"></div>

                <h4>Historique patient</h4>
                @forelse($patientHistory ?? [] as $historyOrder)
                    <a href="{{ route('ris.exams.show', $historyOrder) }}" class="risx-history-item">
                        <strong>{{ $historyOrder->procedure?->label ?? 'Examen RIS' }}</strong>
                        <span>{{ optional($historyOrder->requested_at)->format('d/m/Y') }} - {{ $historyOrder->status_label }}</span>
                    </a>
                @empty
                    <p class="risx-muted">Aucun antecedent RIS pour ce patient.</p>
                @endforelse
            </section>

            <section class="risx-card risx-side-card">
                <h4>Timeline workflow</h4>
                <div class="risx-timeline">
                    @foreach($timelineSteps as $step)
                        <div class="risx-timeline-item {{ $step['done'] ? 'is-done' : '' }}">
                            <span class="risx-timeline-dot"></span>
                            <div>
                                <strong>{{ $step['label'] }}</strong>
                                <span>{{ $step['value'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="risx-card risx-side-card">
                <h4>Actions</h4>
                <form method="POST" action="{{ route('ris.exams.waiting', $order) }}">
                    @csrf
                    @method('PATCH')
                    <button class="risx-btn risx-btn-wide risx-btn-warning" type="submit">Passer en attente</button>
                </form>
                <form method="POST" action="{{ route('ris.exams.images-received', $order) }}">
                    @csrf
                    @method('PATCH')
                    <button class="risx-btn risx-btn-wide risx-btn-info" type="submit">Marquer images recues</button>
                </form>
                @if($order->status !== \Modules\RIS\Models\RisOrder::STATUS_TERMINE && $order->status !== \Modules\RIS\Models\RisOrder::STATUS_ANNULE)
                    <form method="POST" action="{{ route('ris.exams.complete', $order) }}">
                        @csrf
                        @method('PATCH')
                        <button class="risx-btn risx-btn-wide risx-btn-success" type="submit">Terminer & cloturer</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('ris.exams.worklist', $order) }}">
                    @csrf
                    <button class="risx-btn risx-btn-wide" type="submit">Synchroniser</button>
                </form>
                <form method="POST" action="{{ route('ris.exams.cancel', $order) }}">
                    @csrf
                    @method('PATCH')
                    <label>Motif d'annulation</label>
                    <input class="risx-input" name="cancelled_reason" placeholder="Patient absent, doublon...">
                    <button class="risx-btn risx-btn-danger" type="submit">Annuler</button>
                </form>
            </section>
            @if($portalAccess)
            <section class="risx-card risx-side-card" style="border-color: #86efac;">
                <h4 style="display:flex;align-items:center;gap:8px;">
                    <i class="ti ti-door-enter" style="color:#166534;"></i>
                    Portail patient
                </h4>
                <div style="font-size:0.84rem;display:grid;gap:6px;">
                    <div><strong>Statut:</strong>
                        @if($portalAccess->revoked_at)
                            <span style="color:#991b1b;">Révoqué</span>
                        @elseif($portalAccess->verified_at)
                            <span style="color:#166534;">Utilisé ({{ $portalAccess->verified_at->format('d/m/Y H:i') }})</span>
                        @elseif($portalAccess->isExpired())
                            <span style="color:#92400e;">Expiré</span>
                        @else
                            <span style="color:#166534;">Actif</span>
                        @endif
                    </div>
                    <div><strong>Code:</strong> <code style="font-size:1.1rem;letter-spacing:2px;background:#f1f5f9;padding:2px 8px;border-radius:6px;">{{ $portalAccess->access_code_last4 ? str_repeat('•', 4).$portalAccess->access_code_last4 : '—' }}</code></div>
                    <div><strong>Expire le:</strong> {{ optional($portalAccess->expires_at)->format('d/m/Y') ?: '—' }}</div>
                    <div><strong>Email:</strong> {{ $portalAccess->delivery_email ?: '—' }}</div>
                    <div><strong>Tel:</strong> {{ $portalAccess->delivery_phone ?: '—' }}</div>
                    @if($portalAccess->delivery_email)
                        <div style="color:#64748b;font-size:0.78rem;">Un email a été envoyé avec le code d'accès.</div>
                    @else
                        <div style="color:#92400e;font-size:0.78rem;">Aucun email — imprimez le mémo pour remettre le code au patient.</div>
                    @endif
                    <div style="display:flex;gap:8px;margin-top:6px;">
                        <a href="{{ route('patient-portal.admin.show', $portalAccess) }}" class="risx-btn risx-btn-wide" target="_blank">Voir l'accès</a>
                        <a href="{{ route('patient-portal.admin.memo', $portalAccess) }}" class="risx-btn risx-btn-wide" target="_blank">Imprimer mémo</a>
                    </div>
                </div>
            </section>
            @endif
        </aside>

        <main class="risx-main">
            <section class="risx-card">
                <div class="risx-header">
                    <div>
                        <h3>{{ $order->patient?->full_name ?? 'Patient' }}</h3>
                        <div class="risx-chip-row">Statut: <span class="{{ $statusPillClass }}">{{ $order->status_label }}</span></div>
                    </div>
                    <div class="risx-chip-row">
                        <a href="{{ route('ris.exams.index') }}" class="risx-btn">Retour</a>
                    </div>
                </div>

                <div class="risx-grid12 risx-panel">
                    <div class="risx-col-3">
                        <div class="risx-stack">
                            <div>
                                <label>TEMPLATE</label>
                                <select id="report_template_id" name="report_template_id" class="risx-select">
                                    <option value="">-- Aucun --</option>
                                    @foreach($reportTemplates ?? [] as $tpl)
                                        <option value="{{ $tpl->id }}" data-template-content="{{ e($tpl->content) }}">{{ $tpl->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label>INSERER UN CHAMP</label>
                                <select id="report_insert_field" class="risx-select">
                                    <option value="">-- Inserer --</option>
                                    <option value="[NOM_PATIENT]">Nom patient</option>
                                    <option value="[AGE]">Age</option>
                                    <option value="[DATE_EXAMEN]">Date examen</option>
                                    <option value="[MODALITE]">Modalite</option>
                                </select>
                            </div>

                            <div>
                                <button id="templateManagerButton" type="button" class="risx-btn risx-btn-wide">Gerer templates</button>
                            </div>
                        </div>
                    </div>

                    <div class="risx-col-9">
                        <form id="ris-report-form" method="POST" action="{{ route('ris.exams.report', $order) }}">
                            @csrf
                            @method('PUT')

                            <div class="risx-editor-shell risx-card">
                                <div class="risx-editor-toolbar">
                                    <button type="button" data-editor-command="bold" class="risx-btn" title="Gras"><i class="ti ti-bold"></i></button>
                                    <button type="button" data-editor-command="italic" class="risx-btn" title="Italique"><i class="ti ti-italic"></i></button>
                                    <button type="button" data-editor-command="underline" class="risx-btn" title="Souligné"><i class="ti ti-underline"></i></button>
                                    <span class="risx-sep"></span>
                                    <button type="button" data-editor-command="insertUnorderedList" class="risx-btn" title="Liste"><i class="ti ti-list"></i></button>
                                    <button type="button" data-editor-command="insertOrderedList" class="risx-btn" title="Liste numérotée"><i class="ti ti-list-numbers"></i></button>
                                    <span class="risx-sep"></span>
                                    <button type="button" data-editor-command="justifyLeft" class="risx-btn" title="Aligner à gauche"><i class="ti ti-align-left"></i></button>
                                    <button type="button" data-editor-command="justifyCenter" class="risx-btn" title="Centrer"><i class="ti ti-align-center"></i></button>
                                    <button type="button" data-editor-command="justifyRight" class="risx-btn" title="Aligner à droite"><i class="ti ti-align-right"></i></button>
                                    <span class="risx-sep"></span>
                                    <button type="button" data-editor-command="undo" class="risx-btn" title="Annuler"><i class="ti ti-arrow-back-up"></i></button>
                                    <button type="button" data-editor-command="redo" class="risx-btn" title="Refaire"><i class="ti ti-arrow-forward-up"></i></button>
                                    <span class="risx-sep"></span>
                                    <select id="editorBlockFormat" class="risx-select risx-select-inline" @disabled($isLocked)>
                                        <option value="">Style</option>
                                        <option value="p">Paragraphe</option>
                                        <option value="h2">Titre</option>
                                        <option value="h3">Sous-titre</option>
                                    </select>
                                    <button type="button" data-editor-snippet="normal" class="risx-btn" title="Snippet"><i class="ti ti-code-plus"></i></button>
                                    <span class="risx-sep"></span>
                                    <div style="margin-left:auto; display:flex; gap:6px;">
                                        <button type="button" id="wordTemplateImportButton" class="risx-btn" @disabled($isLocked) title="Charger modèle Word"><i class="ti ti-file-import"></i></button>
                                        <button type="button" id="wordExportButton" class="risx-btn" title="Exporter .docx"><i class="ti ti-file-export"></i></button>
                                        <button type="button" id="editorFullscreenButton" class="risx-btn" title="Plein écran"><i class="ti ti-maximize"></i></button>
                                        <button type="button" id="voiceDictationButton" class="risx-btn" @disabled($isLocked) title="Dictee"><i class="ti ti-microphone"></i></button>
                                        <button type="button" id="aiAssistButton" data-ai-url="{{ route('ris.exams.ai.analyze', $order) }}" class="risx-btn risx-btn-ai" @disabled($isLocked) title="Analyser IA"><i class="ti ti-stars"></i></button>
                                    </div>
                                </div>

                                <div class="risx-editor" id="report_editor" contenteditable="{{ $isLocked ? 'false' : 'true' }}">{!! $reportEditorInitialHtml !!}</div>
                                <textarea id="report_text" name="report_text" hidden>{{ old('report_text', $order->report?->content) }}</textarea>
                                <input id="word_template_input" type="file" accept=".docx" hidden>
                            </div>

                            <div class="risx-floating-actions">
                                @unless($isLocked)
                                    <button type="submit" class="risx-btn risx-btn-primary">Enregistrer</button>
                                @endunless

                                @if($order->report)
                                    <a href="{{ route('ris.exams.report.pdf', $order) }}" class="risx-btn">Aperçu PDF</a>
                                    <button type="submit" formaction="{{ route('ris.exams.send-report', $order) }}" formmethod="POST" formnovalidate class="risx-btn">Envoyer</button>
                                @else
                                    <button type="button" class="risx-btn" disabled>Aperçu PDF</button>
                                    <button type="button" class="risx-btn" disabled>Envoyer</button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <section class="risx-card risx-viewer-box" id="risxViewerBox">
                <div class="risx-viewer-toolbar">
                    <span class="risx-viewer-label">OHIF Viewer</span>
                    <button type="button" id="viewerFullscreenButton" class="risx-btn" title="Ouvrir en plein écran"><i class="ti ti-maximize"></i> Plein écran</button>
                </div>
                <div id="orthancViewerFrameContainer">
                    @if($viewerUrl)
                        <iframe src="{{ $viewerUrl }}" frameborder="0"></iframe>
                    @else
                        <div style="padding:24px;">Aucun viewer disponible.</div>
                    @endif
                </div>
            </section>
        </main>
    </div>
</div>

<style>
    .risx-page { display: grid; gap: 16px; color: #0f172a; }
    .risx-alert { border-radius: 10px; padding: 10px 12px; font-weight: 800; font-size: 0.86rem; }
    .risx-alert-success { border: 1px solid #86efac; background: #dcfce7; color: #166534; }
    .risx-alert-warning { border: 1px solid #fdba74; background: #fff7ed; color: #9a3412; }
    .risx-alert-error, .risx-alert-danger { border: 1px solid #fecaca; background: #fee2e2; color: #991b1b; }
    .risx-workbench { display: grid; grid-template-columns: 216px minmax(0, 1fr); gap: 14px; align-items: start; }
    .risx-sidebar { display: grid; gap: 12px; }
    .risx-main { display: grid; gap: 16px; min-width: 0; }

    .risx-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06); }
    .risx-side-card { padding: 12px; }
    .risx-side-card h4 { margin: 0 0 10px; font-size: 0.88rem; font-weight: 900; }

    .risx-patient-head h3 { margin: 0 0 10px; font-size: 0.92rem; font-weight: 900; line-height: 1.25; }
    .risx-side-meta { margin: 0 0 7px; color: #475569; font-size: 0.78rem; line-height: 1.3; }
    .risx-side-meta strong { color: #334155; }
    .risx-muted { margin: 0; color: #64748b; font-size: 0.78rem; line-height: 1.45; }
    .risx-side-divider { height: 1px; margin: 12px -12px; background: #e2e8f0; }

    .risx-history-item { display: grid; gap: 2px; padding: 8px 0; border-top: 1px solid #eef2f7; color: inherit; text-decoration: none; }
    .risx-history-item strong { font-size: 0.78rem; }
    .risx-history-item span { color: #64748b; font-size: 0.74rem; }

    .risx-timeline { display: grid; gap: 10px; }
    .risx-timeline-item { display: grid; grid-template-columns: 12px minmax(0, 1fr); gap: 8px; color: #64748b; font-size: 0.78rem; }
    .risx-timeline-dot { width: 7px; height: 7px; margin-top: 4px; border-radius: 999px; background: #94a3b8; box-shadow: 0 0 0 3px #f1f5f9; }
    .risx-timeline-item.is-done .risx-timeline-dot { background: #2563eb; box-shadow: 0 0 0 3px #dbeafe; }
    .risx-timeline-item strong { display: block; color: #334155; font-size: 0.78rem; }
    .risx-timeline-item span { display: block; margin-top: 2px; }

    .risx-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding: 16px 18px; border-bottom: 1px solid #e2e8f0; }
    .risx-header h3 { margin: 0 0 8px; font-size: 1.05rem; font-weight: 800; }
    .risx-chip-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 0.86rem; }

    .risx-pill-status-ordonne, .risx-pill-status-en_attente, .risx-pill-status-images_recues, .risx-pill-status-termine, .risx-pill-status-annule { display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 999px; font-size: 0.78rem; font-weight: 800; }
    .risx-pill-status-ordonne { background: #e0f2fe; color: #075985; }
    .risx-pill-status-en_attente { background: #fef3c7; color: #92400e; }
    .risx-pill-status-images_recues { background: #dcfce7; color: #166534; }
    .risx-pill-status-termine { background: #e0e7ff; color: #3730a3; }
    .risx-pill-status-annule { background: #fee2e2; color: #991b1b; }

    .risx-panel { padding: 16px 18px; }
    .risx-grid12 { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 16px; align-items: start; }
    .risx-col-3 { grid-column: span 3; }
    .risx-col-9 { grid-column: span 9; }

    .risx-stack { display: grid; gap: 12px; }
    .risx-stack label { display: block; margin-bottom: 5px; color: #64748b; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; }

    .risx-select, .risx-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; background: #fff; font-size: 0.8rem; }
    .risx-select-inline { width: auto; min-width: 130px; }

    .risx-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 34px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 7px 12px; background: #f8fafc; color: #0f172a; font-size: 0.84rem; font-weight: 800; text-decoration: none; cursor: pointer; }
    .risx-btn:hover { border-color: #93c5fd; background: #eff6ff; color: #1d4ed8; }
    .risx-btn-primary { border-color: #2563eb; background: #2563eb; color: #fff; }
    .risx-btn-wide { width: 100%; }
    .risx-btn-warning { border-color: #fdba74; background: #fff7ed; color: #9a3412; }
    .risx-btn-info { border-color: #93c5fd; background: #dbeafe; color: #1d4ed8; }
    .risx-btn-danger { border-color: #fecaca; background: #fee2e2; color: #991b1b; margin-top: 8px; }
    .risx-btn-success { border-color: #86efac; background: #dcfce7; color: #166534; }
    .risx-btn-ai { border-color: #7c3aed; background: #7c3aed; color: #fff; }
    .risx-btn-ai:hover { background: #6d28d9; border-color: #6d28d9; color: #fff; }
    .risx-btn:disabled { opacity: 0.55; cursor: not-allowed; }

    .risx-editor-shell { overflow: hidden; }
    .risx-editor-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; padding: 10px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
    .risx-sep { width: 1px; height: 24px; background: #e2e8f0; display: inline-block; flex: 0 0 auto; }
    .risx-editor { width: 100%; min-height: 260px; padding: 14px; background: #fff; border: 0; outline: none; overflow: auto; font: inherit; line-height: 1.5; }
    .risx-editor[contenteditable="false"] { background: #f1f5f9; color: #64748b; cursor: not-allowed; }

    body.risx-no-scroll { overflow: hidden; }
    .risx-editor-shell.is-fullscreen {
        position: fixed;
        inset: 0;
        z-index: 9999;
        border-radius: 0;
        border: 0;
        box-shadow: none;
        display: grid;
        grid-template-rows: auto minmax(0, 1fr);
        background: #fff;
    }
    .risx-editor-shell.is-fullscreen .risx-editor {
        min-height: 100%;
        height: 100%;
        font-size: 1rem;
        padding: 18px;
    }

    .risx-floating-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; padding: 10px 0; }

    .risx-viewer-box { min-height: 520px; padding: 12px; }
    .risx-viewer-toolbar { display: flex; align-items: center; justify-content: space-between; padding: 6px 0 10px; }
    .risx-viewer-label { font-size: 0.84rem; font-weight: 800; color: #334155; }
    #orthancViewerFrameContainer { width: 100%; height: min(72vh, 760px); min-height: 520px; border-radius: 10px; overflow: hidden; background: #eef4fb; display: grid; }
    #orthancViewerFrameContainer iframe { width: 100%; height: 100%; border: 0; background: #111827; }
    .risx-viewer-box.is-fullscreen {
        position: fixed; inset: 0; z-index: 9999; border-radius: 0; border: 0;
        display: grid; grid-template-rows: auto minmax(0, 1fr); padding: 8px 12px; background: #000;
    }
    .risx-viewer-box.is-fullscreen .risx-viewer-toolbar { padding: 6px 4px 10px; }
    .risx-viewer-box.is-fullscreen .risx-viewer-label { color: #fff; }
    .risx-viewer-box.is-fullscreen #orthancViewerFrameContainer { height: 100%; min-height: 0; border-radius: 0; }
    .risx-viewer-box.is-fullscreen #orthancViewerFrameContainer iframe { background: #000; }

    @media (max-width: 1100px) {
        .risx-workbench, .risx-grid12 { grid-template-columns: 1fr; }
        .risx-col-3, .risx-col-9 { grid-column: auto; }
        .risx-header, .risx-floating-actions { align-items: stretch; flex-direction: column; }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const canEdit = {{ $isLocked ? 'false' : 'true' }};
        const templateSelect = document.getElementById('report_template_id');
        const insertFieldSelect = document.getElementById('report_insert_field');
        const reportTextarea = document.getElementById('report_text');
        const reportEditorEl = document.getElementById('report_editor');
        const reportForm = document.getElementById('ris-report-form');
        const voiceButton = document.getElementById('voiceDictationButton');
        const aiButton = document.getElementById('aiAssistButton');
        const templateManagerButton = document.getElementById('templateManagerButton');
        const blockFormatSelect = document.getElementById('editorBlockFormat');
        const fullscreenButton = document.getElementById('editorFullscreenButton');
        const editorShell = document.querySelector('.risx-editor-shell');
        const wordImportButton = document.getElementById('wordTemplateImportButton');
        const wordTemplateInput = document.getElementById('word_template_input');
        const wordExportButton = document.getElementById('wordExportButton');
        const editorButtons = document.querySelectorAll('[data-editor-command], [data-editor-snippet]');

        const risPatient = {
            full_name: @json($order->patient?->full_name ?? ''),
            age: @json(optional($order->patient)->age ?? ''),
            modality: @json($order->modality?->name ?? ''),
            scheduled_at: @json(optional($order->scheduled_at)->format('d/m/Y H:i') ?? ''),
        };

        let savedRange = null;
        let isFullscreen = false;

        const ensureScript = (src, globalName) => new Promise((resolve, reject) => {
            if (globalName && window[globalName]) {
                resolve(window[globalName]);
                return;
            }

            const existing = document.querySelector(`script[src="${src}"]`);
            if (existing) {
                existing.addEventListener('load', () => resolve(globalName ? window[globalName] : true), { once: true });
                existing.addEventListener('error', () => reject(new Error(`Chargement impossible: ${src}`)), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = () => resolve(globalName ? window[globalName] : true);
            script.onerror = () => reject(new Error(`Chargement impossible: ${src}`));
            document.head.appendChild(script);
        });

        const getContent = () => reportEditorEl?.innerHTML || '';

        const syncHiddenReport = () => {
            if (reportTextarea) {
                reportTextarea.value = getContent();
            }
        };

        const focusEditor = () => {
            reportEditorEl?.focus();
        };

        const rememberSelection = () => {
            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0 || !reportEditorEl?.contains(selection.anchorNode)) {
                return;
            }

            savedRange = selection.getRangeAt(0).cloneRange();
        };

        const restoreSelection = () => {
            if (!savedRange) {
                return false;
            }

            const selection = window.getSelection();
            if (!selection) {
                return false;
            }

            selection.removeAllRanges();
            selection.addRange(savedRange);
            return true;
        };

        const placeCaretAtEnd = () => {
            if (!reportEditorEl) return;
            const range = document.createRange();
            range.selectNodeContents(reportEditorEl);
            range.collapse(false);
            const selection = window.getSelection();
            selection?.removeAllRanges();
            selection?.addRange(range);
            savedRange = range.cloneRange();
        };

        const setContent = (html) => {
            if (!reportEditorEl) return;
            reportEditorEl.innerHTML = html || '';
            syncHiddenReport();
        };

        const toggleFullscreen = () => {
            if (!editorShell) return;
            isFullscreen = !isFullscreen;
            editorShell.classList.toggle('is-fullscreen', isFullscreen);
            document.body.classList.toggle('risx-no-scroll', isFullscreen);
            if (fullscreenButton) {
                fullscreenButton.textContent = isFullscreen ? 'Quitter plein ecran' : 'Plein ecran';
            }
            focusEditor();
        };

        const insertContent = (html) => {
            if (!canEdit || !reportEditorEl) return;
            focusEditor();
            if (!restoreSelection()) {
                placeCaretAtEnd();
            }

            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) {
                reportEditorEl.insertAdjacentHTML('beforeend', html);
                syncHiddenReport();
                return;
            }

            const range = selection.getRangeAt(0);
            range.deleteContents();
            const holder = document.createElement('div');
            holder.innerHTML = html;
            const fragment = document.createDocumentFragment();
            let lastNode = null;

            while (holder.firstChild) {
                lastNode = fragment.appendChild(holder.firstChild);
            }

            range.insertNode(fragment);

            if (lastNode) {
                range.setStartAfter(lastNode);
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
                savedRange = range.cloneRange();
            }

            syncHiddenReport();
        };

        reportEditorEl?.addEventListener('input', syncHiddenReport);
        reportEditorEl?.addEventListener('keyup', syncHiddenReport);
        reportEditorEl?.addEventListener('mouseup', rememberSelection);
        reportEditorEl?.addEventListener('keyup', rememberSelection);
        reportEditorEl?.addEventListener('focus', rememberSelection);
        reportForm?.addEventListener('submit', syncHiddenReport);
        syncHiddenReport();

        blockFormatSelect?.addEventListener('change', () => {
            if (!canEdit) return;
            const tag = blockFormatSelect.value;
            if (!tag) return;
            restoreSelection();
            focusEditor();
            document.execCommand('formatBlock', false, tag);
            syncHiddenReport();
            rememberSelection();
            blockFormatSelect.selectedIndex = 0;
        });

        templateSelect?.addEventListener('change', () => {
            if (!canEdit) return;
            const selectedOption = templateSelect.options[templateSelect.selectedIndex];
            let templateContent = selectedOption?.dataset?.templateContent || '';
            if (!templateContent) return;
            templateContent = templateContent
                .replace(/\[NOM_PATIENT\]/g, risPatient.full_name || '')
                .replace(/\[AGE\]/g, risPatient.age || '')
                .replace(/\[DATE_EXAMEN\]/g, risPatient.scheduled_at || '')
                .replace(/\[MODALITE\]/g, risPatient.modality || '');
            setContent(templateContent);
        });

        insertFieldSelect?.addEventListener('change', () => {
            if (!canEdit) return;
            const val = insertFieldSelect.value;
            if (!val) return;
            insertContent(`<strong>${val}</strong>`);
            insertFieldSelect.selectedIndex = 0;
        });

        editorButtons.forEach(btn => btn.addEventListener('mousedown', (e) => {
            if (!canEdit) return;
            e.preventDefault();
            const command = btn.dataset.editorCommand;
            const snippet = btn.dataset.editorSnippet;

            if (command) {
                restoreSelection();
                focusEditor();
                document.execCommand(command, false, null);
                syncHiddenReport();
                rememberSelection();
                return;
            }

            if (snippet === 'normal') {
                insertContent('<p><strong>Conclusion :</strong> Examen sans anomalie significative.</p>');
            }
        }));

        fullscreenButton?.addEventListener('click', () => {
            toggleFullscreen();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isFullscreen) {
                toggleFullscreen();
            }
        });

        const viewerBox = document.getElementById('risxViewerBox');
        const viewerFullscreenButton = document.getElementById('viewerFullscreenButton');
        let isViewerFullscreen = false;

        const toggleViewerFullscreen = () => {
            isViewerFullscreen = !isViewerFullscreen;
            viewerBox?.classList.toggle('is-fullscreen', isViewerFullscreen);
            document.body.classList.toggle('risx-no-scroll', isViewerFullscreen);
            if (viewerFullscreenButton) {
                viewerFullscreenButton.innerHTML = isViewerFullscreen
                    ? '<i class="ti ti-minimize"></i> Quitter plein écran'
                    : '<i class="ti ti-maximize"></i> Plein écran';
            }
        };

        viewerFullscreenButton?.addEventListener('click', toggleViewerFullscreen);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isViewerFullscreen) {
                toggleViewerFullscreen();
            }
        });

        wordImportButton?.addEventListener('click', () => {
            if (!canEdit) return;
            wordTemplateInput?.click();
        });

        wordTemplateInput?.addEventListener('change', async (event) => {
            if (!canEdit) return;
            const file = event.target?.files?.[0];
            if (!file) return;

            if (!/\.docx$/i.test(file.name)) {
                alert('Veuillez charger un fichier .docx.');
                event.target.value = '';
                return;
            }

            try {
                await ensureScript('https://unpkg.com/mammoth@1.8.0/mammoth.browser.min.js', 'mammoth');
                const arrayBuffer = await file.arrayBuffer();
                const result = await window.mammoth.convertToHtml({ arrayBuffer });
                const html = (result?.value || '').trim();
                if (!html) {
                    alert('Le modele Word est vide ou non reconnu.');
                } else {
                    setContent(html);
                    placeCaretAtEnd();
                }
            } catch (error) {
                alert('Import Word impossible. Verifiez la connexion et le format du fichier.');
            } finally {
                event.target.value = '';
            }
        });

        wordExportButton?.addEventListener('click', async () => {
            try {
                await ensureScript('https://cdnjs.cloudflare.com/ajax/libs/html-docx-js/0.3.1/html-docx.min.js', 'htmlDocx');
                await ensureScript('https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js', 'saveAs');

                const html = getContent().trim();
                if (!html) {
                    alert('Aucun contenu a exporter.');
                    return;
                }

                const wrappedHtml = `<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>${html}</body></html>`;
                const blob = window.htmlDocx.asBlob(wrappedHtml);
                window.saveAs(blob, `rapport-ris-{{ $order->id }}.docx`);
            } catch (error) {
                alert('Export Word impossible pour le moment.');
            }
        });

        aiButton?.addEventListener('click', async () => {
            if (!canEdit) return;
            const currentContent = getContent();
            const mode = currentContent.trim() ? 'correction' : 'pre_report';
            const aiUrl = aiButton.dataset.aiUrl;
            if (!aiUrl) { alert('Configuration IA manquante.'); return; }
            const originalLabel = aiButton.textContent;
            aiButton.textContent = 'Analyse...';
            aiButton.disabled = true;
            try {
                const response = await fetch(aiUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ content: currentContent, mode: mode }),
                });
                if (!response.ok) { alert('L\'assistant IA n\'a pas pu traiter le compte-rendu.'); return; }
                const payload = await response.json();
                const suggestion = payload.corrected_html || payload.pre_report_html || '';
                const message = [payload.summary || '', payload.conclusion || '', (payload.suggestions || []).join('\n')].filter(Boolean).join('\n\n');
                if (confirm((message ? message + '\n\n' : '') + 'Appliquer la proposition IA ?') && suggestion) {
                    setContent(suggestion);
                }
            } catch (e) {
                alert('Erreur réseau IA.');
            } finally {
                aiButton.textContent = originalLabel;
                aiButton.disabled = false;
            }
        });

        voiceButton?.addEventListener('click', () => {
            if (!canEdit) return;
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) {
                alert('Reconnaissance vocale non disponible. Utilisez Chrome ou Edge.');
                return;
            }
            if (voiceButton.dataset.listening === '1') {
                return;
            }
            const recognition = new SpeechRecognition();
            recognition.lang = 'fr-FR';
            recognition.interimResults = true;
            recognition.continuous = false;
            voiceButton.dataset.listening = '1';

            let lastTranscript = '';
            recognition.onstart = () => { voiceButton.innerHTML = '<i class="ti ti-microphone"></i> Écoute...'; };
            recognition.onend = () => {
                voiceButton.innerHTML = '<i class="ti ti-microphone"></i>';
                voiceButton.dataset.listening = '0';
            };
            recognition.onresult = (event) => {
                const results = Array.from(event.results);
                const finalTranscript = results
                    .filter(r => r.isFinal)
                    .map(r => r[0]?.transcript || '')
                    .join('').trim();
                if (finalTranscript && finalTranscript !== lastTranscript) {
                    insertContent(`<span>${finalTranscript} </span>`);
                    lastTranscript = finalTranscript;
                }
            };
            recognition.onerror = (event) => {
                voiceButton.innerHTML = '<i class="ti ti-microphone"></i>';
                voiceButton.dataset.listening = '0';
                if (event.error === 'not-allowed') {
                    alert('Autorisation du micro refusee. Autorisez le micro dans les parametres du navigateur.');
                } else if (event.error === 'no-speech') {
                    // silence, l'utilisateur n'a rien dit
                } else if (event.error === 'aborted') {
                    // ignore
                } else {
                    console.warn('Dictée erreur:', event.error);
                }
            };
            try {
                recognition.start();
            } catch (e) {
                voiceButton.innerHTML = '<i class="ti ti-microphone"></i>';
                voiceButton.dataset.listening = '0';
                alert('Impossible de demarrer la dictee. Verifiez le micro et les permissions.');
            }
        });

        templateManagerButton?.addEventListener('click', () => {
            window.location.href = @json(route('ris.templates.index'));
        });
    });
</script>

@endsection