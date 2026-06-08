<?php

namespace App\Observers;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;

class PatientObserver extends AuditableObserver
{
    /**
     * Resource type for audit logging.
     */
    protected ?string $resourceType = 'patient';

    /**
     * Handle the Patient "created" event.
     */
    public function created(Model $model): void
    {
        parent::created($model);
    }

    /**
     * Handle the Patient "updated" event.
     */
    public function updated(Model $model): void
    {
        parent::updated($model);
    }

    /**
     * Handle the Patient "deleted" event.
     */
    public function deleted(Model $model): void
    {
        parent::deleted($model);
    }
}
