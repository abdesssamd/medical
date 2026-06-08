<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Modules\Appointment\Events\PatientFlowUpdated;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Services\PatientFlowService;

class AppointmentObserver extends AuditableObserver
{
    /**
     * Resource type for audit logging.
     */
    protected ?string $resourceType = 'appointment';

    /**
     * Handle the Appointment "created" event.
     */
    public function created(Model $model): void
    {
        parent::created($model);

        if ($model instanceof Appointment) {
            app(PatientFlowService::class)->syncJourneyFromAppointment($model);
            event(new PatientFlowUpdated($model->fresh(), 'appointment.created'));
        }
    }

    /**
     * Handle the Appointment "updated" event.
     */
    public function updated(Model $model): void
    {
        parent::updated($model);

        if ($model instanceof Appointment) {
            event(new PatientFlowUpdated($model->fresh(), 'appointment.updated'));
        }
    }

    /**
     * Handle the Appointment "deleted" event.
     */
    public function deleted(Model $model): void
    {
        parent::deleted($model);
    }
}
