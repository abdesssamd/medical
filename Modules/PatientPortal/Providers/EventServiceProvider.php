<?php

namespace Modules\PatientPortal\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\PatientPortal\Listeners\CreatePatientPortalAccessForCompletedReport;
use Modules\RIS\Events\RisOrderCompleted;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        RisOrderCompleted::class => [
            CreatePatientPortalAccessForCompletedReport::class,
        ],
    ];
}
