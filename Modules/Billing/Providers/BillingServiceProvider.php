<?php

namespace Modules\Billing\Providers;

use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module services
        $this->app->singleton(\Modules\Billing\Services\BillingService::class);
    }

    public function boot(): void
    {
        // Load routes
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        }

        if (file_exists(__DIR__.'/../Routes/api.php')) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Load views
        if (is_dir(__DIR__.'/../Resources/views')) {
            $this->loadViewsFrom(__DIR__.'/../Resources/views', 'billing');
        }

        // Load translations
        if (is_dir(__DIR__.'/../Resources/lang')) {
            $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'billing');
        }

        // Publish config
        $this->publishes([
            __DIR__.'/../Config/billing.php' => config_path('billing.php'),
        ], 'billing-config');
    }
}
