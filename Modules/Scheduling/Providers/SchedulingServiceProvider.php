<?php

namespace Modules\Scheduling\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SchedulingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module services
        $this->app->singleton(\Modules\Scheduling\Services\AvailabilityService::class);
        $this->app->singleton(\Modules\Scheduling\Services\MultiSpecialtyCoordinationService::class);
        $this->app->singleton(\Modules\Scheduling\Services\BookingService::class);
    }

    public function boot(): void
    {
        // Load web routes
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            Route::middleware('web')
                ->group(__DIR__.'/../Routes/web.php');
        }

        // Load views
        if (is_dir(__DIR__.'/../Resources/views')) {
            $this->loadViewsFrom(__DIR__.'/../Resources/views', 'scheduling');
        }

        // Load translations
        if (is_dir(__DIR__.'/../Resources/lang')) {
            $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'scheduling');
        }
    }
}
