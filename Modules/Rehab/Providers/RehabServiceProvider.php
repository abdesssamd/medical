<?php

namespace Modules\Rehab\Providers;

use Illuminate\Support\ServiceProvider;

class RehabServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'rehab');
    }
}
