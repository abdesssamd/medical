<?php

namespace Modules\Burns\Providers;

use Illuminate\Support\ServiceProvider;

class BurnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'burns');
    }
}
