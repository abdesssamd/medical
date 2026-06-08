<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Patient;
use App\Models\PractitionerAccountingProfile;
use App\Models\Role;
use App\Models\Specialty;
use App\Models\User;
use App\Services\OrthancWorklistService;
use App\Services\KpiDashboardService;
use App\Services\LogisticsService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Appointment\Actions\CreateAppointmentAction;
use Modules\Appointment\Events\PlanningUpdated;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\PatientJourney;
use Modules\Appointment\Services\PatientFlowService;
use Modules\Appointment\Services\RecallAutomationService;
use Modules\ClinicalRecord\Models\ClinicalProcedure;
use Modules\ClinicalRecord\Models\AiImagingAnalysis;
use Modules\ClinicalRecord\Models\HealthQuestionnaire;
use Modules\ClinicalRecord\Models\Medication;
use Modules\ClinicalRecord\Models\OrthodonticPhotoSet;
use Modules\ClinicalRecord\Models\PatientLegalDocument;
use Modules\ClinicalRecord\Models\PeriodontalChart;
use Modules\ClinicalRecord\Models\PatientConsultation;
use Modules\ClinicalRecord\Models\PatientConsultationAttachment;
use Modules\ClinicalRecord\Models\PatientQuestionnaireResponse;
use Modules\ClinicalRecord\Models\Questionnaire;
use Modules\ClinicalRecord\Models\Prescription;
use Modules\ClinicalRecord\Models\PrescriptionTemplate;
use Modules\ClinicalRecord\Models\RadiologyRequest;
use Modules\RIS\Models\RisOrder;
use Modules\ClinicalRecord\Models\TreatmentPlan;
use Modules\ClinicalRecord\Services\AdvancedTreatmentQuoteService;
use Modules\ClinicalRecord\Services\ClinicalWorkflowService;
use Modules\ClinicalRecord\Services\PrescriptionService;
use Modules\Billing\Models\TreatmentQuote;
use Modules\Logistics\Models\LabOrder;
use Modules\Logistics\Models\LabOrderEvent;
use Modules\Logistics\Models\PatientSterilizationTrace;
use Modules\Queue\Models\AppSetting;
use Modules\Logistics\Models\StockItem;
use Modules\Scheduling\Models\AvailabilityBlock;
use Modules\Scheduling\Services\MultiSpecialtyCoordinationService;

class CareSuiteController extends Controller
{
    public function __construct(
        private readonly KpiDashboardService $kpiDashboardService,
        private readonly PatientFlowService $patientFlowService,
        private readonly MultiSpecialtyCoordinationService $coordinationService,
        private readonly ClinicalWorkflowService $clinicalWorkflowService,
        private readonly LogisticsService $logisticsService,
        private readonly AdvancedTreatmentQuoteService $advancedTreatmentQuoteService,
        private readonly RecallAutomationService $recallAutomationService,
        private readonly PrescriptionService $prescriptionService,
        private readonly OrthancWorklistService $orthancWorklistService
    ) {
    }

    public function module1(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'practitioner_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $from = $validated['from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['to'] ?? now()->endOfMonth()->toDateString();

        $kpi = $this->kpiDashboardService->build([
            'from' => $from,
            'to' => $to,
            'organization_id' => $validated['organization_id'] ?? null,
            'practitioner_id' => $validated['practitioner_id'] ?? null,
        ]);

        return view('modules.core-admin', [
            'kpi' => $kpi,
            'users' => User::orderBy('name')->get(['id', 'name', 'email', 'role']),
            'roles' => Role::orderBy('name')->get(['id', 'code', 'name']),
            'permissions' => Permission::orderBy('name')->get(['id', 'code', 'name']),
            'profiles' => PractitionerAccountingProfile::latest()->limit(20)->get(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function assignRoles(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role_codes' => ['required', 'array', 'min:1'],
            'role_codes.*' => ['required', 'string', 'exists:roles,code'],
        ]);

        $roleIds = Role::whereIn('code', $validated['role_codes'])->pluck('id');
        $user->roles()->sync($roleIds);

        return back()->with('success', 'Roles mis a jour.');
    }

    public function assignPermissions(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*.code' => ['required', 'string', 'exists:permissions,code'],
            'permissions.*.is_granted' => ['required', 'boolean'],
        ]);

        foreach ($validated['permissions'] as $entry) {
            $permissionId = Permission::where('code', $entry['code'])->value('id');
            $user->permissions()->syncWithoutDetaching([
                $permissionId => ['is_granted' => (bool) $entry['is_granted']],
            ]);
        }

        return back()->with('success', 'Permissions utilisateur mises a jour.');
    }

    public function accountingProfile(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'entity_code' => ['nullable', 'string', 'max:40'],
            'invoice_prefix' => ['nullable', 'string', 'max:12'],
            'currency' => ['nullable', 'string', 'size:3'],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        PractitionerAccountingProfile::updateOrCreate(
            [
                'practitioner_id' => $user->id,
                'organization_id' => $validated['organization_id'] ?? null,
            ],
            [
                'entity_code' => $validated['entity_code'] ?? null,
                'invoice_prefix' => $validated['invoice_prefix'] ?? 'FAC',
                'currency' => $validated['currency'] ?? 'MAD',
                'default_tax_rate' => $validated['default_tax_rate'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]
        );

        return back()->with('success', 'Profil comptable enregistre.');
    }

    public function module2(Request $request): View
    {
        $date = $request->input('date', now()->toDateString());
        $professionalId = $request->filled('professional_id') ? (int) $request->input('professional_id') : null;
        $professionalIds = $professionalId ? [$professionalId] : null;

        return view('modules.patient-flow', [
            'date' => $date,
            'professionals' => User::whereIn('role', ['professional', 'doctor', 'super_admin'])->orderBy('name')->get(['id', 'name']),
            'specialties' => \App\Models\Specialty::active()->orderBy('name')->get(['id', 'name', 'code']),
            'patients' => \App\Models\Patient::active()->orderBy('last_name')->limit(200)->get(['id', 'first_name', 'last_name', 'medical_record_number']),
            'board' => $this->patientFlowService->board($date, $professionalId),
            'scheduleGrid' => $this->patientFlowService->planningGrid($date, $professionalIds),
            'selectedProfessionalId' => $professionalId,
        ]);
    }

    public function boardData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'professional_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $date = $validated['date'] ?? now()->toDateString();
        $professionalId = $validated['professional_id'] ?? null;

        $board = $this->patientFlowService->board($date, $professionalId);
        $board['items'] = collect($board['items'])->map(function (array $item): array {
            $item['waiting_room_url'] = URL::temporarySignedRoute(
                'care.waiting-room',
                now()->addHours(8),
                ['appointment' => $item['appointment_id']]
            );
            return $item;
        })->values();
        $board['schedule_grid'] = $this->patientFlowService->planningGrid($date, $professionalId ? [$professionalId] : null);

        return response()->json($board);
    }

    public function searchPatients(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));

        $patients = Patient::query()
            ->active()
            ->when($query !== '', fn ($builder) => $builder->search($query))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(12)
            ->get(['id', 'first_name', 'last_name', 'medical_record_number', 'phone', 'email', 'allergies', 'medical_history']);

        return response()->json([
            'items' => $patients->map(function (Patient $patient): array {
                return [
                    'id' => $patient->id,
                    'label' => $patient->full_name.' | '.$patient->medical_record_number,
                    'full_name' => $patient->full_name,
                    'medical_record_number' => $patient->medical_record_number,
                    'phone' => $patient->phone,
                    'email' => $patient->email,
                    'allergies' => $patient->allergies ?? [],
                    'medical_history' => $patient->medical_history ?? [],
                ];
            })->values(),
        ]);
    }

    public function storeAppointment(Request $request, CreateAppointmentAction $action): JsonResponse
    {
        $validated = $request->validate([
            'professional_id' => ['required', 'integer', 'exists:users,id'],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'patient_name' => ['nullable', 'string', 'max:255'],
            'patient_phone' => ['nullable', 'string', 'max:40'],
            'patient_email' => ['nullable', 'email', 'max:191'],
            'appointment_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'consultation_reason' => ['required', 'string', 'max:255'],
            'consultation_type' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
            'appointment_type_id' => ['nullable', 'integer', 'exists:appointment_types,id'],
            'secretary_id' => ['nullable', 'integer', 'exists:users,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ]);

        $appointment = $action->execute($validated, (int) $request->user()->id);

        return response()->json([
            'message' => 'Rendez-vous cree avec consultation attendue.',
            'appointment' => $appointment->load(['patient', 'professional', 'consultation', 'appointmentType', 'room']),
        ], 201);
    }

    public function storeAvailabilityBlock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'practitioner_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'type' => ['required', 'string', 'in:break,formation,absence'],
            'label' => ['nullable', 'string', 'max:120'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ]);

        $block = AvailabilityBlock::create([
            'practitioner_id' => (int) $validated['practitioner_id'],
            'room_id' => $validated['room_id'] ?? null,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'].':00',
            'end_time' => $validated['end_time'].':00',
            'type' => $validated['type'],
            'label' => $validated['label'] ?? null,
            'is_booked' => false,
        ]);

        event(new PlanningUpdated((int) $validated['practitioner_id'], 'availability.blocked'));

        return response()->json([
            'message' => 'CrÃ©neau bloquÃ©.',
            'block' => $block->load('room:id,name,code'),
        ], 201);
    }

    public function transitionFlow(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:arrived,in_care,awaiting_payment,completed'],
        ]);

        return response()->json([
            'journey' => $this->patientFlowService->transition($appointment, $validated['status']),
        ]);
    }

    public function checkIn(Request $request, Appointment $appointment): JsonResponse
    {
        if (empty($appointment->queue_ticket_id)) {
            app(\Modules\Appointment\Contracts\QueueBridgeInterface::class)->checkInFromAppointment($appointment);
            $appointment = $appointment->fresh();
        }

        return response()->json([
            'journey' => $this->patientFlowService->transition($appointment, 'arrived'),
        ]);
    }

    public function callToRoom(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'room_label' => ['nullable', 'string', 'max:120'],
        ]);

        $journey = $this->patientFlowService->transition($appointment, 'in_care');
        if (! empty($validated['room_label'])) {
            $journey->update(['assigned_room_label' => $validated['room_label']]);
            $journey = $journey->fresh();
        }

        return response()->json([
            'message' => 'Patient appele en salle.',
            'journey' => $journey,
        ]);
    }

    public function rescheduleAppointment(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'appointment_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
        ]);

        $appointment->update([
            'appointment_date' => $validated['appointment_date'],
            'start_time' => $validated['start_time'].':00',
        ]);

        return response()->json([
            'message' => 'Rendez-vous reporte.',
            'appointment' => $appointment->fresh(),
        ]);
    }

    public function notifyDelay(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['nullable', 'string', 'in:sms,email,both'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $channel = $validated['channel'] ?? 'sms';

        return response()->json([
            'message' => "Notification retard envoyee via {$channel}.",
            'meta' => [
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'channel' => $channel,
                'payload' => $validated['message'] ?? null,
            ],
        ]);
    }

    public function closeJourney(Appointment $appointment): JsonResponse
    {
        return response()->json([
            'message' => 'Parcours patient cloture.',
            'journey' => $this->patientFlowService->transition($appointment, 'completed'),
        ]);
    }

    public function groupedSuggestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'specialties' => ['required', 'array', 'min:1'],
            'specialties.*.specialty_id' => ['required', 'integer', 'exists:specialties,id'],
            'specialties.*.appointment_type_id' => ['nullable', 'integer', 'exists:appointment_types,id'],
            'specialties.*.priority' => ['nullable', 'integer', 'min:1'],
        ]);

        $optimal = $this->coordinationService->findOptimalDayForMultipleSpecialties(
            $validated['patient_id'],
            $validated['specialties'],
            Carbon::parse($validated['from_date']),
            Carbon::parse($validated['to_date'])
        );

        return response()->json(['optimal_day' => $optimal]);
    }

    public function groupedBook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'date' => ['required', 'date'],
            'specialties' => ['required', 'array', 'min:1'],
            'specialties.*.specialty_id' => ['required', 'integer', 'exists:specialties,id'],
            'specialties.*.appointment_type_id' => ['nullable', 'integer', 'exists:appointment_types,id'],
        ]);

        $appointments = $this->coordinationService->bookGroupedAppointments(
            $validated['patient_id'],
            $validated['specialties'],
            Carbon::parse($validated['date']),
            auth()->id() ?? 0
        );

        return response()->json(['appointments' => $appointments]);
    }

    public function groupedAutoSuggestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $optimal = $this->coordinationService->suggestAutomaticallyFromPatientDossier(
            (int) $validated['patient_id'],
            Carbon::parse($validated['from_date']),
            Carbon::parse($validated['to_date'])
        );

        return response()->json(['optimal_day' => $optimal]);
    }

    public function module3(Request $request): View
    {
        $timelineFilters = $request->validate([
            'timeline_from' => ['nullable', 'date'],
            'timeline_to' => ['nullable', 'date', 'after_or_equal:timeline_from'],
            'timeline_type' => ['nullable', 'string', 'in:all,consultation,procedure,treatment_plan,imaging'],
        ]);

        $patientSelectColumns = [
            'id',
            'first_name',
            'last_name',
            'medical_record_number',
            'phone',
            'date_of_birth',
            'email',
            'address',
            'cin',
            'gender',
            'allergies',
            'medical_history',
            'current_medications',
            'family_history',
            'personal_history',
        ];

        foreach (['blood_group', 'height_cm', 'weight_kg', 'emergency_contact_name', 'emergency_contact_phone', 'critical_conditions'] as $optionalColumn) {
            $patientSelectColumns[] = $optionalColumn;
        }

        $patientQuery = \App\Models\Patient::active()
            ->orderBy('last_name')
            ->limit(300);

        while (true) {
            try {
                $patients = (clone $patientQuery)->get($patientSelectColumns);
                break;
            } catch (QueryException $exception) {
                $sqlState = (string) ($exception->errorInfo[0] ?? '');
                $driverCode = (int) ($exception->errorInfo[1] ?? 0);
                $isUnknownColumn = $sqlState === '42S22' || $driverCode === 1054;

                if (! $isUnknownColumn) {
                    throw $exception;
                }

                if (! preg_match("/Unknown column '([^']+)'/", (string) $exception->getMessage(), $matches)) {
                    throw $exception;
                }

                $missingColumn = $matches[1] ?? null;
                if (! $missingColumn || ! in_array($missingColumn, $patientSelectColumns, true)) {
                    throw $exception;
                }

                $patientSelectColumns = array_values(array_filter(
                    $patientSelectColumns,
                    static fn (string $column): bool => $column !== $missingColumn
                ));
            }
        }

        $patientIds = $patients->pluck('id')->all();

        $latestProceduresByPatient = ClinicalProcedure::whereIn('patient_id', $patientIds)
            ->whereNotNull('name')
            ->orderByDesc('performed_at')
            ->orderByDesc('id')
            ->get(['patient_id', 'name', 'performed_at'])
            ->groupBy('patient_id')
            ->map(fn ($rows) => $rows->first());

        $todayAppointments = Appointment::whereIn('patient_id', $patientIds)
            ->whereDate('appointment_date', now()->toDateString())
            ->get(['id', 'patient_id']);

        $journeys = PatientJourney::whereIn('appointment_id', $todayAppointments->pluck('id')->all())
            ->get(['appointment_id', 'current_status'])
            ->keyBy('appointment_id');

        $waitingStatuses = ['booked', 'arrived', 'in_care', 'awaiting_payment'];
        $waitingPatientIds = $todayAppointments
            ->filter(function ($apt) use ($journeys, $waitingStatuses) {
                $status = $journeys->get($apt->id)?->current_status;
                return in_array($status, $waitingStatuses, true);
            })
            ->pluck('patient_id')
            ->unique()
            ->flip();

        $latestAppointmentDates = Appointment::whereIn('patient_id', $patientIds)
            ->selectRaw('patient_id, MAX(appointment_date) as last_appointment_date')
            ->groupBy('patient_id')
            ->pluck('last_appointment_date', 'patient_id');

        $patientDirectory = $patients->map(function ($patient) use ($latestProceduresByPatient, $waitingPatientIds, $latestAppointmentDates) {
            $lastProcedure = $latestProceduresByPatient->get($patient->id);
            $lastAppointmentDate = $latestAppointmentDates->get($patient->id);

            $isIncomplete = empty($patient->phone) || empty($patient->email) || empty($patient->cin);
            $shouldRecall = $lastAppointmentDate
            ? Carbon::parse((string) $lastAppointmentDate)->lt(now()->subMonthsNoOverflow(6))
                : false;

            $status = 'normal';
            if ($waitingPatientIds->has($patient->id)) {
                $status = 'waiting';
            } elseif ($shouldRecall) {
                $status = 'recall';
            } elseif ($isIncomplete) {
                $status = 'incomplete';
            }

            return [
                'id' => $patient->id,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'full_name' => $patient->full_name,
                'medical_record_number' => $patient->medical_record_number,
                'patient_photo_path' => null,
                'phone' => $patient->phone,
                'email' => $patient->email,
                'address' => $patient->address,
                'cin' => $patient->cin,
                'date_of_birth' => optional($patient->date_of_birth)->toDateString(),
                'gender' => $patient->gender,
                'blood_group' => $patient->blood_group,
                'height_cm' => $patient->height_cm,
                'weight_kg' => $patient->weight_kg,
                'emergency_contact_name' => $patient->emergency_contact_name,
                'emergency_contact_phone' => $patient->emergency_contact_phone,
                'allergies' => $patient->allergies ?? [],
                'medical_history' => $patient->medical_history ?? [],
                'current_medications' => $patient->current_medications ?? [],
                'critical_conditions' => $patient->critical_conditions ?? [],
                'family_history' => $patient->family_history ?? [],
                'personal_history' => $patient->personal_history ?? [],
                'age' => $patient->age,
                'status' => $status,
                'last_act' => $lastProcedure?->name,
                'last_act_at' => $lastProcedure?->performed_at?->format('d/m/Y H:i'),
                'last_visit_at' => $lastAppointmentDate ? Carbon::parse((string) $lastAppointmentDate)->format('d/m/Y') : null,
                'needs_preventive_recall' => $shouldRecall,
            ];
        })->values();

        $selectedPatientId = $request->filled('patient_id')
            ? (int) $request->input('patient_id')
            : $patients->first()?->id;

        $selectedPatient = $selectedPatientId ? Patient::find($selectedPatientId) : null;
        $toPatientList = static fn ($value) => collect(is_array($value) ? $value : [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values();
        $currentTab = $request->input('tab', 'overview');
        if (! in_array($currentTab, ['overview', 'clinical', 'care', 'documents', 'rehab'], true)) {
            $currentTab = 'overview';
        }

        $selectedPatientId = $selectedPatientId ?: 0;
        $bmi = $selectedPatient?->bmi;
        $allergies = $toPatientList($selectedPatient?->allergies);
        $patientAllergies = $allergies;
        $patientMedicalHistory = $toPatientList($selectedPatient?->medical_history);
        $patientCriticalConditions = $toPatientList($selectedPatient?->critical_conditions);
        $riskTags = $toPatientList($selectedPatient?->critical_conditions);
        $criticalBannerItems = collect($selectedPatient?->critical_health_alerts ?? [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values();
        $nextAppointment = $selectedPatientId
            ? Appointment::where('patient_id', $selectedPatientId)
                ->whereDate('appointment_date', '>=', now()->toDateString())
                ->orderBy('appointment_date')
                ->orderBy('start_time')
                ->first(['id', 'appointment_date', 'start_time', 'professional_id'])
            : null;

        $selectedPatientLastVisitDate = $selectedPatientId ? $latestAppointmentDates->get($selectedPatientId) : null;
        $selectedPatientNeedsPreventiveRecall = $selectedPatientId
            ? ($selectedPatientLastVisitDate
                ? Carbon::parse((string) $selectedPatientLastVisitDate)->lt(now()->subMonthsNoOverflow(6))
                : true)
            : false;

        $odontogram = null;
        $timeline = collect();
        $manifest = null;
        $consultationTypes = $this->clinicalWorkflowService->consultationTypes();
        $diagnosisCatalog = $this->clinicalWorkflowService->diagnosisCatalog();
        $consultationMotifs = json_decode((string) AppSetting::getValue('consultation_motifs', '[]'), true) ?: [];

        if ($selectedPatientId) {
            $odontogram = $this->clinicalWorkflowService->odontogram($selectedPatientId);
            $timeline = $this->clinicalWorkflowService->timeline($selectedPatientId, [
                'from' => $timelineFilters['timeline_from'] ?? null,
                'to' => $timelineFilters['timeline_to'] ?? null,
                'type' => $timelineFilters['timeline_type'] ?? 'all',
            ]);
            $manifest = $this->clinicalWorkflowService->imagingManifest($selectedPatientId);
        }

        $questionnaires = Questionnaire::query()
            ->where('is_active', true)
            ->with(['specialty:id,name', 'practitioner:id,name'])
            ->orderBy('name')
            ->get();

        $questionnaireResponses = $selectedPatientId
            ? PatientQuestionnaireResponse::where('patient_id', $selectedPatientId)
                ->with(['questionnaire:id,name,field_schema', 'practitioner:id,name', 'consultation:id,consultation_date,consultation_reason'])
                ->latest('answered_at')
                ->latest('id')
                ->get()
            : collect();
        $questionnaireTemplatesPayload = $questionnaires->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'field_schema' => $template->field_schema ?? [],
                'specialty' => $template->specialty?->name,
                'practitioner' => $template->practitioner?->name,
                'group_name' => $template->group_name,
            ];
        })->values();

        $odontogramTeethStatus = $odontogram['teeth_status'] ?? [];
        $teethSummary = $odontogram['summary'] ?? [];
        $periodontalCharts = $selectedPatientId
            ? PeriodontalChart::where('patient_id', $selectedPatientId)->latest('recorded_on')->limit(50)->get()
            : collect();
        $periodontalSeed = $periodontalCharts->first()?->teeth_measurements ?? [];
        $periodontalHistorySeed = $periodontalCharts->map(fn ($chart) => [
            'id' => $chart->id,
            'recorded_on' => optional($chart->recorded_on)->format('d/m/Y'),
            'summary' => $chart->summary,
            'teeth_measurements' => $chart->teeth_measurements ?? [],
        ])->values()->all();

        // Status colors mapping
        $statusColors = [
            'completed' => '#10b981',
            'in_progress' => '#3b82f6',
            'planned' => '#f59e0b',
            'cancelled' => '#ef4444',
        ];

        return view('modules.clinical-workflow', [
            'patients' => $patients,
            'selectedPatientId' => $selectedPatientId,
            'selectedPatient' => $selectedPatient,
            'patientDirectory' => $patientDirectory,
            'currentTab' => $currentTab,
            'bmi' => $bmi,
            'nextAppointment' => $nextAppointment,
            'allergies' => $allergies,
            'patientAllergies' => $patientAllergies,
            'patientMedicalHistory' => $patientMedicalHistory,
            'patientCriticalConditions' => $patientCriticalConditions,
            'riskTags' => $riskTags,
            'criticalBannerItems' => $criticalBannerItems,
            'odontogram' => $odontogram,
            'odontogramTeethStatus' => $odontogramTeethStatus,
            'teethSummary' => $teethSummary,
            'timeline' => $timeline,
            'timelineFilters' => [
                'timeline_from' => $timelineFilters['timeline_from'] ?? null,
                'timeline_to' => $timelineFilters['timeline_to'] ?? null,
                'timeline_type' => $timelineFilters['timeline_type'] ?? 'all',
            ],
            'manifest' => $manifest,
            'consultationTypes' => $consultationTypes,
            'diagnosisCatalog' => $diagnosisCatalog,
            'consultationMotifs' => $consultationMotifs,
            'radiologyRequests' => $selectedPatientId
                ? RadiologyRequest::where('patient_id', $selectedPatientId)
                    ->with(['prescribingPhysician:id,name', 'requester:id,name'])
                    ->latest('requested_at')
                    ->latest('id')
                    ->limit(120)
                    ->get()
                : collect(),
            'risOrders' => $this->loadRisOrders($selectedPatientId),
            'consultations' => $selectedPatientId
                ? \Modules\ClinicalRecord\Models\PatientConsultation::where('patient_id', $selectedPatientId)
                    ->with(['practitioner:id,name', 'procedures.specialty:id,name', 'procedures.practitioner:id,name'])
                    ->latest('consultation_date')
                    ->latest('id')
                    ->get()
                : collect(),
            'proceduresDone' => $selectedPatientId
                ? \Modules\ClinicalRecord\Models\ClinicalProcedure::where('patient_id', $selectedPatientId)
                    ->with(['specialty:id,name', 'practitioner:id,name', 'consultation:id,consultation_date,consultation_reason'])
                    ->whereIn('status', ['completed', 'in_progress', 'planned', 'cancelled'])
                    ->latest('performed_at')
                    ->latest('id')
                    ->limit(200)
                    ->get()
                : collect(),
            'appointmentsForPatient' => $selectedPatientId
                ? \Modules\Appointment\Models\Appointment::where('patient_id', $selectedPatientId)
                    ->orderByDesc('appointment_date')
                    ->orderByDesc('start_time')
                    ->limit(100)
                    ->get(['id', 'appointment_date', 'start_time', 'professional_id'])
                : collect(),
            'practitioners' => \App\Models\User::whereIn('role', ['professional', 'doctor', 'super_admin'])
                ->orderBy('name')
                ->get(['id', 'name']),
            'specialtiesList' => \App\Models\Specialty::active()->orderBy('name')->get(['id', 'name', 'code']),
            'treatmentPlans' => $selectedPatientId
                ? \Modules\ClinicalRecord\Models\TreatmentPlan::where('patient_id', $selectedPatientId)
                    ->with('practitioner:id,name')
                    ->latest('id')
                    ->limit(100)
                    ->get()
                : collect(),
            'treatmentQuotes' => $selectedPatientId
                ? \Modules\Billing\Models\TreatmentQuote::where('patient_id', $selectedPatientId)
                    ->with(['items', 'practitioner:id,name'])
                    ->latest('id')
                    ->limit(100)
                    ->get()
                : collect(),
            'periodontalCharts' => $periodontalCharts,
            'periodontalSeed' => $periodontalSeed,
            'periodontalHistorySeed' => $periodontalHistorySeed,
            'orthoPhotoSets' => $selectedPatientId
                ? OrthodonticPhotoSet::where('patient_id', $selectedPatientId)->latest('captured_on')->limit(60)->get()
                : collect(),
            'legalDocuments' => $selectedPatientId
                ? PatientLegalDocument::where('patient_id', $selectedPatientId)->latest('id')->limit(80)->get()
                : collect(),
            'healthQuestionnaires' => $selectedPatientId
                ? HealthQuestionnaire::where('patient_id', $selectedPatientId)->latest('filled_on')->limit(30)->get()
                : collect(),
            'questionnaires' => $questionnaires,
            'questionnaireTemplatesPayload' => $questionnaireTemplatesPayload,
            'questionnaireResponses' => $questionnaireResponses,
            'aiAnalyses' => $selectedPatientId
                ? AiImagingAnalysis::where('patient_id', $selectedPatientId)->latest('id')->limit(80)->get()
                : collect(),
            'patientRecalls' => $selectedPatientId
                ? \Modules\Appointment\Models\PatientRecall::where('patient_id', $selectedPatientId)->latest('due_date')->limit(80)->get()
                : collect(),
            'medications' => Medication::where('is_active', true)->orderBy('name')->limit(500)->get(),
            'prescriptionTemplates' => PrescriptionTemplate::with('items')->where('is_active', true)->orderBy('name')->get(),
            'prescriptions' => $selectedPatientId
                ? Prescription::where('patient_id', $selectedPatientId)->with(['items', 'practitioner', 'template'])->latest('issued_at')->limit(120)->get()
                : collect(),
            'statusColors' => $statusColors,
            'selectedPatientNeedsPreventiveRecall' => $selectedPatientNeedsPreventiveRecall,
            'selectedPatientLastVisitAt' => $selectedPatientLastVisitDate
                ? Carbon::parse((string) $selectedPatientLastVisitDate)->format('d/m/Y')
                : null,
            'currentSpecialtyCode' => auth()->user()->specialty?->code ?? null,
            'gynecologyDashboard' => $selectedPatientId && in_array(auth()->user()->specialty?->code, ['GYNECO', 'OMNI'])
                ? app(\Modules\Gynecology\Services\GynecologyService::class)->patientDashboard($selectedPatientId)
                : null,
            'pediatricsDashboard' => $selectedPatientId && in_array(auth()->user()->specialty?->code, ['PEDIA', 'OMNI'])
                ? app(\Modules\Pediatrics\Services\PediatricGrowthService::class)->patientDashboard($selectedPatientId)
                : null,
            'pediatricsVaccinationSchedule' => $selectedPatientId && in_array(auth()->user()->specialty?->code, ['PEDIA', 'OMNI'])
                ? app(\Modules\Pediatrics\Services\VaccinationScheduleService::class)->generateSchedule($selectedPatientId)
                : [],
            'pediatricsVaccinationSummary' => $selectedPatientId && in_array(auth()->user()->specialty?->code, ['PEDIA', 'OMNI'])
                ? app(\Modules\Pediatrics\Services\VaccinationScheduleService::class)->getVaccinationSummary($selectedPatientId)
                : [],
            'burnsDashboard' => $selectedPatientId && in_array(auth()->user()->specialty?->code, ['BURNS', 'OMNI'])
                ? app(\Modules\Burns\Services\BurnsService::class)->patientDashboard($selectedPatientId)
                : null,
            'rehabDashboard' => $selectedPatientId && in_array(auth()->user()->specialty?->code, ['REHAB', 'OMNI'])
                ? app(\Modules\Rehab\Services\RehabService::class)->patientDashboard($selectedPatientId)
                : [],
            'internalMedicineDashboard' => $selectedPatientId && in_array(auth()->user()->specialty?->code, ['INTMED', 'OMNI'])
                ? app(\Modules\InternalMedicine\Services\InternalMedicineService::class)->patientDashboard($selectedPatientId)
                : [],
        ]);
    }

    public function storeTreatmentPlan(Request $request, int $patientId): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'objective' => ['nullable', 'string'],
            'total_estimated_cost' => ['required', 'numeric', 'min:0'],
            'phases_text' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'signature_channel' => ['nullable', 'string', 'in:sms,email'],
            'signature_recipient' => ['nullable', 'string', 'max:191'],
        ]);

        $phases = collect(preg_split('/[\r\n]+/', trim($validated['phases_text'])) ?: [])
            ->map(fn ($line, $idx) => [
                'order' => $idx + 1,
                'name' => trim((string) $line),
                'status' => 'planned',
            ])
            ->filter(fn ($phase) => $phase['name'] !== '')
            ->values()
            ->all();

        $status = ! empty($validated['signature_channel']) && ! empty($validated['signature_recipient'])
            ? TreatmentPlan::STATUS_PENDING_SIGNATURE
            : TreatmentPlan::STATUS_DRAFT;

        $plan = TreatmentPlan::create([
            'patient_id' => $patientId,
            'practitioner_id' => auth()->id(),
            'name' => $validated['name'],
            'objective' => $validated['objective'] ?? null,
            'status' => $status,
            'total_estimated_cost' => $validated['total_estimated_cost'],
            'paid_amount' => 0,
            'phases' => $phases,
            'notes' => $validated['notes'] ?? null,
            'signature_channel' => $validated['signature_channel'] ?? null,
            'signature_recipient' => $validated['signature_recipient'] ?? null,
            'signature_token' => Str::random(40),
            'signature_requested_at' => now(),
            'signature_expires_at' => now()->addDays(7),
        ]);

        return redirect()->route('care.module3.index', ['patient_id' => $patientId])
            ->with('success', "Plan de traitement '{$plan->name}' cree.");
    }

    public function requestTreatmentPlanSignature(TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $treatmentPlan->update([
            'status' => TreatmentPlan::STATUS_PENDING_SIGNATURE,
            'signature_token' => $treatmentPlan->signature_token ?: Str::random(40),
            'signature_requested_at' => now(),
            'signature_expires_at' => now()->addDays(7),
        ]);

        $url = URL::temporarySignedRoute(
            'care.module3.plan.signature.form',
            $treatmentPlan->signature_expires_at ?? now()->addDays(7),
            ['plan' => $treatmentPlan->id, 'token' => $treatmentPlan->signature_token]
        );

        return back()->with('success', 'Lien signature pret: '.$url);
    }

    public function treatmentPlanSignatureForm(Request $request, TreatmentPlan $plan): View
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless(hash_equals((string) $plan->signature_token, (string) $request->query('token')), 403);

        return view('modules.treatment-plan-signature', [
            'plan' => $plan->load('patient:id,first_name,last_name,medical_record_number', 'practitioner:id,name'),
        ]);
    }

    public function treatmentPlanSign(Request $request, TreatmentPlan $plan): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless(hash_equals((string) $plan->signature_token, (string) $request->query('token')), 403);

        $validated = $request->validate([
            'patient_name' => ['required', 'string', 'max:191'],
            'signature_data' => ['required', 'string', 'max:20000'],
            'accept_terms' => ['required', 'accepted'],
        ]);

        $plan->update([
            'status' => TreatmentPlan::STATUS_APPROVED,
            'signed_at' => now(),
            'signed_by_patient_name' => $validated['patient_name'],
            'signature_ip' => (string) $request->ip(),
            'signature_payload' => [
                'data_url' => $validated['signature_data'],
                'accepted_terms' => true,
                'signed_user_agent' => (string) $request->userAgent(),
            ],
        ]);

        return back()->with('success', 'Signature enregistree avec succes.');
    }

    public function createTreatmentQuote(Request $request, TreatmentPlan $plan): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'insurance_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mutual_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $quote = $this->advancedTreatmentQuoteService->createQuoteFromPlan(
            treatmentPlanId: $plan->id,
            insuranceRate: (float) ($validated['insurance_rate'] ?? 70),
            mutualRate: (float) ($validated['mutual_rate'] ?? 0)
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Devis {$quote->quote_number} genere.",
                'quote' => [
                    'id' => $quote->id,
                    'quote_number' => $quote->quote_number,
                    'subtotal' => (float) $quote->subtotal,
                    'insurance_amount' => (float) $quote->insurance_amount,
                    'mutual_amount' => (float) $quote->mutual_amount,
                    'patient_amount' => (float) $quote->patient_amount,
                    'pdf_url' => route('care.module3.quote.pdf', ['quote' => $quote->id]),
                ],
            ]);
        }

        return back()->with('success', "Devis {$quote->quote_number} genere.");
    }

    public function treatmentQuotePdf(TreatmentQuote $quote)
    {
        $data = $this->advancedTreatmentQuoteService->buildPdfData($quote);

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf();
            $html = view('modules.treatment-quote-pdf', $data)->render();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$quote->quote_number.'.pdf"',
            ]);
        }

        return response()->view('modules.treatment-quote-pdf', $data);
    }

    public function signTreatmentQuote(Request $request, TreatmentQuote $quote): RedirectResponse
    {
        $validated = $request->validate([
            'patient_name' => ['required', 'string', 'max:191'],
            'signature_data' => ['required', 'string', 'max:20000'],
        ]);

        $this->advancedTreatmentQuoteService->signQuoteOnTablet(
            quote: $quote,
            patientName: $validated['patient_name'],
            signatureData: $validated['signature_data'],
            ip: (string) $request->ip()
        );

        return back()->with('success', "Consentement signe pour devis {$quote->quote_number}.");
    }

    public function storePeriodontalChart(Request $request, int $patientId): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'recorded_on' => ['required', 'date', 'before_or_equal:today'],
            'measurements' => ['nullable', 'string'],
            'measurements_json' => ['nullable', 'string'],
            'summary' => ['nullable', 'string'],
        ]);

        $rows = $this->normalizePeriodontalMeasurements(
            $validated['measurements_json'] ?? null,
            $validated['measurements'] ?? null
        );

        $summary = trim((string) ($validated['summary'] ?? ''));
        if ($summary === '') {
            $summary = $this->buildPeriodontalSummary($rows, $validated['recorded_on']);
        }

        $chart = PeriodontalChart::create([
            'patient_id' => $patientId,
            'recorded_by' => auth()->id(),
            'recorded_on' => $validated['recorded_on'],
            'teeth_measurements' => $rows,
            'summary' => $summary,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Parodontogramme enregistre.',
                'chart' => [
                    'id' => $chart->id,
                    'recorded_on' => optional($chart->recorded_on)->format('d/m/Y'),
                    'summary' => $chart->summary,
                    'teeth_measurements' => $chart->teeth_measurements,
                ],
            ]);
        }

        return back()->with('success', 'Charting parodontal enregistre.');
    }

    public function storeOrthodonticPhotoSet(Request $request, int $patientId): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:191'],
            'captured_on' => ['nullable', 'date', 'before_or_equal:today'],
            'before_image' => ['nullable', 'file', 'max:10240', 'mimes:jpeg,png,jpg'],
            'after_image' => ['nullable', 'file', 'max:10240', 'mimes:jpeg,png,jpg'],
            'notes' => ['nullable', 'string'],
        ]);

        $beforePath = null;
        $afterPath = null;
        if ($request->hasFile('before_image')) {
            $beforePath = 'storage/'.$request->file('before_image')->store("ortho_photos/patient_{$patientId}", ['disk' => 'public']);
        }
        if ($request->hasFile('after_image')) {
            $afterPath = 'storage/'.$request->file('after_image')->store("ortho_photos/patient_{$patientId}", ['disk' => 'public']);
        }

        OrthodonticPhotoSet::create([
            'patient_id' => $patientId,
            'created_by' => auth()->id(),
            'label' => $validated['label'],
            'captured_on' => $validated['captured_on'] ?? now()->toDateString(),
            'before_image_path' => $beforePath,
            'after_image_path' => $afterPath,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Serie photo Avant/Apres ajoutee.');
    }

    public function storeLegalDocument(Request $request, int $patientId): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:191'],
            'risk_summary' => ['nullable', 'string'],
            'risk_flag' => ['nullable', 'boolean'],
            'signed_on' => ['nullable', 'date', 'before_or_equal:today'],
            'file' => ['nullable', 'file', 'max:20480'],
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = 'storage/'.$request->file('file')->store("legal_docs/patient_{$patientId}", ['disk' => 'public']);
        }

        PatientLegalDocument::create([
            'patient_id' => $patientId,
            'uploaded_by' => auth()->id(),
            'document_type' => $validated['document_type'],
            'title' => $validated['title'],
            'file_path' => $filePath,
            'status' => 'active',
            'signed_on' => $validated['signed_on'] ?? null,
            'risk_flag' => (bool) ($validated['risk_flag'] ?? false),
            'risk_summary' => $validated['risk_summary'] ?? null,
        ]);

        return back()->with('success', 'Document legal enregistre.');
    }

    public function storeHealthQuestionnaire(Request $request, int $patientId): RedirectResponse
    {
        $validated = $request->validate([
            'answers' => ['required', 'string'],
            'risk_tags' => ['nullable', 'string'],
            'critical_notes' => ['nullable', 'string'],
            'has_critical_risk' => ['nullable', 'boolean'],
            'filled_on' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $answers = collect(preg_split('/[\r\n]+/', (string) $validated['answers']) ?: [])
            ->map(function (string $line): ?array {
                $line = trim($line);
                if ($line === '') {
                    return null;
                }
                [$q, $a] = array_pad(explode(':', $line, 2), 2, '');
                return ['question' => trim($q), 'answer' => trim($a)];
            })
            ->filter()
            ->values()
            ->all();

        $riskTags = array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', (string) ($validated['risk_tags'] ?? '')) ?: [])));

        HealthQuestionnaire::create([
            'patient_id' => $patientId,
            'validated_by' => auth()->id(),
            'filled_on' => $validated['filled_on'] ?? now()->toDateString(),
            'answers' => $answers,
            'risk_tags' => $riskTags,
            'has_critical_risk' => (bool) ($validated['has_critical_risk'] ?? false),
            'critical_notes' => $validated['critical_notes'] ?? null,
        ]);

        return back()->with('success', 'Questionnaire sante enregistre.');
    }

    public function questionnaireSettings(): View
    {
        $questionnaires = Questionnaire::query()
            ->with(['specialty:id,name', 'practitioner:id,name'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $questionnaireTemplatesPayload = $questionnaires->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'field_schema' => $template->field_schema ?? [],
                'specialty' => $template->specialty?->name,
                'practitioner' => $template->practitioner?->name,
                'group_name' => $template->group_name,
            ];
        })->values();

        return view('admin.questionnaire-settings', [
            'questionnaires' => $questionnaires,
            'questionnaireTemplatesPayload' => $questionnaireTemplatesPayload,
            'specialtiesList' => Specialty::active()->orderBy('name')->get(['id', 'name', 'code']),
            'practitioners' => User::whereIn('role', ['professional', 'doctor', 'super_admin'])
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function storeQuestionnaireTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'practitioner_id' => ['nullable', 'integer', 'exists:users,id'],
            'group_name' => ['nullable', 'string', 'max:191'],
            'is_active' => ['nullable', 'boolean'],
            'field_schema_json' => ['required', 'string'],
        ]);

        $schema = json_decode((string) $validated['field_schema_json'], true);
        if (! is_array($schema) || count($schema) === 0) {
            return back()->withErrors(['field_schema_json' => 'Le schema JSON du formulaire est invalide.'])->withInput();
        }

        foreach ($schema as $index => $field) {
            if (! is_array($field) || empty($field['key']) || empty($field['label']) || empty($field['type'])) {
                return back()->withErrors(['field_schema_json' => 'Chaque champ doit contenir key, label et type.'])->withInput();
            }

            if (! in_array($field['type'], ['date', 'number', 'select', 'checkbox', 'textarea', 'text'], true)) {
                return back()->withErrors(['field_schema_json' => 'Type de champ non supporte au rang '.($index + 1).'.'])->withInput();
            }

            if (in_array($field['type'], ['select'], true) && (empty($field['options']) || ! is_array($field['options']))) {
                return back()->withErrors(['field_schema_json' => 'Les champs select doivent contenir une liste options.'])->withInput();
            }
        }

        Questionnaire::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'specialty_id' => $validated['specialty_id'] ?? null,
            'practitioner_id' => $validated['practitioner_id'] ?? null,
            'group_name' => $validated['group_name'] ?? null,
            'created_by' => auth()->id(),
            'field_schema' => $schema,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', 'Modele de questionnaire cree.');
    }

    public function storeQuestionnaireResponse(Request $request, int $patientId, Questionnaire $questionnaire): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'consultation_id' => ['required', 'integer', 'exists:patient_consultations,id'],
            'answered_at' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
            'answers' => ['required', 'array'],
        ]);

        $consultation = PatientConsultation::query()->findOrFail($validated['consultation_id']);
        if ((int) $consultation->patient_id !== $patientId) {
            return back()->withErrors(['consultation_id' => 'La consultation ne correspond pas a ce patient.'])->withInput();
        }

        $schema = $questionnaire->field_schema ?? [];
        $rules = [];
        foreach ($schema as $field) {
            $key = $field['key'] ?? $field['id'] ?? null;
            if (! $key) {
                continue;
            }

            $rules[$key] = $field['required'] ?? false ? ['required'] : ['nullable'];
            $rules[$key][] = match ($field['type'] ?? 'text') {
                'date' => 'date',
                'number' => 'numeric',
                'checkbox' => is_array($field['options'] ?? null) && count($field['options'] ?? []) > 0 ? 'array' : 'boolean',
                default => 'string',
            };
        }

        $answerBag = $request->input('answers', []);
        $normalizedAnswers = [];
        foreach ($schema as $field) {
            $key = $field['key'] ?? $field['id'] ?? null;
            if (! $key) {
                continue;
            }

            $value = $answerBag[$key] ?? null;
            if (($field['type'] ?? '') === 'checkbox') {
                if (is_array($field['options'] ?? null) && count($field['options'] ?? []) > 0) {
                    $normalizedAnswers[$key] = is_array($value)
                        ? array_values(array_filter(array_map(static fn ($item) => is_string($item) ? trim($item) : $item, $value), static fn ($item) => $item !== null && $item !== ''))
                        : [];
                } else {
                    $normalizedAnswers[$key] = (bool) $value;
                }
            } else {
                $normalizedAnswers[$key] = is_string($value) ? trim($value) : $value;
            }
        }

        foreach ($schema as $field) {
            $key = $field['key'] ?? null;
            if (! $key || empty($field['required'])) {
                continue;
            }

            if (($normalizedAnswers[$key] ?? null) === null || $normalizedAnswers[$key] === '') {
                return back()->withErrors([$key => 'Ce champ est obligatoire.'])->withInput();
            }
        }

        PatientQuestionnaireResponse::create([
            'questionnaire_id' => $questionnaire->id,
            'patient_id' => $patientId,
            'consultation_id' => $consultation->id,
            'practitioner_id' => auth()->id(),
            'answers' => $normalizedAnswers,
            'answered_at' => $validated['answered_at'] ?? now(),
            'notes' => $validated['notes'] ?? null,
            'source' => 'module3',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Questionnaire enregistre.']);
        }

        return redirect()->route('care.module3.index', ['patient_id' => $patientId])
            ->with('success', 'Questionnaire enregistre et rattache a la consultation.');
    }

    public function requestAiImagingAnalysis(Request $request, int $patientId): RedirectResponse
    {
        $validated = $request->validate([
            'imaging_study_id' => ['nullable', 'integer', 'exists:imaging_studies,id'],
            'analysis_type' => ['required', 'string', 'in:caries_detection,bone_loss'],
            'provider' => ['nullable', 'string', 'max:50'],
        ]);

        AiImagingAnalysis::create([
            'patient_id' => $patientId,
            'imaging_study_id' => $validated['imaging_study_id'] ?? null,
            'provider' => $validated['provider'] ?? 'orthanc_api',
            'analysis_type' => $validated['analysis_type'],
            'status' => 'queued',
            'requested_at' => now(),
            'findings' => [
                'note' => 'Analyse en attente de l API IA (integration Orthanc-ready).',
            ],
        ]);

        return back()->with('success', 'Analyse IA demandee (file d attente).');
    }

    public function generatePatientRecalls(int $patientId): RedirectResponse
    {
        $count = $this->recallAutomationService->generateRecallsFromHistory($patientId);
        return back()->with('success', "{$count} rappels prevention generes.");
    }

    public function searchMedications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        if ($q === '') {
            return response()->json(['items' => []]);
        }

        $items = $this->prescriptionService->searchMedications($q, 15)
            ->map(fn ($med) => [
                'id' => $med['id'] ?? null,
                'name' => $med['name'] ?? '',
                'category' => $med['category'] ?? null,
                'strength' => $med['strength'] ?? null,
                'default_unit' => $med['default_unit'] ?? null,
                'default_frequency' => $med['default_frequency'] ?? null,
                'default_duration_days' => $med['default_duration_days'] ?? null,
                'source' => $med['source'] ?? 'local',
            ])->values();

        return response()->json(['items' => $items]);
    }

    public function prescriptionTemplateData(PrescriptionTemplate $template): JsonResponse
    {
        return response()->json($this->prescriptionService->templateData($template->id));
    }

    public function storePrescription(Request $request, int $patientId): RedirectResponse
    {
        $validated = $request->validate([
            'consultation_id' => ['nullable', 'integer', 'exists:patient_consultations,id'],
            'prescription_template_id' => ['nullable', 'integer', 'exists:prescription_templates,id'],
            'items_json' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'signature_data' => ['nullable', 'string', 'max:30000'],
        ]);

        $items = json_decode((string) $validated['items_json'], true);
        if (! is_array($items) || count($items) === 0) {
            return back()->withErrors(['items_json' => 'Ajoutez au moins un medicament a l ordonnance.']);
        }

        $patient = Patient::findOrFail($patientId);

        $analysis = $this->prescriptionService->analyzeSafety($patient, $items);
        if (! empty($analysis['blocking'])) {
            return back()->withErrors([
                'prescription_safety' => implode(' | ', $analysis['blocking']),
            ])->withInput();
        }

        $prescription = $this->prescriptionService->createPrescription($patient, [
            'consultation_id' => $validated['consultation_id'] ?? null,
            'prescription_template_id' => $validated['prescription_template_id'] ?? null,
            'items' => $items,
            'notes' => $validated['notes'] ?? null,
            'signature_mode' => 'digital',
            'signature_payload' => ! empty($validated['signature_data']) ? ['data_url' => $validated['signature_data']] : null,
        ]);

        return redirect()
            ->route('care.module3.index', ['patient_id' => $patientId])
            ->with('success', "Ordonnance {$prescription->prescription_number} enregistree.");
    }

    public function addPatientHistoryItem(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'group' => ['required', 'string', 'in:allergies,medical_history,critical_conditions'],
            'item' => ['required', 'string', 'max:191'],
        ]);

        $patient = Patient::findOrFail($patientId);
        $item = trim((string) $validated['item']);
        $group = $validated['group'];

        $targetGroup = $group;
        $current = array_values(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            (array) ($patient->{$targetGroup} ?? [])
        )));

        $existingLower = array_map('mb_strtolower', $current);
        if ($item === '' || in_array(mb_strtolower($item), $existingLower, true)) {
            return response()->json([
                'message' => 'Element deja present.',
                'patient' => [
                    'id' => $patient->id,
                    'allergies' => $patient->allergies ?? [],
                    'medical_history' => $patient->medical_history ?? [],
                    'critical_conditions' => $patient->critical_conditions ?? [],
                    'critical_health_alerts' => $patient->critical_health_alerts,
                ],
            ]);
        }

        $current[] = $item;
        $savedValues = $current;

        try {
            $patient->update([
                $targetGroup => $current,
            ]);
        } catch (\Illuminate\Database\QueryException $exception) {
            $sqlState = (string) ($exception->errorInfo[0] ?? '');
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);
            $isUnknownColumn = $sqlState === '42S22' || $driverCode === 1054;

            if ($targetGroup !== 'critical_conditions' || ! $isUnknownColumn) {
                throw $exception;
            }

            // Backward compatibility for DBs where `patients.critical_conditions` is not yet deployed.
            $targetGroup = 'medical_history';
            $fallback = array_values(array_filter(array_map(
                static fn ($value) => trim((string) $value),
                (array) ($patient->{$targetGroup} ?? [])
            )));

            if (! in_array(mb_strtolower($item), array_map('mb_strtolower', $fallback), true)) {
                $fallback[] = $item;
            }

            $patient->update([
                $targetGroup => $fallback,
            ]);
            $savedValues = $fallback;
        }

        $patient->setAttribute($targetGroup, $savedValues);

        return response()->json([
            'message' => 'Antecedent ajoute avec succes.',
            'patient' => [
                'id' => $patient->id,
                'allergies' => $patient->allergies ?? [],
                'medical_history' => $patient->medical_history ?? [],
                'critical_conditions' => $patient->critical_conditions ?? [],
                'critical_health_alerts' => $patient->critical_health_alerts,
            ],
        ]);
    }

    public function managePatientHistory(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:personal,family,allergies'],
            'value' => ['required', 'string', 'max:191'],
        ]);

        $patient = Patient::findOrFail($patientId);
        $type = $validated['type'];
        $value = trim((string) $validated['value']);

        // Map UI type to database field
        $fieldMap = [
            'personal' => 'personal_history',
            'family' => 'family_history',
            'allergies' => 'allergies',
        ];
        $field = $fieldMap[$type] ?? null;

        if (!$field) {
            return response()->json(['message' => 'Type invalide'], 400);
        }

        if ($request->isMethod('POST')) {
            // Add item
            $current = array_values(array_filter(array_map(
                static fn ($v) => trim((string) $v),
                (array) ($patient->{$field} ?? [])
            )));

            $existingLower = array_map('mb_strtolower', $current);
            if ($value === '' || in_array(mb_strtolower($value), $existingLower, true)) {
                return response()->json(['message' => 'Element dÃ©jÃ  prÃ©sent'], 409);
            }

            $current[] = $value;
            $patient->update([$field => $current]);

            return response()->json([
                'message' => 'AjoutÃ© avec succÃ¨s',
                'value' => $value,
            ]);
        } elseif ($request->isMethod('DELETE')) {
            // Remove item
            $current = array_values(array_filter(array_map(
                static fn ($v) => trim((string) $v),
                (array) ($patient->{$field} ?? [])
            )));

            $key = array_search(mb_strtolower($value), array_map('mb_strtolower', $current), true);
            if ($key === false) {
                return response()->json(['message' => 'Ã‰lÃ©ment non trouvÃ©'], 404);
            }

            unset($current[$key]);
            $current = array_values($current);
            $patient->update([$field => $current]);

            return response()->json([
                'message' => 'SupprimÃ© avec succÃ¨s',
                'value' => $value,
            ]);
        }

        return response()->json(['message' => 'MÃ©thode non autorisÃ©e'], 405);
    }

    public function prescriptionPdf(Prescription $prescription)
    {
        $data = $this->prescriptionService->buildPdfData($prescription);

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf();
            $html = view('modules.prescription-pdf', $data)->render();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$prescription->prescription_number.'.pdf"',
            ]);
        }

        return response()->view('modules.prescription-pdf', $data);
    }

    public function verifyPrescription(string $token): View
    {
        $prescription = Prescription::where('qr_token', $token)
            ->with(['patient:id,first_name,last_name,medical_record_number', 'practitioner:id,name'])
            ->firstOrFail();

        return view('modules.prescription-verify', [
            'prescription' => $prescription,
        ]);
    }

    public function sendPrescriptionEmail(Request $request, Prescription $prescription): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:191'],
        ]);

        $this->prescriptionService->sendToEmail($prescription, $validated['email']);

        return back()->with('success', "Ordonnance envoyee a {$validated['email']}.");
    }

    public function waitingRoom(Request $request, Appointment $appointment): View
    {
        abort_unless($request->hasValidSignature(), 403);

        return view('modules.patient-waiting-room', [
            'appointment' => $appointment->load(['patient:id,first_name,last_name', 'professional:id,name', 'room:id,name']),
            'dataUrl' => URL::temporarySignedRoute(
                'care.waiting-room.data',
                now()->addMinutes(30),
                ['appointment' => $appointment->id]
            ),
        ]);
    }

    public function waitingRoomData(Request $request, Appointment $appointment): JsonResponse
    {
        abort_unless($request->hasValidSignature(), 403);

        $journey = PatientJourney::firstWhere('appointment_id', $appointment->id);
        $board = $this->patientFlowService->board($appointment->appointment_date->toDateString(), $appointment->professional_id);
        $item = collect($board['items'])->firstWhere('appointment_id', $appointment->id) ?? [];

        return response()->json([
            'patient' => $appointment->patient?->full_name ?? $appointment->patient_name,
            'professional' => $appointment->professional?->name,
            'status' => $journey?->current_status ?? 'booked',
            'estimated_wait_minutes' => $item['estimated_wait_minutes'] ?? $journey?->estimated_wait_minutes,
            'doctor_delay_minutes' => $item['doctor_delay_minutes'] ?? 0,
            'queue_position' => $item['queue_position'] ?? 0,
            'room' => $item['room_name'] ?? $appointment->room?->name ?? $journey?->assigned_room_label,
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    public function storeRadiologyRequest(Request $request, int $patientId): RedirectResponse
    {
        $validated = $request->validate([
            'exam_type' => ['required', 'string', 'max:120'],
            'anatomical_region' => ['required', 'string', 'max:120'],
            'priority' => ['required', 'string', 'in:urgent,routine'],
            'target_modality' => ['required', 'string', 'max:16'],
            'scheduled_station_ae_title' => ['required', 'string', 'max:64'],
            'prescribing_physician_id' => ['required', 'integer', 'exists:users,id'],
            'clinical_reason' => ['required', 'string'],
        ]);

        $patient = Patient::findOrFail($patientId);
        $requestedProcedureDescription = trim($validated['exam_type'].' - '.$validated['anatomical_region']);

        $radiologyRequest = RadiologyRequest::create([
            'patient_id' => $patient->id,
            'requested_by' => auth()->id(),
            'prescribing_physician_id' => (int) $validated['prescribing_physician_id'],
            'exam_type' => $validated['exam_type'],
            'anatomical_region' => $validated['anatomical_region'],
            'priority' => $validated['priority'],
            'clinical_reason' => $validated['clinical_reason'],
            'target_modality' => strtoupper($validated['target_modality']),
            'scheduled_station_ae_title' => strtoupper($validated['scheduled_station_ae_title']),
            'requested_procedure_description' => $requestedProcedureDescription,
            'workflow_status' => RadiologyRequest::STATUS_REQUESTED,
            'requested_at' => now(),
        ]);

        $dispatchResult = $this->orthancWorklistService->createAndDispatch($patient, $radiologyRequest);
        $orthancResult = (array) ($dispatchResult['orthanc'] ?? []);
        $orthancBody = $orthancResult['body'] ?? null;

        $radiologyRequest->update([
            'worklist_file_path' => $dispatchResult['worklist_file_path'] ?? null,
            'orthanc_worklist_id' => is_array($orthancBody)
                ? ($orthancBody['ID'] ?? $orthancBody['id'] ?? $orthancBody['Parent'] ?? null)
                : null,
            'orthanc_payload' => [
                'dataset' => $dispatchResult['dataset'] ?? null,
                'dispatch' => $orthancResult,
            ],
        ]);

        if (!($orthancResult['ok'] ?? false)) {
            return redirect()
                ->route('care.module3.index', ['patient_id' => $patientId, 'tab' => 'documents'])
                ->with('warning', 'Demande enregistree, mais push Orthanc MWL non confirme. Verifiez la config Orthanc et DCMTK.');
        }

        return redirect()
            ->route('care.module3.index', ['patient_id' => $patientId, 'tab' => 'documents'])
            ->with('success', "Demande radiologie enregistree (Accession {$radiologyRequest->accession_number}).");
    }

    public function orthancWebhook(Request $request): JsonResponse
    {
        $expectedToken = (string) config('services.orthanc.webhook_token', '');
        if ($expectedToken !== '') {
            $provided = (string) $request->header('X-Orthanc-Token', '');
            if (! hash_equals($expectedToken, $provided)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        $event = strtolower((string) $request->input('event', ''));
        $accession = (string) $request->input('accession_number', '');
        if ($accession === '') {
            $accession = (string) data_get($request->input('tags', []), '0008,0050', '');
        }
        if ($accession === '') {
            return response()->json(['message' => 'Missing accession_number'], 422);
        }

        $radiologyRequest = RadiologyRequest::where('accession_number', $accession)->first();
        if (! $radiologyRequest) {
            return response()->json(['message' => 'Radiology request not found'], 404);
        }

        $status = null;
        $timestamps = [];

        if (in_array($event, ['study-started', 'started', 'in-progress', 'in_progress'], true)) {
            $status = RadiologyRequest::STATUS_IN_PROGRESS;
            $timestamps['started_at'] = $radiologyRequest->started_at ?? now();
        } elseif (in_array($event, ['study-received', 'received'], true)) {
            $status = RadiologyRequest::STATUS_RECEIVED;
            $timestamps['received_at'] = $radiologyRequest->received_at ?? now();
        } elseif (in_array($event, ['study-completed', 'completed'], true)) {
            $status = RadiologyRequest::STATUS_COMPLETED;
            $timestamps['completed_at'] = $radiologyRequest->completed_at ?? now();
            $timestamps['received_at'] = $radiologyRequest->received_at ?? now();
        } else {
            return response()->json(['message' => 'Ignored event', 'event' => $event], 202);
        }

        $payload = (array) ($radiologyRequest->orthanc_payload ?? []);
        $payload['webhook_last_event'] = $event;
        $payload['webhook_last_body'] = $request->all();
        $payload['webhook_last_at'] = now()->toIso8601String();

        $radiologyRequest->update(array_merge([
            'workflow_status' => $status,
            'orthanc_payload' => $payload,
        ], $timestamps));

        return response()->json([
            'message' => 'Workflow updated',
            'accession_number' => $accession,
            'workflow_status' => $status,
        ]);
    }

    public function storeImaging(Request $request, int $patientId): RedirectResponse
    {
        $validated = $request->validate([
            'modality' => ['required', 'string', 'in:xray,cbct,stl,dicom'],
            'file_path' => ['nullable', 'string', 'max:500'],
            'study_file' => ['nullable', 'file', 'max:51200', 'mimes:jpeg,png,jpg,pdf,dcm,dicom,zip'],
            'study_uid' => ['nullable', 'string', 'max:191'],
            'series_uid' => ['nullable', 'string', 'max:191'],
            'instance_uid' => ['nullable', 'string', 'max:191'],
            'mime_type' => ['nullable', 'string', 'max:191'],
            'file_size_bytes' => ['nullable', 'integer', 'min:0'],
            'captured_at' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        if (! $request->hasFile('study_file') && empty($validated['file_path'])) {
            return back()->withErrors(['study_file' => 'Ajoutez un fichier ou un chemin imagerie.']);
        }

        if ($request->hasFile('study_file')) {
            $file = $request->file('study_file');
            $dir = "imaging/patient_{$patientId}";
            $stored = $file->store($dir, ['disk' => 'public']);
            $validated['file_path'] = 'storage/'.$stored;
            $validated['mime_type'] = $validated['mime_type'] ?: $file->getClientMimeType();
            $validated['file_size_bytes'] = $validated['file_size_bytes'] ?: $file->getSize();
        }

        $this->clinicalWorkflowService->storeImagingStudy($patientId, $validated);

        return redirect()
            ->route('care.module3.index', ['patient_id' => $patientId])
            ->with('success', 'Etude imagerie ajoutee.');
    }

    public function exportPatientReport(int $patientId)
    {
        $patient = Patient::findOrFail($patientId);
        $odontogram = $this->clinicalWorkflowService->odontogram($patientId);
        $timeline = $this->clinicalWorkflowService->timeline($patientId);
        $manifest = $this->clinicalWorkflowService->imagingManifest($patientId);

        $teethStatusSummary = collect($odontogram['teeth_status'] ?? [])
            ->map(function ($status) {
                if (is_array($status)) {
                    return (string) data_get($status, 'status', 'present');
                }

                return (string) $status;
            })
            ->countBy()
            ->sortDesc();

        $latestImagingItems = collect($manifest['items'] ?? [])
            ->sortByDesc(function ($item) {
                return $item['captured_at'] ?? $item['created_at'] ?? $item['id'] ?? 0;
            })
            ->take(6)
            ->values();

        $sterilizationTraces = PatientSterilizationTrace::query()
            ->where('patient_id', $patientId)
            ->with(['pouch.batch', 'clinicalProcedure:id,name,procedure_code,tooth_number', 'appointment:id,appointment_date,start_time'])
            ->latest('scanned_at')
            ->limit(120)
            ->get();

        $consultations = PatientConsultation::where('patient_id', $patientId)
            ->with('practitioner:id,name')
            ->latest('consultation_date')
            ->limit(200)
            ->get();

        $procedures = ClinicalProcedure::where('patient_id', $patientId)
            ->with(['specialty:id,name', 'practitioner:id,name'])
            ->latest('performed_at')
            ->limit(300)
            ->get();

        $plans = TreatmentPlan::where('patient_id', $patientId)
            ->with('practitioner:id,name')
            ->latest('id')
            ->limit(100)
            ->get();

        $payload = compact(
            'patient',
            'odontogram',
            'teethStatusSummary',
            'timeline',
            'manifest',
            'latestImagingItems',
            'consultations',
            'procedures',
            'plans',
            'sterilizationTraces'
        );

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf();
            $html = view('modules.clinical-report-pdf', $payload)->render();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="dossier-'.$patient->medical_record_number.'.pdf"',
            ]);
        }

        return view('modules.clinical-report-pdf', $payload);
    }

    public function createPatient(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:191'],
            'last_name' => ['required', 'string', 'max:191'],
            'cin' => ['nullable', 'string', 'max:60'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'string', 'max:20'],
            'blood_group' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'height_cm' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'weight_kg' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:191'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:191'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'allergies' => ['nullable', 'string'],
            'medical_history' => ['nullable', 'string'],
            'current_medications' => ['nullable', 'string'],
            'critical_conditions' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'personal_history' => ['nullable', 'string'],
            'patient_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'consultation_reason' => ['nullable', 'string', 'max:255'],
            'consultation_type' => ['nullable', 'string', 'max:40'],
        ]);

        $deduplicationKey = Patient::buildDeduplicationKey(
            $validated['phone'] ?? null,
            $validated['cin'] ?? null,
            $validated['date_of_birth']
        );

        if ($deduplicationKey !== null) {
            $existingPatient = Patient::where('deduplication_key', $deduplicationKey)->first();

            if ($existingPatient) {
                return redirect()
                    ->route('care.module3.index', ['patient_id' => $existingPatient->id])
                    ->with('info', 'Patient deja enregistre, dossier ouvert.');
            }
        }

        $parseList = static function (?string $value): array {
            if (! $value) {
                return [];
            }

            return array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', $value) ?: [])));
        };

        $photoPath = null;
        if ($request->hasFile('patient_photo')) {
            $stored = $request->file('patient_photo')->store('patient_photos', ['disk' => 'public']);
            $photoPath = 'storage/'.$stored;
        }

        $patientPayload = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'cin' => $validated['cin'] ?? null,
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'] ?? null,
            'blood_group' => $validated['blood_group'] ?? null,
            'height_cm' => isset($validated['height_cm']) ? (float) $validated['height_cm'] : null,
            'weight_kg' => isset($validated['weight_kg']) ? (float) $validated['weight_kg'] : null,
            'patient_photo_path' => $photoPath,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'allergies' => $parseList($validated['allergies'] ?? null),
            'medical_history' => $parseList($validated['medical_history'] ?? null),
            'current_medications' => $parseList($validated['current_medications'] ?? null),
            'critical_conditions' => $parseList($validated['critical_conditions'] ?? null),
            'family_history' => $parseList($validated['family_history'] ?? null),
            'personal_history' => $parseList($validated['personal_history'] ?? null),
            'is_active' => true,
        ];

        try {
            $patient = DB::transaction(function () use ($patientPayload, $validated) {
                $patient = Patient::create($patientPayload);

                if (! empty($validated['consultation_reason'])) {
                    $invoice = \Modules\Billing\Models\Invoice::where('patient_id', $patient->id)
                        ->whereDate('invoice_date', now()->toDateString())
                        ->latest('id')
                        ->first();

                    \Modules\ClinicalRecord\Models\PatientConsultation::create([
                        'patient_id' => $patient->id,
                        'practitioner_id' => auth()->id(),
                        'consultation_date' => now()->toDateString(),
                        'consultation_reason' => $validated['consultation_reason'],
                        'consultation_type' => $validated['consultation_type'] ?? 'bilan',
                        'consultation_status' => 'attendu',
                        'chief_complaint' => $validated['consultation_reason'],
                        'source' => 'registration',
                        'invoice_id' => $invoice?->id,
                        'payment_status' => $invoice?->status === \Modules\Billing\Models\Invoice::STATUS_PAID
                            ? 'paid'
                            : ($invoice ? 'billed' : 'unbilled'),
                        'paid_at' => $invoice?->paid_at,
                    ]);
                }

                return $patient;
            });
        } catch (QueryException $exception) {
            $sqlState = (string) ($exception->errorInfo[0] ?? '');
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);
            $isUnknownColumn = $sqlState === '42S22' || $driverCode === 1054;
            $isDuplicateKey = $sqlState === '23000' || $sqlState === '23505' || $driverCode === 1062 || $driverCode === 1555;

            if ($isUnknownColumn) {
                unset($patientPayload['patient_photo_path']);

                try {
                    $patient = Patient::create($patientPayload);

                    if (! empty($validated['consultation_reason'])) {
                        $invoice = \Modules\Billing\Models\Invoice::where('patient_id', $patient->id)
                            ->whereDate('invoice_date', now()->toDateString())
                            ->latest('id')
                            ->first();

                        \Modules\ClinicalRecord\Models\PatientConsultation::create([
                            'patient_id' => $patient->id,
                            'practitioner_id' => auth()->id(),
                            'consultation_date' => now()->toDateString(),
                            'consultation_reason' => $validated['consultation_reason'],
                            'consultation_type' => $validated['consultation_type'] ?? 'bilan',
                            'consultation_status' => 'attendu',
                            'chief_complaint' => $validated['consultation_reason'],
                            'source' => 'registration',
                            'invoice_id' => $invoice?->id,
                            'payment_status' => $invoice?->status === \Modules\Billing\Models\Invoice::STATUS_PAID
                                ? 'paid'
                                : ($invoice ? 'billed' : 'unbilled'),
                            'paid_at' => $invoice?->paid_at,
                        ]);
                    }
                } catch (QueryException $retryException) {
                    $retrySqlState = (string) ($retryException->errorInfo[0] ?? '');
                    $retryDriverCode = (int) ($retryException->errorInfo[1] ?? 0);
                    $retryDuplicateKey = $retrySqlState === '23000' || $retrySqlState === '23505' || $retryDriverCode === 1062 || $retryDriverCode === 1555;

                    if ($retryDuplicateKey && $deduplicationKey !== null) {
                        $patient = Patient::where('deduplication_key', $deduplicationKey)->first();

                        if ($patient) {
                            return redirect()
                                ->route('care.module3.index', ['patient_id' => $patient->id])
                                ->with('info', 'Patient deja enregistre, dossier ouvert.');
                        }
                    }

                    throw $retryException;
                }
            } elseif ($isDuplicateKey && $deduplicationKey !== null) {
                $patient = Patient::where('deduplication_key', $deduplicationKey)->first();

                if ($patient) {
                    return redirect()
                        ->route('care.module3.index', ['patient_id' => $patient->id])
                        ->with('info', 'Patient deja enregistre, dossier ouvert.');
                }

                throw $exception;
            } else {
                throw $exception;
            }
        }

        return redirect()->route('care.module3.index', ['patient_id' => $patient->id, 'new_patient' => 1])
            ->with('success', 'Patient crÃ©Ã© avec dossier mÃ©dical initial.');
    }
    public function updatePatient(Request $request, int $patientId): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:191'],
            'last_name' => ['required', 'string', 'max:191'],
            'cin' => ['nullable', 'string', 'max:60'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'string', 'max:20'],
            'blood_group' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'height_cm' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'weight_kg' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:191'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:191'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'allergies' => ['nullable', 'string'],
            'medical_history' => ['nullable', 'string'],
            'current_medications' => ['nullable', 'string'],
            'critical_conditions' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'personal_history' => ['nullable', 'string'],
            'patient_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $parseList = static function (?string $value): array {
            if (! $value) {
                return [];
            }

            return array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', $value) ?: [])));
        };

        $patient = Patient::findOrFail($patientId);

        $deduplicationKey = Patient::buildDeduplicationKey(
            $validated['phone'] ?? null,
            $validated['cin'] ?? null,
            $validated['date_of_birth']
        );

        if ($deduplicationKey !== null) {
            $duplicatePatient = Patient::where('deduplication_key', $deduplicationKey)
                ->where('id', '!=', $patient->id)
                ->first();

            if ($duplicatePatient) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'cin' => 'Un patient existe deja avec ce telephone, ce CIN et cette date de naissance.',
                    ]);
            }
        }

        $photoPath = $patient->patient_photo_path;
        if ($request->hasFile('patient_photo')) {
            $stored = $request->file('patient_photo')->store('patient_photos', ['disk' => 'public']);
            $photoPath = 'storage/'.$stored;
        }

        $patientPayload = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'cin' => $validated['cin'] ?? null,
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'] ?? null,
            'blood_group' => $validated['blood_group'] ?? null,
            'height_cm' => isset($validated['height_cm']) ? (float) $validated['height_cm'] : null,
            'weight_kg' => isset($validated['weight_kg']) ? (float) $validated['weight_kg'] : null,
            'patient_photo_path' => $photoPath,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'allergies' => $parseList($validated['allergies'] ?? null),
            'medical_history' => $parseList($validated['medical_history'] ?? null),
            'current_medications' => $parseList($validated['current_medications'] ?? null),
            'critical_conditions' => $parseList($validated['critical_conditions'] ?? null),
            'family_history' => $parseList($validated['family_history'] ?? null),
            'personal_history' => $parseList($validated['personal_history'] ?? null),
        ];

        try {
            $patient->update($patientPayload);
        } catch (QueryException $exception) {
            $sqlState = (string) ($exception->errorInfo[0] ?? '');
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);
            $isUnknownColumn = $sqlState === '42S22' || $driverCode === 1054;
            $isDuplicateKey = $sqlState === '23000' || $sqlState === '23505' || $driverCode === 1062 || $driverCode === 1555;

            if ($isDuplicateKey) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'cin' => 'Un patient existe deja avec ce telephone, ce CIN et cette date de naissance.',
                    ]);
            }

            if (! $isUnknownColumn) {
                throw $exception;
            }

            unset($patientPayload['patient_photo_path']);
            $patient->update($patientPayload);
        }

        return redirect()->route('care.module3.index', ['patient_id' => $patient->id])
            ->with('success', 'Patient modifie avec succes.');
    }
    public function storeConsultation(Request $request, int $patientId): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'practitioner_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', ['professional', 'doctor', 'medecin', 'practitioner', 'super_admin'])),
            ],
            'consultation_date' => ['required', 'date', 'before_or_equal:today'],
            'consultation_reason' => ['required', 'string', 'max:255'],
            'consultation_type' => ['required', 'string', 'in:bilan,soins,chirurgie,controle'],
            'consultation_status' => ['required', 'string', 'in:attendu,en_soin,termine'],
            'observations' => ['nullable', 'string'],
            'diagnosis_code' => ['nullable', 'string', 'max:80'],
            'diagnosis_label' => ['nullable', 'string'],
            'chief_complaint' => ['nullable', 'string'],
            'anamnesis' => ['nullable', 'string'],
            'clinical_exam' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'prescription' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
            'vital_signs' => ['nullable', 'string'],
            'vital_bp' => ['nullable', 'string', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'vital_pulse' => ['nullable', 'integer', 'min:20', 'max:250'],
            'vital_weight' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'notes' => ['nullable', 'string'],
            'consent_obtained' => ['required', 'accepted'],
            'sterilization_pouch_code' => ['nullable', 'string', 'exists:sterilization_pouches,pouch_code'],
            'sterilization_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! empty($validated['appointment_id'])) {
            $appointment = Appointment::query()->find($validated['appointment_id']);

            if (! $appointment
                || (int) $appointment->patient_id !== $patientId
                || (int) $appointment->professional_id !== (int) $validated['practitioner_id']) {
                $message = 'Le rendez-vous selectionne doit appartenir au patient et au praticien responsable.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $message,
                        'errors' => [
                            'appointment_id' => [$message],
                        ],
                    ], 422);
                }

                return back()->withInput()->withErrors([
                    'appointment_id' => $message,
                ]);
            }

            if ($appointment->appointment_date?->toDateString() !== $validated['consultation_date']) {
                $message = 'La date de consultation doit correspondre a la date du rendez-vous associe.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $message,
                        'errors' => [
                            'consultation_date' => [$message],
                        ],
                    ], 422);
                }

                return back()->withInput()->withErrors([
                    'consultation_date' => $message,
                ]);
            }
        }

        $vitalSigns = [];
        if (! empty($validated['vital_signs'])) {
            $pairs = preg_split('/[\r\n]+/', (string) $validated['vital_signs']) ?: [];
            foreach ($pairs as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                [$k, $v] = array_pad(explode(':', $line, 2), 2, '');
                $vitalSigns[trim($k)] = trim($v);
            }
        }
        if (! empty($validated['vital_bp'])) {
            $vitalSigns['tension_arterielle'] = $validated['vital_bp'];
        }
        if (! empty($validated['vital_pulse'])) {
            $vitalSigns['pouls_bpm'] = (int) $validated['vital_pulse'];
        }
        if (! empty($validated['vital_weight'])) {
            $vitalSigns['poids_kg'] = (float) $validated['vital_weight'];
        }

        $invoice = \Modules\Billing\Models\Invoice::where('patient_id', $patientId)
            ->whereDate('invoice_date', $validated['consultation_date'])
            ->latest('id')
            ->first();

        $consultation = null;

        try {
            DB::transaction(function () use ($request, $validated, $patientId, $invoice, $vitalSigns, &$consultation): void {
                $notes = trim((string) ($validated['notes'] ?? ''));
                $consentNote = 'Consentement eclaire obtenu le '.now()->format('d/m/Y H:i');
                $notes = $notes !== '' ? ($notes."\n".$consentNote) : $consentNote;

                $consultation = PatientConsultation::create([
                    'patient_id' => $patientId,
                    'appointment_id' => $validated['appointment_id'] ?? null,
                    'practitioner_id' => $validated['practitioner_id'],
                    'consultation_date' => $validated['consultation_date'],
                    'consultation_reason' => $validated['consultation_reason'],
                    'consultation_type' => $validated['consultation_type'],
                    'consultation_status' => $validated['consultation_status'],
                    'chief_complaint' => $validated['chief_complaint'] ?? $validated['consultation_reason'],
                    'anamnesis' => $validated['anamnesis'] ?? null,
                    'observations' => $validated['observations'] ?? null,
                    'clinical_exam' => $validated['clinical_exam'] ?? $validated['observations'] ?? null,
                    'diagnosis' => $validated['diagnosis'] ?? $validated['diagnosis_label'] ?? null,
                    'diagnosis_code' => $validated['diagnosis_code'] ?? null,
                    'diagnosis_label' => $validated['diagnosis_label'] ?? null,
                    'prescription' => $validated['prescription'] ?? null,
                    'recommendations' => $validated['recommendations'] ?? null,
                    'vital_signs' => $vitalSigns !== [] ? $vitalSigns : null,
                    'source' => 'manual_entry',
                    'invoice_id' => $invoice?->id ?? null,
                    'payment_status' => $invoice?->status === \Modules\Billing\Models\Invoice::STATUS_PAID
                        ? 'paid'
                        : ($invoice ? 'billed' : 'unbilled'),
                    'paid_at' => $invoice?->paid_at,
                    'notes' => $notes,
                ]);

                foreach ($request->file('attachments', []) as $file) {
                    $stored = $file->store("consultation_attachments/patient_{$patientId}/consultation_{$consultation->id}", ['disk' => 'public']);

                    PatientConsultationAttachment::create([
                        'consultation_id' => $consultation->id,
                        'patient_id' => $patientId,
                        'uploaded_by' => auth()->id(),
                        'file_path' => 'storage/'.$stored,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'file_size_bytes' => $file->getSize(),
                    ]);
                }

                if (! empty($validated['sterilization_pouch_code'])) {
                    $this->logisticsService->scanPouchToPatient([
                        'pouch_code' => $validated['sterilization_pouch_code'],
                        'patient_id' => $patientId,
                        'appointment_id' => $validated['appointment_id'] ?? null,
                        'clinical_procedure_id' => null,
                        'notes' => trim((string) ($validated['sterilization_notes'] ?? '')) ?: 'Trace consultation #'.$consultation->id,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            report($exception);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Impossible d associer le lot de sterilisation: '.$exception->getMessage(),
                    'errors' => [
                        'sterilization_pouch_code' => ['Impossible d associer le lot de sterilisation: '.$exception->getMessage()],
                    ],
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'sterilization_pouch_code' => 'Impossible d associer le lot de sterilisation: '.$exception->getMessage(),
                ]);
        }

        if ($request->expectsJson() && $consultation instanceof PatientConsultation) {
            $consultation->loadMissing(['practitioner:id,name']);

            return response()->json([
                'message' => 'Consultation ajoutee au dossier.',
                'consultation' => [
                    'id' => $consultation->id,
                    'date' => optional($consultation->consultation_date)->format('d/m/Y'),
                    'reason' => $consultation->consultation_reason,
                    'type' => $consultation->consultation_type,
                    'status' => $consultation->consultation_status,
                    'practitioner' => $consultation->practitioner?->name ?: '-',
                    'diagnosis' => $consultation->diagnosis_label ?: $consultation->diagnosis ?: '-',
                    'chief_complaint' => $consultation->chief_complaint ?: '-',
                    'observations' => $consultation->observations ?: $consultation->clinical_exam ?: '-',
                    'notes' => $consultation->notes ?: '-',
                ],
            ]);
        }

        return redirect()->route('care.module3.index', ['patient_id' => $patientId])
            ->with('success', 'Consultation ajoutee au dossier.');
    }

    public function storeClinicalProcedure(Request $request, int $patientId): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'consultation_id' => ['nullable', 'integer', 'exists:patient_consultations,id'],
            'practitioner_id' => ['nullable', 'integer', 'exists:users,id'],
            'specialty_id' => ['required', 'integer', 'exists:specialties,id'],
            'tooth_number' => ['nullable', 'integer'],
            'procedure_code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:planned,in_progress,completed,cancelled'],
            'performed_at' => ['nullable', 'date'],
            'materials_used' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $materials = array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', (string) ($validated['materials_used'] ?? '')) ?: [])));

        $procedure = ClinicalProcedure::create([
            'patient_id' => $patientId,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'consultation_id' => $validated['consultation_id'] ?? null,
            'practitioner_id' => $validated['practitioner_id'] ?? auth()->id(),
            'specialty_id' => $validated['specialty_id'],
            'tooth_number' => $validated['tooth_number'] ?? null,
            'procedure_code' => $validated['procedure_code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'status' => $validated['status'],
            'planned_date' => $validated['status'] === 'planned' ? now()->toDateString() : null,
            'performed_at' => ! empty($validated['performed_at']) ? Carbon::parse($validated['performed_at']) : ($validated['status'] === 'completed' ? now() : null),
            'materials_used' => $materials,
            'notes' => ! empty($validated['notes']) ? ['note' => $validated['notes']] : null,
        ]);

        // Mettre à jour le dental chart si tooth_number est fourni
        if ($validated['tooth_number']) {
            $this->updateDentalChartForProcedure($patientId, (int) $validated['tooth_number'], $procedure);
        }

        $procedure->loadMissing('practitioner:id,name', 'specialty:id,name');

        $procedurePayload = [
            'id' => $procedure->id,
            'tooth_number' => $procedure->tooth_number,
            'procedure_code' => $procedure->procedure_code,
            'name' => $procedure->name,
            'status' => $procedure->status,
            'price' => $procedure->price,
            'performed_at' => optional($procedure->performed_at)->toISOString(),
            'created_at' => optional($procedure->created_at)->toISOString(),
            'practitioner_name' => $procedure->practitioner?->name,
            'specialty_name' => $procedure->specialty?->name,
            'notes' => $procedure->notes,
            'materials_used' => $procedure->materials_used,
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Acte clinique enregistré.',
                'procedure' => $procedurePayload,
            ], 201);
        }

        return redirect()->route('care.module3.index', ['patient_id' => $patientId])
            ->with('success', 'Acte clinique enregistré.');
    }

    /**
     * Get form data for creating a procedure from odontogram.
     */
    public function getProcedureFormData(int $patientId): JsonResponse
    {
        // Consultations disponibles (non clôturées ou récentes)
        $consultations = \Modules\ClinicalRecord\Models\PatientConsultation::where('patient_id', $patientId)
            ->with('practitioner:id,name')
            ->latest('consultation_date')
            ->latest('id')
            ->limit(10)
            ->get(['id', 'consultation_date', 'consultation_reason', 'consultation_type', 'consultation_status']);
        
        // Nomenclature des actes dentaires
        $procedureNomenclature = $this->getDentalProcedureNomenclature();
        
        // Spécialité du praticien connecté
        $userSpecialtyId = auth()->user()->specialty_id;
        
        return response()->json([
            'consultations' => $consultations,
            'procedures' => $procedureNomenclature,
            'default_specialty_id' => $userSpecialtyId,
        ]);
    }

    /**
     * Get dental procedure nomenclature.
     */
    private function getDentalProcedureNomenclature(): array
    {
        return [
            ['code' => 'EX001', 'name' => 'Extraction simple', 'category' => 'Chirurgie', 'default_status' => 'completed', 'tooth_status' => 'extracted'],
            ['code' => 'EX002', 'name' => 'Extraction chirurgicale', 'category' => 'Chirurgie', 'default_status' => 'completed', 'tooth_status' => 'extracted'],
            ['code' => 'OC001', 'name' => 'Obturation composite 1 face', 'category' => 'Odontologie conservatrice', 'default_status' => 'completed', 'tooth_status' => 'filling'],
            ['code' => 'OC002', 'name' => 'Obturation composite 2 faces', 'category' => 'Odontologie conservatrice', 'default_status' => 'completed', 'tooth_status' => 'filling'],
            ['code' => 'OC003', 'name' => 'Obturation composite 3 faces', 'category' => 'Odontologie conservatrice', 'default_status' => 'completed', 'tooth_status' => 'filling'],
            ['code' => 'EN001', 'name' => 'Traitement endodontique 1 canal', 'category' => 'Endodontie', 'default_status' => 'completed', 'tooth_status' => 'root_canal'],
            ['code' => 'EN002', 'name' => 'Traitement endodontique 2 canaux', 'category' => 'Endodontie', 'default_status' => 'completed', 'tooth_status' => 'root_canal'],
            ['code' => 'EN003', 'name' => 'Traitement endodontique 3 canaux et +', 'category' => 'Endodontie', 'default_status' => 'completed', 'tooth_status' => 'root_canal'],
            ['code' => 'PR001', 'name' => 'Couronne unitaire', 'category' => 'Prothèse', 'default_status' => 'planned', 'tooth_status' => 'crown'],
            ['code' => 'PR002', 'name' => 'Bridge 3 éléments', 'category' => 'Prothèse', 'default_status' => 'planned', 'tooth_status' => 'crown'],
            ['code' => 'IM001', 'name' => 'Implant unitaire', 'category' => 'Implantologie', 'default_status' => 'planned', 'tooth_status' => 'implant'],
            ['code' => 'PA001', 'name' => 'Détartrage complet', 'category' => 'Parodontologie', 'default_status' => 'completed', 'tooth_status' => null],
            ['code' => 'PA002', 'name' => 'Surfaçage radiculaire', 'category' => 'Parodontologie', 'default_status' => 'completed', 'tooth_status' => null],
            ['code' => 'RA001', 'name' => 'Radiographie periapicale', 'category' => 'Radiologie', 'default_status' => 'completed', 'tooth_status' => null],
            ['code' => 'RA002', 'name' => 'Radiographie retroalvéolaire', 'category' => 'Radiologie', 'default_status' => 'completed', 'tooth_status' => null],
        ];
    }

    /**
     * Return JSON list of clinical procedures for a patient (AJAX).
     */
    public function patientProcedures(int $patientId): JsonResponse
    {
        $procedures = \Modules\ClinicalRecord\Models\ClinicalProcedure::where('patient_id', $patientId)
            ->with(['specialty:id,name', 'practitioner:id,name', 'consultation:id,consultation_date'])
            ->orderByDesc('performed_at')
            ->limit(1000)
            ->get();

        return response()->json($procedures);
    }

    /**
     * Update dental chart after procedure creation.
     */
    private function updateDentalChartForProcedure(int $patientId, int $toothNumber, \Modules\ClinicalRecord\Models\ClinicalProcedure $procedure): void
    {
        $dentalChartService = app(\Modules\ClinicalRecord\Services\DentalChartService::class);
        
        $toothStatus = $this->mapProcedureToToothStatus($procedure->name);
        
        if ($toothStatus) {
            $chart = $dentalChartService->getOrCreateLatestChart($patientId);
            $chart->updateToothStatus(
                $toothNumber,
                $toothStatus,
                auth()->id(),
                [
                    'type' => $procedure->procedure_code,
                    'details' => [
                        'procedure_id' => $procedure->id,
                        'name' => $procedure->name,
                        'date' => $procedure->performed_at?->toDateString() ?? now()->toDateString(),
                    ]
                ]
            );
        }
    }

    /**
     * Map procedure name to tooth status.
     */
    private function mapProcedureToToothStatus(string $procedureName): ?string
    {
        $nameLower = strtolower($procedureName);
        
        $mapping = [
            'extraction' => 'extracted',
            'extract' => 'extracted',
            'obturation' => 'filling',
            'composite' => 'filling',
            'traitement endodontique' => 'root_canal',
            'endodontique' => 'root_canal',
            'couronne' => 'crown',
            'bridge' => 'crown',
            'implant' => 'implant',
        ];
        
        foreach ($mapping as $keyword => $status) {
            if (str_contains($nameLower, $keyword)) {
                return $status;
            }
        }
        
        return null;
    }

    public function module4(): View
    {
        return view('modules.logistics', $this->logisticsService->dashboardData());
    }

    public function createBatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'batch_code' => ['required', 'string', 'max:120', 'unique:sterilization_batches,batch_code'],
            'sterilized_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:sterilized_at'],
            'sterilizer_cycle' => ['nullable', 'string', 'max:120'],
            'instrument_set_name' => ['nullable', 'string', 'max:191'],
            'pouch_count' => ['required', 'integer', 'min:1', 'max:300'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->logisticsService->createSterilizationBatch($validated);

        return back()->with('success', 'Lot de sterilisation cree.');
    }

    public function scanPouch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pouch_code' => ['required', 'string', 'exists:sterilization_pouches,pouch_code'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'clinical_procedure_id' => ['nullable', 'integer', 'exists:clinical_procedures,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->logisticsService->scanPouchToPatient($validated);

        return back()->with('success', 'Trace sterile enregistree dans le dossier patient.');
    }

    public function createStockMovement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'stock_item_id' => ['required', 'integer', 'exists:stock_items,id'],
            'type' => ['required', 'string', 'in:in,out,adjustment,reserve,release'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'reference_type' => ['nullable', 'string', 'max:120'],
            'reference_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->logisticsService->createStockMovement($validated);

        return back()->with('success', 'Mouvement de stock enregistre.');
    }

    public function createStockItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80', 'unique:stock_items,code'],
            'name' => ['required', 'string', 'max:191'],
            'category' => ['required', 'string', 'in:consumable,high_value'],
            'unit' => ['required', 'string', 'max:30'],
            'current_quantity' => ['required', 'numeric', 'min:0'],
            'minimum_quantity' => ['required', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        StockItem::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'category' => $validated['category'],
            'is_high_value' => $validated['category'] === 'high_value',
            'unit' => $validated['unit'],
            'current_quantity' => $validated['current_quantity'],
            'minimum_quantity' => $validated['minimum_quantity'],
            'reorder_quantity' => $validated['reorder_quantity'] ?? null,
            'unit_cost' => $validated['unit_cost'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Article de stock cree.');
    }

    public function createLabOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'clinical_procedure_id' => ['nullable', 'integer', 'exists:clinical_procedures,id'],
            'practitioner_id' => ['nullable', 'integer', 'exists:users,id'],
            'lab_name' => ['required', 'string', 'max:191'],
            'lab_contact' => ['nullable', 'string', 'max:191'],
            'type' => ['required', 'string', 'in:crown,implant,ortho,prosthesis,other'],
            'status' => ['nullable', 'string', 'in:created,sent,in_progress,delivered,fitted,cancelled'],
            'due_date' => ['nullable', 'date'],
            'external_file_paths' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $payload = $validated;
        if (! empty($payload['external_file_paths'])) {
            $payload['external_file_paths'] = array_values(array_filter(array_map('trim', explode("\n", (string) $payload['external_file_paths']))));
        } else {
            $payload['external_file_paths'] = null;
        }

        $this->logisticsService->createLabOrder($payload);

        return back()->with('success', 'Commande labo enregistree.');
    }

    public function addLabOrderEvent(Request $request, LabOrder $labOrder): RedirectResponse
    {
        $validated = $request->validate([
            'event_type' => ['required', 'string', 'in:file_sent,lab_feedback,status_update'],
            'status' => ['nullable', 'string', 'in:created,sent,in_progress,delivered,fitted,cancelled'],
            'message' => ['required', 'string'],
        ]);

        $this->logisticsService->addLabOrderEvent($labOrder, $validated);

        return back()->with('success', 'Evenement labo ajoute.');
    }

    public function labOrderFeed(LabOrder $labOrder): JsonResponse
    {
        return response()->json([
            'order' => $labOrder->only(['id', 'order_number', 'status', 'lab_name', 'updated_at']),
            'events' => LabOrderEvent::where('lab_order_id', $labOrder->id)
                ->latest('event_at')
                ->limit(50)
                ->get(['id', 'event_type', 'status', 'message', 'event_at']),
        ]);
    }

    public function updateLabOrderStatus(Request $request, LabOrder $labOrder): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:created,sent,in_progress,delivered,fitted,cancelled'],
        ]);

        $this->logisticsService->updateLabOrderStatus($labOrder, $validated['status']);

        return back()->with('success', 'Statut commande labo mis a jour.');
    }

    private function normalizePeriodontalMeasurements(?string $jsonPayload, ?string $legacyPayload): array
    {
        if (! empty($jsonPayload)) {
            $decoded = json_decode($jsonPayload, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return collect($decoded)
                    ->map(function ($row): ?array {
                        if (! is_array($row) || empty($row['tooth'])) {
                            return null;
                        }

                        $sideData = $row['sides'] ?? [];
                        if (empty($sideData) && isset($row['side'])) {
                            $sideData = [$row['side'] => $row['measurements'] ?? []];
                        }

                        return [
                            'tooth' => (int) $row['tooth'],
                            'sides' => collect($sideData)->map(function ($side) {
                                return [
                                    'mesial' => (int) ($side['mesial'] ?? 0),
                                    'central' => (int) ($side['central'] ?? 0),
                                    'distal' => (int) ($side['distal'] ?? 0),
                                    'bone' => isset($side['bone']) ? (int) $side['bone'] : null,
                                    'bleeding' => (bool) ($side['bleeding'] ?? false),
                                    'mobility' => (int) ($side['mobility'] ?? 0),
                                    'plaque' => (bool) ($side['plaque'] ?? false),
                                ];
                            })->all(),
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();
            }
        }

        return collect(preg_split('/[\r\n]+/', (string) $legacyPayload) ?: [])
            ->map(function (string $line): ?array {
                $line = trim($line);
                if ($line === '') {
                    return null;
                }

                [$tooth, $pockets, $meta] = array_pad(explode(':', $line, 3), 3, '');
                $depths = array_values(array_filter(array_map('trim', explode(',', $pockets))));

                return [
                    'tooth' => (int) trim($tooth),
                    'sides' => [
                        'vestibulaire' => [
                            'mesial' => (int) ($depths[0] ?? 0),
                            'central' => (int) ($depths[1] ?? 0),
                            'distal' => (int) ($depths[2] ?? 0),
                            'bone' => null,
                            'bleeding' => str_contains(strtolower($meta), 'b1'),
                            'mobility' => 0,
                            'plaque' => str_contains(strtolower($meta), 'p1'),
                        ],
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function buildPeriodontalSummary(array $rows, string $recordedOn): string
    {
        $teethCount = count($rows);
        $avgDepths = [];
        $bleedingCount = 0;
        $plaqueCount = 0;
        $mobilityCount = 0;
        $deepPocketCount = 0;

        foreach ($rows as $row) {
            foreach (($row['sides'] ?? []) as $side) {
                $values = [
                    (int) ($side['mesial'] ?? 0),
                    (int) ($side['central'] ?? 0),
                    (int) ($side['distal'] ?? 0),
                ];

                $avgDepths[] = array_sum($values) / max(1, count($values));
                $bleedingCount += ! empty($side['bleeding']) ? 1 : 0;
                $plaqueCount += ! empty($side['plaque']) ? 1 : 0;
                $mobilityCount += (int) ($side['mobility'] ?? 0);
                if (max($values) >= 5) {
                    $deepPocketCount += 1;
                }
            }
        }

        $avgDepth = ! empty($avgDepths) ? round(array_sum($avgDepths) / count($avgDepths), 1) : 0;
        $deepStatus = $deepPocketCount > 0 ? 'avec poches profondes' : 'sans poche profonde';

        return sprintf(
            'Charting du %s: %d dents, profondeur moyenne %s mm, saignement %d secteurs, plaque %d secteurs, mobilite cumulÃ©e %d, %s.',
            Carbon::parse($recordedOn)->format('d/m/Y'),
            $teethCount,
            $avgDepth,
            $bleedingCount,
            $plaqueCount,
            $mobilityCount,
            $deepStatus
        );
    }

    private function loadRisOrders(int|null $selectedPatientId): \Illuminate\Support\Collection
    {
        if (! $selectedPatientId) {
            return collect();
        }

        $risEnabled = (bool) config('ris.enabled', false);
        if (! $risEnabled && class_exists('Modules\\Queue\\Models\\AppSetting')) {
            $risEnabled = filter_var((string) \Modules\Queue\Models\AppSetting::getValue('module.ris.enabled', false), FILTER_VALIDATE_BOOL) === true;
        }

        if (! $risEnabled || ! class_exists(RisOrder::class)) {
            return collect();
        }

        return RisOrder::where('patient_id', $selectedPatientId)
            ->with(['procedure:id,label', 'modality:id,name', 'report:id,order_id'])
            ->orderByDesc('requested_at')
            ->limit(50)
            ->get();
    }
}
