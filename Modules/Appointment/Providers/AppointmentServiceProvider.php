<?php

namespace Modules\Appointment\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Appointment\Contracts\QueueBridgeInterface;
use Modules\Appointment\Services\NullQueueBridgeService;
use Modules\Appointment\Services\QueueModuleBridgeService;

class AppointmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/appointment.php', 'appointment');

        $this->app->bind(QueueBridgeInterface::class, function ($app) {
            $queueManagerContract = 'Modules\\Queue\\Contracts\\QueueManagerInterface';

            if (interface_exists($queueManagerContract) && $app->bound($queueManagerContract)) {
                return $app->make(QueueModuleBridgeService::class);
            }

            return $app->make(NullQueueBridgeService::class);
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/secretary.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'appointment');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'appointment');

        $this->publishes([
            __DIR__.'/../Config/appointment.php' => config_path('appointment.php'),
        ], 'appointment-config');
    }
}
