<?php

namespace Modules\Queue\Services;

use Illuminate\Support\Facades\DB;
use Modules\Appointment\Models\Appointment;
use Modules\Queue\Models\QueuePriority;
use Modules\Queue\Models\Ticket;

class QueueManagementService
{
    /**
     * Réordonne manuellement la file d'attente.
     */
    public function reorderQueue(Appointment $appointment, int $newPosition, ?string $reason = null, ?\App\Models\User $overriddenBy = null): QueuePriority
    {
        return DB::transaction(function () use ($appointment, $newPosition, $reason, $overriddenBy) {
            $priority = QueuePriority::updateOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'priority_level' => QueuePriority::PRIORITY_HIGH,
                    'position' => $newPosition,
                    'override_reason' => $reason,
                    'overridden_by' => $overriddenBy?->id,
                    'overridden_at' => now(),
                ]
            );

            \Log::info('queue.reordered', [
                'appointment_id' => $appointment->id,
                'patient' => $appointment->patient?->full_name,
                'new_position' => $newPosition,
                'reason' => $reason,
                'overridden_by' => $overriddenBy?->name,
            ]);

            return $priority;
        });
    }

    /**
     * Définit le niveau de priorité d'un patient.
     */
    public function setPriority(Appointment $appointment, string $priorityLevel, ?string $reason = null, ?\App\Models\User $changedBy = null): QueuePriority
    {
        return DB::transaction(function () use ($appointment, $priorityLevel, $reason, $changedBy) {
            $priority = QueuePriority::updateOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'priority_level' => $priorityLevel,
                    'override_reason' => $reason,
                    'overridden_by' => $changedBy?->id,
                    'overridden_at' => now(),
                ]
            );

            \Log::info('queue.priority_changed', [
                'appointment_id' => $appointment->id,
                'priority_level' => $priorityLevel,
                'reason' => $reason,
                'changed_by' => $changedBy?->name,
            ]);

            return $priority;
        });
    }

    /**
     * Récupère la file ordonnée avec priorités.
     */
    public function getOrderedQueue(string $date, int $serviceId): array
    {
        $priorities = QueuePriority::whereHas('appointment', function ($q) use ($date, $serviceId) {
            $q->whereDate('appointment_date', $date);
        })->get()->keyBy('appointment_id');

        $tickets = Ticket::whereDate('ticket_date', $date)
            ->where('service_id', $serviceId)
            ->with('appointment')
            ->get();

        $queue = $tickets->map(function (Ticket $ticket) use ($priorities) {
            $priority = $priorities->get($ticket->appointment_id);
            $appointment = $ticket->appointment;

            return [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'appointment_id' => $appointment?->id,
                'patient_name' => $appointment?->patient?->full_name ?? 'Unknown',
                'priority_level' => $priority?->priority_level ?? QueuePriority::PRIORITY_NORMAL,
                'status' => $ticket->status,
                'arrived_at' => $ticket->arrived_at?->toDateTimeString(),
                'wait_minutes' => $this->calculateWaitMinutes($ticket),
                'is_late' => $this->isLateThreshold($ticket),
                'position' => $priority?->position ?? $ticket->sequence_number,
            ];
        })->sortBy('position')->values();

        return [
            'total' => $queue->count(),
            'waiting' => $queue->where('status', 'waiting')->count(),
            'called' => $queue->where('status', 'called')->count(),
            'served' => $queue->where('status', 'served')->count(),
            'queue' => $queue,
        ];
    }

    /**
     * Détecte les patients en attente excessive.
     */
    public function getEscalatedTickets(int $serviceId, int $thresholdMinutes = 20): array
    {
        $tickets = Ticket::where('service_id', $serviceId)
            ->whereDate('ticket_date', today())
            ->where('status', 'waiting')
            ->whereNotNull('arrived_at')
            ->get();

        return $tickets->filter(function (Ticket $ticket) use ($thresholdMinutes) {
            return now()->diffInMinutes($ticket->arrived_at) > $thresholdMinutes;
        })->map(function (Ticket $ticket) {
            return [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'wait_minutes' => now()->diffInMinutes($ticket->arrived_at),
                'escalation_level' => $this->getEscalationLevel($ticket),
            ];
        })->values()->all();
    }

    private function calculateWaitMinutes(Ticket $ticket): int
    {
        if (!$ticket->arrived_at) {
            return 0;
        }
        return now()->diffInMinutes($ticket->arrived_at);
    }

    private function isLateThreshold(Ticket $ticket, int $threshold = 20): bool
    {
        return $this->calculateWaitMinutes($ticket) > $threshold;
    }

    private function getEscalationLevel(Ticket $ticket): string
    {
        $waitMinutes = $this->calculateWaitMinutes($ticket);
        if ($waitMinutes > 60) {
            return 'critical';
        }
        if ($waitMinutes > 40) {
            return 'high';
        }
        if ($waitMinutes > 20) {
            return 'medium';
        }
        return 'normal';
    }
}
