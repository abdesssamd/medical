<?php

namespace Modules\Appointment\Services;

use Illuminate\Support\Facades\Log;
use Modules\Appointment\Contracts\QueueBridgeInterface;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\Setting;
use Modules\Queue\Contracts\QueueManagerInterface;

class QueueModuleBridgeService implements QueueBridgeInterface
{
    public function __construct(private readonly QueueManagerInterface $queueManager)
    {
    }

    public function checkInFromAppointment(Appointment $appointment): void
    {
        // Idempotent: do not enqueue twice for the same appointment.
        if (! empty($appointment->queue_ticket_id)) {
            return;
        }

        $setting = Setting::where('professional_id', $appointment->professional_id)->first();
        if (! $setting || empty($setting->queue_service_id)) {
            return;
        }

        try {
            $patientKey = (int) $appointment->id;
            $ticket = $this->queueManager->addToQueue($patientKey, (int) $setting->queue_service_id);

            $appointment->update([
                'queue_ticket_id' => $ticket->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('appointment.queue_bridge_failed', [
                'appointment_id' => $appointment->id,
                'professional_id' => $appointment->professional_id,
                'queue_service_id' => $setting->queue_service_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
