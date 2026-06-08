<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureRole;
use Modules\Appointment\Http\Controllers\Web\SecretaryDashboardController;
use Modules\Billing\Http\Controllers\CashSessionController;
use Modules\Queue\Http\Controllers\QueueManagementController;

// Module Secrétaire - Dashboard action-oriented
Route::middleware(['web', 'auth', EnsureRole::class . ':secretary'])->prefix('secretary')->name('secretary.')->group(function () {
    // Dashboard principal
    Route::get('/dashboard', [SecretaryDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [SecretaryDashboardController::class, 'getData'])->name('dashboard.data');
    Route::get('/patients/search', [SecretaryDashboardController::class, 'searchPatients'])->name('patients.search');
    Route::post('/appointments/quick-create', [SecretaryDashboardController::class, 'quickCreateAppointment'])->name('appointments.quick-create');
    Route::patch('/appointments/{appointment}/schedule', [SecretaryDashboardController::class, 'updateSchedule'])->name('appointments.schedule');
    Route::patch('/appointments/{appointment}/flow-action', [SecretaryDashboardController::class, 'updateFlowAction'])->name('appointments.flow-action');

    // Notes contextuelles
    Route::post('/appointments/{appointment}/notes', [SecretaryDashboardController::class, 'createNote'])->name('notes.create');
    Route::patch('/notes/{note}/read', [SecretaryDashboardController::class, 'markNoteAsRead'])->name('notes.read');
    Route::get('/notes/unread', [SecretaryDashboardController::class, 'getUnreadNotes'])->name('notes.unread');

    // Caisse
    Route::prefix('cash')->name('cash.')->group(function () {
        Route::get('/', [CashSessionController::class, 'index'])->name('index');
        Route::post('/open', [CashSessionController::class, 'open'])->name('open');
        Route::get('/session/{cashSession}', [CashSessionController::class, 'show'])->name('show');
        Route::post('/session/{cashSession}/transaction', [CashSessionController::class, 'recordTransaction'])->name('transaction.record');
        Route::post('/session/{cashSession}/close', [CashSessionController::class, 'close'])->name('close');
        Route::get('/session/{cashSession}/export', [CashSessionController::class, 'export'])->name('export');
    });

    // Queue Management
    Route::prefix('queue')->name('queue.')->group(function () {
        Route::get('/ordered', [QueueManagementController::class, 'getOrderedQueue'])->name('ordered');
        Route::post('/appointments/{appointment}/reorder', [QueueManagementController::class, 'reorder'])->name('reorder');
        Route::post('/appointments/{appointment}/priority', [QueueManagementController::class, 'setPriority'])->name('priority');
        Route::get('/escalated', [QueueManagementController::class, 'getEscalated'])->name('escalated');
    });
});
