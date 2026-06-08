<?php

namespace Modules\Gynecology\Providers;

use Illuminate\Support\ServiceProvider;

class GynecologyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'gynecology');
    }
}
