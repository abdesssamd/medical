<?php

use Illuminate\Support\Facades\Route;
use Modules\Scheduling\Http\Controllers\SchedulingController;

Route::middleware(['web', 'auth'])->prefix('scheduling')->name('scheduling.')->group(function () {
    Route::get('/dashboard', [SchedulingController::class, 'dashboard'])->name('dashboard');
    Route::get('/appointment-types', [SchedulingController::class, 'appointmentTypes'])->name('appointment-types');
    Route::post('/appointment-types', [SchedulingController::class, 'storeAppointmentType'])->name('appointment-types.store');
    Route::get('/availability-blocks', [SchedulingController::class, 'availabilityBlocks'])->name('availability-blocks');
    Route::post('/availability-blocks/recurring', [SchedulingController::class, 'storeRecurringBlock'])->name('availability-blocks.store-recurring');
    Route::get('/multi-specialty', [SchedulingController::class, 'multiSpecialtyCoordination'])->name('multi-specialty');
    Route::post('/multi-specialty/find-optimal', [SchedulingController::class, 'findOptimalDay'])->name('multi-specialty.find-optimal');
});
