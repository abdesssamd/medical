<?php

namespace Modules\Appointment\Listeners;

use Modules\Appointment\Events\AppointmentConsulted;
use Modules\Appointment\Services\CommissionService;

class CreateCommissionOnAppointmentConsulted
{
    public function __construct(private readonly CommissionService $commissionService)
    {
    }

    public function handle(AppointmentConsulted $event): void
    {
        $this->commissionService->registerFromConsultedAppointment($event->appointment);
    }
}
