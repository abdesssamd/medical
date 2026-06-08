<?php

namespace Modules\Appointment\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Appointment\Events\AppointmentConsulted;
use Modules\Appointment\Listeners\CreateCommissionOnAppointmentConsulted;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AppointmentConsulted::class => [
            CreateCommissionOnAppointmentConsulted::class,
        ],
    ];
}
