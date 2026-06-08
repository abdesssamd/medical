<?php

namespace Modules\Queue\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Queue\Events\PatientCheckedIn;
use Modules\Queue\Listeners\LogPatientCheckedIn;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PatientCheckedIn::class => [
            LogPatientCheckedIn::class,
        ],
    ];
}
