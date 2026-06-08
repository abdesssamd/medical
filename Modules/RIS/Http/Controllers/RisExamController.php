<?php

namespace Modules\RIS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\RIS\Models\RisModality;
use Modules\RIS\Models\RisOrder;
use Modules\RIS\Models\RisProcedure;
use Modules\RIS\Models\RisReport;
use Modules\RIS\Models\RisReportTemplate;
use Modules\RIS\Services\OrthancService;
use Modules\RIS\Services\RisReportService;

class RisExamController extends Controller
{
    public function index(Request $request, OrthancService $orthancService): View
    {
        $this->ensureCatalog();

        $selectedPatientId = (int) $request->session()->get('ris.selected_patient_id', 0);
        $selectedPatient = $selectedPatientId > 0
            ? Patient::query()->find($selectedPatientId)
            : null;

        $filters = [
            'search' => trim((string) $request->string('search')),
            'status' => trim((string) $request->string('status')),
            'modality_id' => $request->integer('modality_id') ?: null,
            'priority' => trim((string) $request->string('priority')),
            'patient_id' => $selectedPatient?->id,
        ];

        $orders = RisOrder::query()
            ->with(['patient', 'procedure', 'modality', 'report', 'requestedBy'])
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['priority'] !== '', fn ($query) => $query->where('priority', $filters['priority']))
            ->when($filters['modality_id'], fn ($query, $modalityId) => $query->where('modality_id', $modalityId))
            ->when($filters['patient_id'], fn ($query, $patientId) => $query->where('patient_id', $patientId))
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('accession_number', 'like', '%'.$search.'%')
                        ->orWhere('clinical_indication', 'like', '%'.$search.'%')
                        ->orWhereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery
                                ->where('first_name', 'like', '%'.$search.'%')
                                ->orWhere('last_name', 'like', '%'.$search.'%')
                                ->orWhere('medical_record_number', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('procedure', fn ($procedureQuery) => $procedureQuery->where('label', 'like', '%'.$search.'%'));
                });
            })
            ->latest('requested_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $statsForPatient = fn ($query) => $selectedPatient
            ? $query->where('patient_id', $selectedPatient->id)
            : $query;

        $stats = [
            'total' => $statsForPatient(RisOrder::query())->count(),
            'today' => $statsForPatient(RisOrder::query())->whereDate('requested_at', now()->toDateString())->count(),
            'waiting' => $statsForPatient(RisOrder::query())->where('status', RisOrder::STATUS_EN_ATTENTE)->count(),
            'received' => $statsForPatient(RisOrder::query())->where('status', RisOrder::STATUS_IMAGES_RECUES)->count(),
            'done' => $statsForPatient(RisOrder::query())->where('status', RisOrder::STATUS_TERMINE)->count(),
            'urgent' => $statsForPatient(RisOrder::query())->whereIn('priority', [RisOrder::PRIORITY_URGENT, RisOrder::PRIORITY_STAT])->count(),
        ];

        $recentReports = RisReport::query()
            ->with(['order.patient', 'order.procedure', 'signingPhysician'])
            ->latest('validated_at')
            ->latest('id')
            ->limit(5)
            ->get();

        $modalities = RisModality::query()->orderBy('name')->get();
        $procedures = RisProcedure::query()->orderBy('label')->get();

        $patients = Patient::query()
            ->select(['id', 'first_name', 'last_name', 'medical_record_number', 'phone'])
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($subQuery) use ($filters): void {
                    $subQuery
                        ->where('first_name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('last_name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('medical_record_number', 'like', '%'.$filters['search'].'%');
                });
            })
            ->latest('id')
            ->limit(50)
            ->get();

        $requesters = User::query()
            ->whereIn('role', ['professional', 'doctor', 'medecin', 'admin', 'super_admin'])
            ->orderBy('name')
            ->get(['id', 'name', 'professional_title', 'role']);

        return view('ris::exams.index', [
            'orders' => $orders,
            'filters' => $filters,
            'stats' => $stats,
            'recentReports' => $recentReports,
            'orthancStatus' => $orthancService->checkServerStatus(),
            'modalities' => $modalities,
            'procedures' => $procedures,
            'patients' => $patients,
            'selectedPatient' => $selectedPatient,
            'requesters' => $requesters,
            'statusLabels' => RisOrder::statusLabels(),
            'priorityLabels' => RisOrder::priorityLabels(),
        ]);
    }

    public function searchPatients(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $patients = Patient::query()
            ->select(['id', 'first_name', 'last_name', 'medical_record_number', 'date_of_birth', 'patient_photo_path', 'phone'])
            ->where(function ($query) use ($term): void {
                $query
                    ->where('first_name', 'like', '%'.$term.'%')
                    ->orWhere('last_name', 'like', '%'.$term.'%')
                    ->orWhere('medical_record_number', 'like', '%'.$term.'%')
                    ->orWhere('cin', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(8)
            ->get()
            ->map(fn (Patient $patient): array => $this->formatPatientResult($patient));

        return response()->json(['results' => $patients]);
    }

    public function spotlight(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));

        if (mb_strlen($term) < 2) {
            return response()->json([
                'patients' => [],
                'orders' => [],
                'actions' => $this->quickActions(),
            ]);
        }

        $patients = Patient::query()
            ->select(['id', 'first_name', 'last_name', 'medical_record_number', 'date_of_birth', 'patient_photo_path', 'phone'])
            ->where(function ($query) use ($term): void {
                $query
                    ->where('first_name', 'like', '%'.$term.'%')
                    ->orWhere('last_name', 'like', '%'.$term.'%')
                    ->orWhere('medical_record_number', 'like', '%'.$term.'%')
                    ->orWhere('cin', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(6)
            ->get()
            ->map(fn (Patient $patient): array => $this->formatPatientResult($patient))
            ->values();

        $orders = RisOrder::query()
            ->with(['patient:id,first_name,last_name,medical_record_number', 'procedure:id,label'])
            ->where(function ($query) use ($term): void {
                $query
                    ->where('accession_number', 'like', '%'.$term.'%')
                    ->orWhere('clinical_indication', 'like', '%'.$term.'%')
                    ->orWhereHas('patient', function ($patientQuery) use ($term): void {
                        $patientQuery
                            ->where('first_name', 'like', '%'.$term.'%')
                            ->orWhere('last_name', 'like', '%'.$term.'%')
                            ->orWhere('medical_record_number', 'like', '%'.$term.'%');
                    })
                    ->orWhereHas('procedure', fn ($procedureQuery) => $procedureQuery->where('label', 'like', '%'.$term.'%'));
            })
            ->latest('requested_at')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(function (RisOrder $order): array {
                return [
                    'id' => $order->id,
                    'label' => $order->procedure?->label ?? 'Examen RIS',
                    'subtitle' => trim(($order->patient?->full_name ?? 'Patient').' | '.($order->accession_number ?: 'RIS-'.$order->id)),
                    'status' => $order->status,
                    'status_label' => $order->status_label,
                    'url' => route('ris.exams.show', $order),
                ];
            })
            ->values();

        return response()->json([
            'patients' => $patients,
            'orders' => $orders,
            'actions' => $this->filterQuickActions($term),
        ]);
    }

    public function liveSnapshot(Request $request): JsonResponse
    {
        $selectedPatientId = (int) $request->session()->get('ris.selected_patient_id', 0);
        $ids = collect($request->input('ids', []))
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $statsQuery = RisOrder::query();
        if ($selectedPatientId > 0) {
            $statsQuery->where('patient_id', $selectedPatientId);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'today' => (clone $statsQuery)->whereDate('requested_at', now()->toDateString())->count(),
            'waiting' => (clone $statsQuery)->where('status', RisOrder::STATUS_EN_ATTENTE)->count(),
            'received' => (clone $statsQuery)->where('status', RisOrder::STATUS_IMAGES_RECUES)->count(),
            'done' => (clone $statsQuery)->where('status', RisOrder::STATUS_TERMINE)->count(),
            'urgent' => (clone $statsQuery)->whereIn('priority', [RisOrder::PRIORITY_URGENT, RisOrder::PRIORITY_STAT])->count(),
        ];

        $orders = $ids->isEmpty()
            ? collect()
            : RisOrder::query()
                ->whereIn('id', $ids->all())
                ->get(['id', 'status', 'priority', 'updated_at', 'received_at'])
                ->map(fn (RisOrder $order): array => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'status_label' => $order->status_label,
                    'priority' => $order->priority,
                    'priority_label' => $order->priority_label,
                    'updated_at' => optional($order->updated_at)->toIso8601String(),
                    'received_at' => optional($order->received_at)->toIso8601String(),
                ]);

        return response()->json([
            'stats' => $stats,
            'orders' => $orders->values(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function selectPatient(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
        ]);

        $request->session()->put('ris.selected_patient_id', (int) $validated['patient_id']);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'redirect' => route('ris.exams.index'),
            ]);
        }

        return redirect()->route('ris.exams.index');
    }

    public function clearPatient(Request $request): RedirectResponse
    {
        $request->session()->forget('ris.selected_patient_id');

        return redirect()->route('ris.exams.index');
    }

    public function show(RisOrder $order, OrthancService $orthancService): View
    {
        $this->ensureCatalog();

        $order->load(['patient', 'procedure', 'modality', 'report.signingPhysician', 'requestedBy']);

        $patientId = (string) ($order->patient?->medical_record_number ?: $order->patient_id);
        $patientStudies = $patientId !== '' ? $orthancService->getPatientStudies($patientId) : ['ok' => false, 'studies' => []];
        $patientHistory = RisOrder::query()
            ->with(['procedure', 'modality', 'report'])
            ->where('patient_id', $order->patient_id)
            ->whereKeyNot($order->getKey())
            ->latest('requested_at')
            ->latest('id')
            ->limit(8)
            ->get();
        $reportTemplates = RisReportTemplate::query()
            ->select(['id', 'title', 'category', 'content'])
            ->orderBy('category')
            ->orderBy('title')
            ->get();

        return view('ris::exams.show', [
            'order' => $order,
            'patientStudies' => $patientStudies,
            'patientHistory' => $patientHistory,
            'reportTemplates' => $reportTemplates,
            'statusLabels' => RisOrder::statusLabels(),
            'priorityLabels' => RisOrder::priorityLabels(),
            'requesters' => User::query()
                ->whereIn('role', ['professional', 'doctor', 'medecin', 'admin', 'super_admin'])
                ->orderBy('name')
                ->get(['id', 'name', 'professional_title']),
        ]);
    }

    public function previousReport(RisOrder $order): JsonResponse
    {
        $previous = RisOrder::query()
            ->with(['report'])
            ->where('patient_id', $order->patient_id)
            ->whereKeyNot($order->getKey())
            ->whereHas('report', fn ($query) => $query->whereNotNull('content'))
            ->latest('requested_at')
            ->latest('id')
            ->first();

        if (! $previous || ! $previous->report) {
            return response()->json([
                'ok' => false,
                'message' => 'Aucun compte-rendu precedent disponible pour ce patient.',
            ]);
        }

        return response()->json([
            'ok' => true,
            'order_id' => $previous->id,
            'requested_at' => optional($previous->requested_at)->format('d/m/Y H:i'),
            'procedure' => $previous->procedure?->label,
            'content' => (string) $previous->report->content,
        ]);
    }

    public function store(Request $request, OrthancService $orthancService): RedirectResponse
    {
        $this->ensureCatalog();

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'procedure_id' => ['required', 'integer', 'exists:ris_procedures,id'],
            'modality_id' => ['required', 'integer', 'exists:ris_modalities,id'],
            'priority' => ['required', 'string', 'in:routine,urgent,stat'],
            'clinical_indication' => ['nullable', 'string', 'max:4000'],
            'requested_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'requested_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'sync_to_orthanc' => ['nullable', 'boolean'],
        ]);

        $order = DB::transaction(function () use ($validated): RisOrder {
            return RisOrder::query()->create([
                'patient_id' => $validated['patient_id'],
                'procedure_id' => $validated['procedure_id'],
                'modality_id' => $validated['modality_id'],
                'accession_number' => $this->generateAccessionNumber(),
                'priority' => $validated['priority'],
                'clinical_indication' => $validated['clinical_indication'] ?? null,
                'requested_by_user_id' => $validated['requested_by_user_id'] ?? Auth::id(),
                'status' => RisOrder::STATUS_ORDONNE,
                'requested_at' => isset($validated['requested_at']) ? Carbon::parse($validated['requested_at']) : now(),
                'scheduled_at' => isset($validated['scheduled_at']) ? Carbon::parse($validated['scheduled_at']) : null,
                'orthanc_payload' => [
                    'source' => 'ris_module',
                    'created_by' => Auth::id(),
                ],
            ]);
        });

        if ((bool) ($validated['sync_to_orthanc'] ?? false)) {
            $this->dispatchWorklist($order, $orthancService);
        }

        return redirect()
            ->route('ris.exams.show', $order)
            ->with('success', 'Examen RIS cree avec succes.');
    }

    public function syncSelectedPatientWithOrthanc(Request $request, OrthancService $orthancService): RedirectResponse
    {
        $patientId = (int) ($request->input('patient_id') ?: $request->session()->get('ris.selected_patient_id', 0));

        if ($patientId <= 0) {
            return back()->with('error', "Selectionnez d'abord un patient avant la synchronisation PACS.");
        }

        $patient = Patient::query()->findOrFail($patientId);
        $request->session()->put('ris.selected_patient_id', $patient->id);

        $orders = RisOrder::query()
            ->with(['patient', 'procedure', 'modality', 'requestedBy'])
            ->where('patient_id', $patient->id)
            ->whereIn('status', [RisOrder::STATUS_ORDONNE, RisOrder::STATUS_EN_ATTENTE])
            ->orderBy('requested_at')
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('warning', 'Aucun examen RIS actif a synchroniser pour ce patient.');
        }

        $updated = 0;
        $inspected = 0;

        foreach ($orders as $order) {
            $inspected++;
            $result = $orthancService->reconcileOrderFromOrthanc($order);

            if ($result['matched'] ?? false) {
                $updated++;
            }
        }

        if ($updated === 0) {
            return back()->with('warning', "Aucune etude Orthanc n'a confirme ce patient ({$patient->medical_record_number}).");
        }

        return back()->with('success', "Synchronisation PACS terminee pour {$patient->medical_record_number}: {$updated} examen(s) mis a jour sur {$inspected} examine(s).");
    }

    public function markAsWaiting(RisOrder $order): RedirectResponse
    {
        $order->forceFill([
            'status' => RisOrder::STATUS_EN_ATTENTE,
            'started_at' => $order->started_at ?? now(),
        ])->save();

        return back()->with('success', 'Examen passe en attente de realisation.');
    }

    public function markAsImagesReceived(RisOrder $order): RedirectResponse
    {
        $order->forceFill([
            'status' => RisOrder::STATUS_IMAGES_RECUES,
            'received_at' => $order->received_at ?? now(),
        ])->save();

        return back()->with('success', 'Examen marque comme images recues.');
    }

    public function updatePriority(Request $request, RisOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'priority' => ['required', 'string', 'in:routine,urgent,stat'],
        ]);

        $order->forceFill([
            'priority' => $validated['priority'],
        ])->save();

        return back()->with('success', 'Priorite RIS mise a jour.');
    }

    public function saveReport(Request $request, RisOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'report_text' => ['required', 'string'],
            'signing_physician_id' => ['nullable', 'integer', 'exists:users,id'],
            'validated_at' => ['nullable', 'date'],
            'severity_tag' => ['nullable', 'string', 'in:none,urgent,critical'],
            'signature_name' => ['nullable', 'string', 'max:120'],
        ]);

        $physician = ! empty($validated['signing_physician_id'])
            ? User::query()->find($validated['signing_physician_id'])
            : null;

        $report = RisReport::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'content' => $this->normalizeReportContent($validated['report_text']),
                'signing_physician_id' => $validated['signing_physician_id'] ?? null,
                'signing_physician_name' => $physician?->display_name ?: $physician?->name,
                'validated_at' => isset($validated['validated_at']) ? Carbon::parse($validated['validated_at']) : ($order->report?->validated_at),
            ]
        );

        $payload = (array) ($order->orthanc_payload ?? []);
        $payload['ui_meta'] = array_merge((array) data_get($payload, 'ui_meta', []), [
            'severity_tag' => $validated['severity_tag'] ?? data_get($payload, 'ui_meta.severity_tag', 'none'),
            'signature_name' => $validated['signature_name'] ?? data_get($payload, 'ui_meta.signature_name'),
            'signature_at' => ($validated['signature_name'] ?? '') !== '' ? now()->toIso8601String() : data_get($payload, 'ui_meta.signature_at'),
            'report_state' => $report->validated_at ? 'validated' : 'draft',
        ]);

        $order->forceFill(['orthanc_payload' => $payload])->save();

        return back()->with('success', 'Compte rendu RIS enregistre.');
    }

    public function sendReportCopy(RisOrder $order, RisReportService $reportService): RedirectResponse
    {
        if (! $order->report || ! $order->report->validated_at) {
            return back()->with('warning', "Le compte-rendu doit être signé avant l'envoi par email.");
        }

        try {
            $delivery = $reportService->sendSignedReport($order);
        } catch (\Throwable $exception) {
            return back()->with('error', 'Envoi impossible: '.$exception->getMessage());
        }

        $queued = (int) ($delivery['queued'] ?? 0);

        if ($queued <= 0) {
            return back()->with('warning', 'Aucun destinataire email valide trouve pour ce compte-rendu.');
        }

        return back()->with('success', "Envoi programme pour {$queued} destinataire(s). Le compte-rendu signe sera expédie par file d'attente.");
    }

    private function normalizeReportContent(string $content): string
    {
        $content = trim($content);

        if ($content === '') {
            return '';
        }

        return strip_tags($content, '<p><br><strong><b><em><i><ul><ol><li><table><thead><tbody><tr><th><td><div><span>');
    }

    public function markAsCompleted(Request $request, RisOrder $order, RisReportService $reportService): RedirectResponse
    {
        $validated = $request->validate([
            'report_text' => ['nullable', 'string'],
            'signing_physician_id' => ['nullable', 'integer', 'exists:users,id'],
            'validated_at' => ['nullable', 'date'],
            'send_copy' => ['nullable', 'boolean'],
        ]);

        $physician = ! empty($validated['signing_physician_id'])
            ? User::query()->find($validated['signing_physician_id'])
            : Auth::user();

        $reportService->signReport($order, $physician, $validated['report_text'] ?? null, (bool) ($validated['send_copy'] ?? false));

        return back()->with('success', 'Compte rendu signe, examen verrouille et lien patient genere.');
    }

    public function signReport(Request $request, RisOrder $order, RisReportService $reportService): RedirectResponse
    {
        $validated = $request->validate([
            'send_copy' => ['nullable', 'boolean'],
            'severity_tag' => ['nullable', 'string', 'in:none,urgent,critical'],
            'signature_name' => ['nullable', 'string', 'max:120'],
        ]);

        $payload = (array) ($order->orthanc_payload ?? []);
        $payload['ui_meta'] = array_merge((array) data_get($payload, 'ui_meta', []), [
            'severity_tag' => $validated['severity_tag'] ?? data_get($payload, 'ui_meta.severity_tag', 'none'),
            'signature_name' => $validated['signature_name'] ?? data_get($payload, 'ui_meta.signature_name'),
            'signature_at' => ($validated['signature_name'] ?? '') !== '' ? now()->toIso8601String() : data_get($payload, 'ui_meta.signature_at'),
            'report_state' => 'archived',
        ]);

        $order->forceFill(['orthanc_payload' => $payload])->save();

        $reportService->signReport($order, Auth::user(), null, (bool) ($validated['send_copy'] ?? false));

        return back()->with('success', 'Compte rendu signe, examen verrouille et lien patient genere.');
    }

    public function reportPdf(RisOrder $order, RisReportService $reportService)
    {
        return $reportService->generatePdfResponse($order);
    }

    public function sharedReport(string $token, RisReportService $reportService)
    {
        $report = RisReport::query()
            ->with(['order.patient', 'order.procedure', 'order.modality', 'order.report.signingPhysician', 'order.requestedBy'])
            ->where('share_token', $token)
            ->firstOrFail();

        abort_if($report->share_expires_at && $report->share_expires_at->isPast(), 410, 'Lien expire.');

        $order = $report->order;
        $payload = (array) ($order->orthanc_payload ?? []);
        $studyId = data_get($payload, 'study_uid')
            ?? data_get($payload, 'reconciliation.matched_study.study_instance_uid')
            ?? data_get($payload, 'orthanc_study_id')
            ?? data_get($payload, 'reconciliation.matched_study.study_id');
        $viewerBaseUrl = rtrim((string) config('ris.orthanc.viewer_base_url', config('ris.orthanc.base_url', config('services.orthanc.base_url', 'http://127.0.0.1:8042'))), '/');
        $viewerUrl = $studyId ? $viewerBaseUrl.'/stone-webviewer/index.html?study='.urlencode((string) $studyId) : null;

        return view('ris::reports.shared', [
            'report' => $report,
            'order' => $order,
            'viewerUrl' => $viewerUrl,
            'sealCode' => 'RIS-VALID-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
        ]);
    }

    public function cancel(Request $request, RisOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'cancelled_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $order->forceFill([
            'status' => RisOrder::STATUS_ANNULE,
            'cancelled_at' => now(),
            'cancelled_reason' => $validated['cancelled_reason'] ?? null,
        ])->save();

        return back()->with('success', 'Examen RIS annule.');
    }

    public function syncWorklist(RisOrder $order, OrthancService $orthancService): RedirectResponse
    {
        $reconciliation = $orthancService->reconcileOrderFromOrthanc($order);

        if ($reconciliation['matched'] ?? false) {
            return back()->with('success', 'Images PACS recuperees depuis Orthanc pour cet examen.');
        }

        if (($reconciliation['ok'] ?? true) === false) {
            return back()->with('error', 'Synchronisation PACS impossible: '.($reconciliation['message'] ?? 'Orthanc inaccessible.'));
        }

        $result = $this->dispatchWorklist($order, $orthancService);

        if (! ($result['result']['ok'] ?? false)) {
            return back()->with('error', 'Aucune etude PACS trouvee pour ce patient, et synchronisation worklist impossible: '.($result['result']['message'] ?? 'Erreur inconnue'));
        }

        return back()->with('success', 'Aucune etude PACS existante trouvee; ordre RIS envoye vers la worklist Orthanc.');
    }

    private function dispatchWorklist(RisOrder $order, OrthancService $orthancService): array
    {
        $order->loadMissing(['patient', 'procedure', 'modality', 'requestedBy']);

        $result = $orthancService->createModalityWorklistForOrder($order);
        $payload = (array) ($order->orthanc_payload ?? []);
        $payload['worklist'] = $result;
        $payload['last_worklist_sync_at'] = now()->toIso8601String();

        $order->forceFill([
            'status' => ($result['result']['ok'] ?? false) ? RisOrder::STATUS_EN_ATTENTE : $order->status,
            'orthanc_payload' => $payload,
        ])->save();

        return $result;
    }

    private function ensureCatalog(): void
    {
        if (RisModality::query()->count() === 0) {
            RisModality::query()->insert([
                [
                    'name' => 'Radio intra-orale',
                    'type' => RisModality::TYPE_RADIO,
                    'ae_title' => 'INTRAORAL_AE',
                    'ip_address' => '127.0.0.1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Panoramique dentaire',
                    'type' => RisModality::TYPE_PANORAMIQUE,
                    'ae_title' => 'PANO_AE',
                    'ip_address' => '127.0.0.1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'CBCT',
                    'type' => RisModality::TYPE_SCANNER,
                    'ae_title' => 'CBCT_AE',
                    'ip_address' => '127.0.0.1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (RisProcedure::query()->count() === 0) {
            RisProcedure::query()->insert([
                ['code' => 'RX-RETRO', 'label' => 'Retro-alveolaire', 'price' => 120, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'RX-PANO', 'label' => 'Panoramique', 'price' => 250, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'RX-CBCT', 'label' => 'Cone Beam CT', 'price' => 900, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'RX-TM', 'label' => 'ATM / articulation temporo-mandibulaire', 'price' => 350, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    private function generateAccessionNumber(): string
    {
        do {
            $candidate = 'RIS-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
        } while (RisOrder::query()->where('accession_number', $candidate)->exists());

        return $candidate;
    }

    private function formatPatientResult(Patient $patient): array
    {
        $initials = Str::upper(Str::substr((string) $patient->first_name, 0, 1).Str::substr((string) $patient->last_name, 0, 1));

        return [
            'id' => $patient->id,
            'full_name' => $patient->full_name,
            'medical_record_number' => $patient->medical_record_number,
            'date_of_birth' => optional($patient->date_of_birth)->format('d/m/Y'),
            'phone' => $patient->phone,
            'initials' => $initials !== '' ? $initials : 'P',
            'photo_url' => $patient->patient_photo_path ? asset($patient->patient_photo_path) : null,
        ];
    }

    private function quickActions(): array
    {
        return collect([
            ['key' => 'new_exam', 'label' => 'Nouvelle demande RIS', 'hint' => 'Ouvrir le panneau lateral', 'action' => 'open-new-exam'],
            ['key' => 'focus_filters', 'label' => 'Filtrer la file RIS', 'hint' => 'Aller aux filtres de liste', 'action' => 'focus-filters'],
            ['key' => 'open_reports', 'label' => 'Voir les comptes rendus recents', 'hint' => 'Descendre vers les derniers comptes rendus', 'action' => 'open-reports'],
        ])->values()->all();
    }

    private function filterQuickActions(string $term): array
    {
        return collect($this->quickActions())
            ->filter(function (array $action) use ($term): bool {
                $haystack = Str::lower($action['label'].' '.$action['hint']);

                return str_contains($haystack, Str::lower($term));
            })
            ->values()
            ->all();
    }
}
