<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Modules\ClinicalRecord\Events\PatientConsultationCreated;
use Modules\ClinicalRecord\Models\PatientConsultation;

class PatientConsultationObserver extends AuditableObserver
{
    protected ?string $resourceType = 'patient_consultation';

    public function created(Model $model): void
    {
        parent::created($model);

        if ($model instanceof PatientConsultation) {
            event(new PatientConsultationCreated($model->fresh()));
        }
    }

    public function updated(Model $model): void
    {
        parent::updated($model);

        if ($model instanceof PatientConsultation) {
            event(new PatientConsultationCreated($model->fresh()));
        }
    }
}