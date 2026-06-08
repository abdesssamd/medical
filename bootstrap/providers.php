<?php

return [
    App\Providers\AppServiceProvider::class,
    Modules\Queue\Providers\QueueServiceProvider::class,
    Modules\Queue\Providers\EventServiceProvider::class,
    Modules\Appointment\Providers\AppointmentServiceProvider::class,
    Modules\Appointment\Providers\EventServiceProvider::class,
    Modules\Scheduling\Providers\SchedulingServiceProvider::class,
    Modules\ClinicalRecord\Providers\ClinicalRecordServiceProvider::class,
    Modules\Billing\Providers\BillingServiceProvider::class,
    Modules\RIS\Providers\RisServiceProvider::class,
    Modules\RIS\Providers\EventServiceProvider::class,
    Modules\Gynecology\Providers\GynecologyServiceProvider::class,
    Modules\Pediatrics\Providers\PediatricsServiceProvider::class,
    Modules\Burns\Providers\BurnsServiceProvider::class,
    Modules\Rehab\Providers\RehabServiceProvider::class,
    Modules\InternalMedicine\Providers\InternalMedicineServiceProvider::class,
];
