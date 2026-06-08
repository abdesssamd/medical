<?php

namespace Modules\Appointment\Http\Controllers\Web;

use App\Models\Patient;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Actions\CreateAppointmentAction;
use Modules\Appointment\Contracts\QueueBridgeInterface;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\PatientJourney;
use Modules\Appointment\Models\SecretaryNote;
use Modules\Appointment\Services\PatientFlowService;
use Modules\Appointment\Services\SecretaryDashboardService;
use Modules\Appointment\Services\SecretaryNoteService;

class SecretaryDashboardController extends Controller
{
    public function __construct(
        private readonly SecretaryDashboardService $dashboardService,
        private readonly SecretaryNoteService $noteService,
        private readonly CreateAppointmentAction $createAppointmentAction,
        private readonly QueueBridgeInterface $queueBridge,
        private readonly PatientFlowService $patientFlowService,
    ) {}

    /**
     * Affiche le dashboard secrétaire action-oriented.
     */
    public function index(Request $request)
    {
        if (!$request->user()->isSecretary()) {
            abort(403);
        }

        $date = $request->query('date', today()->toDateString());
        $professionalId = $request->integer('professional_id', null);

        $dashboardData = $this->dashboardService->getDashboardData($date, $professionalId);
        $professionals = User::query()
            ->whereIn('role', ['professional', 'doctor', 'medecin', 'practitioner', 'super_admin'])
            ->orderBy('name')
            ->get(['id', 'name', 'professional_title']);

        return view('appointment::secretary.dashboard-action-oriented', compact('dashboardData', 'date', 'professionalId', 'professionals'));
    }

    /**
     * Récupère les données du dashboard en JSON (pour mise à jour auto).
     */
    public function getData(Request $request): JsonResponse
    {
        $date = $request->query('date', today()->toDateString());
        $professionalId = $request->integer('professional_id', null);

        $dashboardData = $this->dashboardService->getDashboardData($date, $professionalId);

        return response()->json($dashboardData);
    }

    /**
     * Crée une note rapide contextuelle.
     */
    public function createNote(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'tag' => 'required|string|in:document_missing,insurance_verify,consent_pending,payment_issue,urgent,other',
            'message' => 'required|string|max:500',
            'priority' => 'nullable|string|in:critical,high,normal',
        ]);

        $note = $this->noteService->createNote(
            $appointment,
            $validated['tag'],
            $validated['message'],
            $request->user(),
            $validated['priority'] ?? SecretaryNote::PRIORITY_NORMAL
        );

        return response()->json([
            'success' => true,
            'note' => $note->load('createdBy'),
            'message' => 'Note créée et praticien notifié.',
        ]);
    }

    /**
     * Marque une note comme lue.
     */
    public function markNoteAsRead(Request $request, SecretaryNote $note): JsonResponse
    {
        $this->noteService->markAsRead($note);

        return response()->json([
            'success' => true,
            'message' => 'Note marquée comme lue.',
        ]);
    }

    /**
     * Récupère les notes non lues du praticien.
     */
    public function getUnreadNotes(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'professional') {
            $notes = $this->noteService->getUnreadNotesForPractitioner($user);
        } else {
            $notes = collect([]);
        }

        return response()->json([
            'unread_count' => $notes->count(),
            'notes' => $notes->map(fn ($n) => [
                'id' => $n->id,
                'tag' => $n->tag,
                'message' => $n->message,
                'priority' => $n->priority,
                'created_by' => $n->createdBy->name,
                'created_at' => $n->created_at->toDateTimeString(),
            ]),
        ]);
    }

    public function searchPatients(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['items' => []]);
        }

        $like = "%{$query}%";

        $patients = Patient::query()
            ->select(['id', 'first_name', 'last_name', 'phone', 'medical_record_number'])
            ->where(function ($builder) use ($like): void {
                $builder
                    ->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('cin', 'like', $like)
                    ->orWhere('medical_record_number', 'like', $like);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(8)
            ->get()
            ->map(fn (Patient $patient) => [
                'id' => $patient->id,
                'name' => $patient->full_name,
                'phone' => $patient->phone,
                'mrn' => $patient->medical_record_number,
            ])
            ->values();

        return response()->json(['items' => $patients]);
    }

    public function quickCreateAppointment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'professional_id' => 'required|exists:users,id',
            'appointment_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
            'patient_id' => 'nullable|exists:patients,id',
            'first_name' => 'nullable|string|max:120',
            'last_name' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'immediate_checkin' => 'nullable|boolean',
        ]);

        $patient = null;
        if (!empty($validated['patient_id'])) {
            $patient = Patient::find($validated['patient_id']);
        } elseif (!empty($validated['first_name']) || !empty($validated['last_name'])) {
            $patient = Patient::create([
                'first_name' => $validated['first_name'] ?? 'Patient',
                'last_name' => $validated['last_name'] ?? '',
                'phone' => $validated['phone'] ?? null,
                'date_of_birth' => now()->subYears(30),
            ]);
        }

        if (!$patient) {
            return response()->json([
                'message' => 'Sélectionnez un patient existant ou créez une fiche express.',
            ], 422);
        }

        $appointment = $this->createAppointmentAction->execute([
            'professional_id' => (int) $validated['professional_id'],
            'secretary_id' => $request->user()?->id,
            'patient_id' => $patient->id,
            'patient_name' => $patient->full_name,
            'patient_phone' => $patient->phone,
            'appointment_date' => $validated['appointment_date'],
            'start_time' => $validated['start_time'],
            'notes' => $validated['notes'] ?? null,
        ], (int) $request->user()->id);

        if ($request->boolean('immediate_checkin')) {
            $this->queueBridge->checkInFromAppointment($appointment);
            $this->patientFlowService->transition($appointment->fresh(), PatientJourney::STATUS_ARRIVED);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous créé.',
            'appointment_id' => $appointment->id,
        ], 201);
    }

    public function updateSchedule(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'appointment_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
        ]);

        $appointment->update([
            'appointment_date' => $validated['appointment_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Créneau mis à jour.',
        ]);
    }

    public function updateFlowAction(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:check_in,start,pause,no_show',
        ]);

        $journey = PatientJourney::query()->firstOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'patient_id' => $appointment->patient_id,
                'current_status' => PatientJourney::STATUS_BOOKED,
            ]
        );

        if ($validated['action'] === 'check_in') {
            $this->queueBridge->checkInFromAppointment($appointment);
            $this->patientFlowService->transition($appointment->fresh(), PatientJourney::STATUS_ARRIVED);
        }

        if ($validated['action'] === 'start') {
            if (empty($appointment->queue_ticket_id)) {
                $this->queueBridge->checkInFromAppointment($appointment);
                $appointment = $appointment->fresh();
            }

            $this->patientFlowService->transition($appointment, PatientJourney::STATUS_ARRIVED);
            $this->patientFlowService->transition($appointment, PatientJourney::STATUS_IN_CARE);
        }

        if ($validated['action'] === 'pause') {
            if (empty($appointment->queue_ticket_id)) {
                $this->queueBridge->checkInFromAppointment($appointment);
                $appointment = $appointment->fresh();
            }

            $this->patientFlowService->transition($appointment, PatientJourney::STATUS_ARRIVED);
        }

        if ($validated['action'] === 'no_show') {
            $appointment->update(['status' => 'no_show']);
            $journey->update([
                'current_status' => PatientJourney::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Action appliquée.',
        ]);
    }
}
