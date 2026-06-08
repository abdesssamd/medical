<?php

namespace Modules\Appointment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Contracts\QueueBridgeInterface;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Services\PatientFlowService;

class PatientFlowController extends Controller
{
    public function __construct(
        private readonly PatientFlowService $patientFlowService,
        private readonly QueueBridgeInterface $queueBridge
    ) {
    }

    public function board(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'professional_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $date = $validated['date'] ?? now()->toDateString();

        return response()->json(
            $this->patientFlowService->board($date, $validated['professional_id'] ?? null)
        );
    }

    public function checkIn(Appointment $appointment): JsonResponse
    {
        if (empty($appointment->queue_ticket_id)) {
            $this->queueBridge->checkInFromAppointment($appointment);
            $appointment = $appointment->fresh();
        }

        $journey = $this->patientFlowService->transition($appointment, 'arrived');

        return response()->json([
            'message' => 'Patient marque comme arrive.',
            'journey' => $journey,
        ]);
    }

    public function transition(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:arrived,in_care,awaiting_payment,completed'],
        ]);

        $journey = $this->patientFlowService->transition($appointment, $validated['status']);

        return response()->json([
            'message' => 'Statut de flux mis a jour.',
            'journey' => $journey,
        ]);
    }
}
