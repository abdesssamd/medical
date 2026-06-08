<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;
use Modules\Appointment\Http\Controllers\Web\ProfessionalController;
use Modules\Appointment\Http\Controllers\Web\SecretaryController;
use Modules\Appointment\Http\Controllers\Web\SecretaryDashboardController;

Route::middleware(['web', 'auth', EnsureRole::class.':professional'])->prefix('appointment/pro')->name('appointment.pro.')->group(function (): void {
    Route::get('/dashboard', [ProfessionalController::class, 'dashboard'])->name('dashboard');
    Route::put('/planning/{day}', [ProfessionalController::class, 'updatePlanning'])->name('planning.update');
    Route::patch('/planning/{day}/toggle', [ProfessionalController::class, 'togglePlanning'])->name('planning.toggle');
    Route::post('/planning/duplicate-monday', [ProfessionalController::class, 'duplicateMondayToWeek'])->name('planning.duplicate-monday');
    Route::post('/planning/optimize-week', [ProfessionalController::class, 'optimizeWeek'])->name('planning.optimize-week');
    Route::get('/planning/no-show-list', [ProfessionalController::class, 'noShowList'])->name('planning.no-show-list');
    Route::post('/planning/capacity-settings', [ProfessionalController::class, 'updateCapacitySettings'])->name('planning.capacity-settings');
});

Route::middleware(['web', 'auth', EnsureRole::class.':secretary'])->prefix('appointment/sec')->name('appointment.sec.')->group(function (): void {
    Route::get('/dashboard', [SecretaryDashboardController::class, 'index'])->name('dashboard');
});
