<?php

namespace Modules\Pediatrics\Providers;

use Illuminate\Support\ServiceProvider;

class PediatricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'pediatrics');
    }
}
