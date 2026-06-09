<?php

namespace App\Providers;

use App\Database\Grammars\LegacyMariaDbGrammar;
use App\Models\Patient;
use App\Observers\AppointmentObserver;
use App\Observers\PatientConsultationObserver;
use App\Observers\PatientObserver;
use App\Observers\PaymentObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Modules\Appointment\Models\Appointment;
use Modules\ClinicalRecord\Models\PatientConsultation;
use Modules\Billing\Models\Payment;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Audit Service
        $this->app->singleton(\App\Services\AuditService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        try {
            $conn = DB::connection();
            if ($conn->getSchemaGrammar() instanceof \Illuminate\Database\Schema\Grammars\MariaDbGrammar) {
                $conn->setSchemaGrammar(new LegacyMariaDbGrammar($conn));
            }
        } catch (\Throwable) {
            // Database not yet configured
        }

        // Register model observers for audit trail
        Patient::observe(PatientObserver::class);
        Appointment::observe(AppointmentObserver::class);
        PatientConsultation::observe(PatientConsultationObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}
