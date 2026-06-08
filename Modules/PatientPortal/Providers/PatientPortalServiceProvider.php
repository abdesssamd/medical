<?php

namespace Modules\PatientPortal\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\PatientPortal\Services\PatientPortalAccessService;

class PatientPortalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PatientPortalAccessService::class);
    }

    public function boot(): void
    {
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            Route::middleware('web')->group(__DIR__.'/../Routes/web.php');
        }

        if (is_dir(__DIR__.'/../Resources/views')) {
            $this->loadViewsFrom(__DIR__.'/../Resources/views', 'patient_portal');
        }

        if (is_dir(__DIR__.'/../Database/Migrations')) {
            $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        }
    }
}
