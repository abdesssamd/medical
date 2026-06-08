<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

abstract class AuditableObserver
{
    /**
     * Resource type for audit logging.
     */
    protected ?string $resourceType = null;

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        AuditLog::logCreated($model, $this->resourceType);
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        // Only log if there are actual changes
        if (! empty($model->getChanges())) {
            AuditLog::logUpdated($model, $this->resourceType);
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        AuditLog::logDeleted($model, $this->resourceType);
    }

    /**
     * Handle the model "retrieved" event (optional, can be heavy on DB).
     * Only enable for highly sensitive models.
     */
    public function retrieved(Model $model): void
    {
        // Uncomment to enable view tracking (may impact performance)
        // AuditLog::logViewed($model, $this->resourceType);
    }
}
