<?php

namespace Modules\Appointment\Services;

use Illuminate\Support\Facades\DB;
use Modules\Appointment\Contracts\QueueBridgeInterface;
use Modules\Appointment\Events\AppointmentConsulted;
use Modules\Appointment\Models\Appointment;

class AppointmentStatusService
{
    public function __construct(private readonly QueueBridgeInterface $queueBridge)
    {
    }

    public function markConsulted(Appointment $appointment, ?int $actorId = null): Appointment
    {
        if ($appointment->status === 'consulted' && ! empty($appointment->queue_ticket_id)) {
            return $appointment->fresh();
        }

        return DB::transaction(function () use ($appointment, $actorId): Appointment {
            $appointment->update([
                'status' => 'consulted',
                'consulted_at' => now(),
            ]);

            $fresh = $appointment->fresh();
            event(new AppointmentConsulted($fresh, $actorId));
            $this->queueBridge->checkInFromAppointment($fresh);

            return $appointment->fresh();
        });
    }
}
