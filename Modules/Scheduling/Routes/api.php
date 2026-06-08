<?php

use Illuminate\Support\Facades\Route;
use Modules\Scheduling\Http\Controllers\Api\ApiSchedulingController;

Route::middleware(['api'])->prefix('api/scheduling')->name('api.scheduling.')->group(function () {
    // Availability
    Route::get('/availability', [ApiSchedulingController::class, 'availability'])->name('availability');
    Route::get('/availability/range', [ApiSchedulingController::class, 'availabilityRange'])->name('availability.range');
    
    // Appointments
    Route::post('/appointments', [ApiSchedulingController::class, 'storeAppointment'])->name('appointments.store');
    Route::patch('/appointments/{appointment}/cancel', [ApiSchedulingController::class, 'cancelAppointment'])->name('appointments.cancel');
    Route::patch('/appointments/{appointment}/no-show', [ApiSchedulingController::class, 'markNoShow'])->name('appointments.no-show');
    
    // Coordination
    Route::post('/coordination/find-optimal-day', [ApiSchedulingController::class, 'findOptimalDay'])->name('coordination.find-optimal-day');
    Route::post('/coordination/book-grouped', [ApiSchedulingController::class, 'bookGrouped'])->name('coordination.book-grouped');
    
    // Appointment Types
    Route::get('/appointment-types', [ApiSchedulingController::class, 'getAppointmentTypes'])->name('appointment-types.index');
    Route::post('/appointment-types', [ApiSchedulingController::class, 'storeAppointmentType'])->name('appointment-types.store');
    
    // Rooms
    Route::get('/rooms/available', [ApiSchedulingController::class, 'availableRooms'])->name('rooms.available');
});
