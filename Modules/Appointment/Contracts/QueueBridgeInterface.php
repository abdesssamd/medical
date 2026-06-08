<?php

namespace Modules\Appointment\Contracts;

use Modules\Appointment\Models\Appointment;

interface QueueBridgeInterface
{
    public function checkInFromAppointment(Appointment $appointment): void;
}
