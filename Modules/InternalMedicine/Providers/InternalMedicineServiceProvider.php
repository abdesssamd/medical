<?php

namespace Modules\InternalMedicine\Providers;

use Illuminate\Support\ServiceProvider;

class InternalMedicineServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'internal-medicine');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
