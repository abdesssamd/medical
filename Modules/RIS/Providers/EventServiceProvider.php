<?php

namespace Modules\RIS\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\RIS\Events\RisOrderCompleted;
use Modules\RIS\Listeners\SyncRisOrderToDentalRecord;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        RisOrderCompleted::class => [
            SyncRisOrderToDentalRecord::class,
        ],
    ];
}
