<?php

namespace Modules\Queue\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Queue\Contracts\QueueManagerInterface;
use Modules\Queue\Services\QueueService;

class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/queue.php', 'queue');

        $this->app->bind(QueueManagerInterface::class, QueueService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'queue');

        app('view')->addLocation(__DIR__.'/../Resources/views');

        $this->publishes([
            __DIR__.'/../Config/queue.php' => config_path('queue.php'),
        ], 'queue-config');
    }
}
