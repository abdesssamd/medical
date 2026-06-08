<?php

namespace Modules\RIS\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Modules\RIS\Services\OrthancService;
use Modules\RIS\Services\RisReportService;

class RisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrthancService::class);
        $this->app->singleton(RisReportService::class);
    }

    public function boot(): void
    {
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            Route::middleware('web')->group(__DIR__.'/../Routes/web.php');
        }

        if (is_dir(__DIR__.'/../Resources/views')) {
            $this->loadViewsFrom(__DIR__.'/../Resources/views', 'ris');
        }

        if (is_dir(__DIR__.'/../Database/Migrations')) {
            $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        }

        $this->autoInstallMigrationsIfEnabled();
    }

    /**
     * Installe automatiquement les migrations RIS quand le module est active via l'interface admin.
     */
    private function autoInstallMigrationsIfEnabled(): void
    {
        // Eviter les boucles en console (artisan migrate charge deja les migrations).
        if ($this->app->runningInConsole()) {
            return;
        }

        if (! $this->isRisEnabled()) {
            return;
        }

        try {
            if (Schema::hasTable('ris_orders')) {
                return;
            }

            Artisan::call('migrate', [
                '--path' => 'Modules/RIS/Database/Migrations',
                '--force' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('ris.auto_migration_failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function isRisEnabled(): bool
    {
        if ((bool) config('ris.enabled', false)) {
            return true;
        }

        $settingModel = 'Modules\\Queue\\Models\\AppSetting';
        if (! class_exists($settingModel) || ! method_exists($settingModel, 'getValue')) {
            return false;
        }

        $value = $settingModel::getValue('module.ris.enabled', false);

        return filter_var($value, FILTER_VALIDATE_BOOL) === true;
    }
}
