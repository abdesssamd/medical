<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class PaymentObserver extends AuditableObserver
{
    protected ?string $resourceType = 'payment';

    public function created(Model $model): void
    {
        parent::created($model);
    }

    public function updated(Model $model): void
    {
        parent::updated($model);
    }

    public function deleted(Model $model): void
    {
        parent::deleted($model);
    }
}