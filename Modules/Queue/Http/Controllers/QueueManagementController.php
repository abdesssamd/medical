<?php

namespace Modules\Queue\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Models\Appointment;
use Modules\Queue\Models\QueuePriority;
use Modules\Queue\Services\QueueManagementService;

class QueueManagementController extends Controller
{
    public function __construct(private readonly QueueManagementService $queueService) {}

    /**
     * Récupère la file ordonnée pour une date et un service.
     */
    public function getOrderedQueue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'service_id' => 'required|integer',
        ]);

        $queue = $this->queueService->getOrderedQueue($validated['date'], $validated['service_id']);

        return response()->json($queue);
    }

    /**
     * Réordonne manuellement un patient dans la file.
     */
    public function reorder(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'new_position' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $priority = $this->queueService->reorderQueue(
                $appointment,
                $validated['new_position'],
                $validated['reason'] ?? null,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'priority' => $priority,
                'message' => 'File réordonnée avec audit trail.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Définit la priorité d'un patient.
     */
    public function setPriority(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'priority_level' => 'required|string|in:critical,high,normal,low',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $priority = $this->queueService->setPriority(
                $appointment,
                $validated['priority_level'],
                $validated['reason'] ?? null,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'priority' => $priority,
                'message' => 'Priorité définie.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Récupère les tickets en escalade.
     */
    public function getEscalated(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer',
            'threshold_minutes' => 'nullable|integer|min:5',
        ]);

        $escalated = $this->queueService->getEscalatedTickets(
            $validated['service_id'],
            $validated['threshold_minutes'] ?? 20
        );

        return response()->json([
            'count' => count($escalated),
            'tickets' => $escalated,
        ]);
    }
}
