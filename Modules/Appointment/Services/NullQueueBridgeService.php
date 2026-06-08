<?php

namespace Modules\Appointment\Services;

use Modules\Appointment\Contracts\QueueBridgeInterface;
use Modules\Appointment\Models\Appointment;

class NullQueueBridgeService implements QueueBridgeInterface
{
    public function checkInFromAppointment(Appointment $appointment): void
    {
        // Intentionally empty: replace with real Queue integration when module is linked.
    }
}
