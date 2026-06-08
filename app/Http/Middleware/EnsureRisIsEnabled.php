<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRisIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isRisEnabled()) {
            abort(404, 'Le module RIS est desactive.');
        }

        return $next($request);
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
