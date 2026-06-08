<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;
use Modules\Appointment\Http\Controllers\Api\AppointmentController;
use Modules\Appointment\Http\Controllers\Api\CommissionController;
use Modules\Appointment\Http\Controllers\Api\DashboardController;
use Modules\Appointment\Http\Controllers\Api\PlanningController;

Route::middleware(['api', 'auth', EnsureRole::class.':professional,secretary'])->prefix('api/appointment')->group(function (): void {
    Route::get('/availability', [AppointmentController::class, 'availability']);
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);

    Route::get('/professionals/{professional}/plannings', [PlanningController::class, 'index']);
    Route::put('/professionals/{professional}/plannings', [PlanningController::class, 'upsert']);
    Route::put('/professionals/{professional}/settings', [PlanningController::class, 'updateSettings']);

    Route::get('/dashboard/secretary', [DashboardController::class, 'secretary']);
});

Route::middleware(['api', 'auth', EnsureRole::class.':professional'])->prefix('api/appointment')->group(function (): void {
    Route::get('/commissions/summary', [CommissionController::class, 'summary']);
    Route::patch('/commissions/{commission}/mark-paid', [CommissionController::class, 'markPaid']);
});
