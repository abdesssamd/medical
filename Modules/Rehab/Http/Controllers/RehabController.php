<?php

namespace Modules\Rehab\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Rehab\Models\RehabPrescription;
use Modules\Rehab\Services\RehabService;

class RehabController extends Controller
{
    public function __construct(
        private readonly RehabService $rehabService,
    ) {
    }

    public function dashboard(int $patientId): JsonResponse
    {
        return response()->json($this->rehabService->patientDashboard($patientId));
    }

    public function storePrescription(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'diagnosis' => ['required', 'string', 'max:500'],
            'prescribed_sessions_count' => ['required', 'integer', 'min:1', 'max:200'],
            'objectives' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:pending,in_progress'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['patient_id'] = $patientId;

        $prescription = $this->rehabService->storePrescription($validated, auth()->id());

        return response()->json([
            'message' => 'Prescription de rééducation créée avec succès.',
            'prescription' => $prescription->fresh(['doctor', 'sessions', 'evaluations']),
        ], 201);
    }

    public function updatePrescription(Request $request, int $prescriptionId): JsonResponse
    {
        $prescription = RehabPrescription::findOrFail($prescriptionId);

        $validated = $request->validate([
            'diagnosis' => ['sometimes', 'string', 'max:500'],
            'prescribed_sessions_count' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'objectives' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'in:pending,in_progress,completed,cancelled'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $prescription = $this->rehabService->updatePrescription($prescription, $validated);

        return response()->json([
            'message' => 'Prescription mise à jour.',
            'prescription' => $prescription,
        ]);
    }

    public function storeEvaluation(Request $request, int $prescriptionId): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:initial,intermediate,final'],
            'evaluation_date' => ['nullable', 'date'],
            'goniometry' => ['nullable', 'array'],
            'muscle_testing' => ['nullable', 'array'],
            'functional_tests' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['prescription_id'] = $prescriptionId;

        $evaluation = $this->rehabService->storeEvaluation($validated);

        return response()->json([
            'message' => 'Bilan enregistré avec succès.',
            'evaluation' => $evaluation->fresh(),
        ], 201);
    }

    public function storeSession(Request $request, int $prescriptionId): JsonResponse
    {
        $validated = $request->validate([
            'session_date' => ['nullable', 'date'],
            'pain_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'exercises_performed' => ['nullable', 'array'],
            'status' => ['nullable', 'in:planned,completed,cancelled,missed'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:300'],
        ]);

        $validated['prescription_id'] = $prescriptionId;

        try {
            $session = $this->rehabService->storeSession($validated, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Séance validée avec succès.',
            'session' => $session->fresh(['physiotherapist']),
            'stats' => $this->rehabService->computeStats($session->prescription),
        ], 201);
    }

    public function progressStats(int $prescriptionId): JsonResponse
    {
        $prescription = RehabPrescription::with(['sessions' => fn ($q) => $q->orderBy('session_number')])->findOrFail($prescriptionId);

        return response()->json([
            'prescription' => $prescription,
            'stats' => $this->rehabService->computeStats($prescription),
        ]);
    }
}
