<?php

namespace Modules\Appointment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Appointment\Models\Appointment;

class AppointmentConsulted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public ?int $actorId = null
    ) {
    }
}
