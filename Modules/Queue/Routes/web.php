<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Controllers\Web\CareSuiteController;
use Illuminate\Support\Facades\Route;
use Modules\ClinicalRecord\Http\Controllers\MedicationController;
use Modules\ClinicalRecord\Http\Controllers\Icd10CodeController;
use Modules\Queue\Http\Controllers\AdminController;
use Modules\Queue\Http\Controllers\AgentController;
use Modules\Queue\Http\Controllers\AuthController;
use Modules\Queue\Http\Controllers\TicketController;

Route::middleware('web')->group(function (): void {
Route::get('/', [TicketController::class, 'create'])->name('home');

Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['fr', 'ar'], true)) {
        session(['locale' => $locale]);
    }

    return back();
})->name('lang.switch');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', EnsureRole::class.':super_admin,admin,professional,doctor,medecin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
    Route::get('/settings/questionnaires', [CareSuiteController::class, 'questionnaireSettings'])->name('settings.questionnaires');
    Route::post('/settings/questionnaires', [CareSuiteController::class, 'storeQuestionnaireTemplate'])->name('settings.questionnaires.store');
});

Route::middleware(['auth', EnsureRole::class.':super_admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/statistics', [AdminController::class, 'statistics'])->name('statistics');
    Route::get('/history', [AdminController::class, 'history'])->name('history');
    Route::get('/supervisor', [AdminController::class, 'supervisorDashboard'])->name('supervisor');
    Route::get('/supervisor/live', [AdminController::class, 'supervisorLive'])->name('supervisor.live');
    Route::get('/appointments', [AdminController::class, 'appointments'])->name('appointments');
    Route::post('/appointments', [AdminController::class, 'storeAppointment'])->name('appointments.store');
    Route::put('/appointments/{ticket}', [AdminController::class, 'updateAppointment'])->name('appointments.update');
    Route::delete('/appointments/{ticket}', [AdminController::class, 'destroyAppointment'])->name('appointments.destroy');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    Route::get('/medications', [MedicationController::class, 'index'])->name('medications.index');
    Route::post('/medications', [MedicationController::class, 'store'])->name('medications.store');
    Route::put('/medications/{medication}', [MedicationController::class, 'update'])->name('medications.update');
    Route::delete('/medications/{medication}', [MedicationController::class, 'destroy'])->name('medications.destroy');
    Route::get('/medications/export', [MedicationController::class, 'exportExcel'])->name('medications.export');
    Route::get('/medications/example', [MedicationController::class, 'exampleExcel'])->name('medications.example');
    Route::post('/medications/import', [MedicationController::class, 'importExcel'])->name('medications.import');
    Route::get('/icd10-codes', [Icd10CodeController::class, 'index'])->name('icd10-codes.index');
    Route::post('/icd10-codes', [Icd10CodeController::class, 'store'])->name('icd10-codes.store');
    Route::put('/icd10-codes/{icd10Code}', [Icd10CodeController::class, 'update'])->name('icd10-codes.update');
    Route::delete('/icd10-codes/{icd10Code}', [Icd10CodeController::class, 'destroy'])->name('icd10-codes.destroy');
    Route::get('/icd10-codes/export', [Icd10CodeController::class, 'exportExcel'])->name('icd10-codes.export');
    Route::get('/icd10-codes/example', [Icd10CodeController::class, 'exampleExcel'])->name('icd10-codes.example');
    Route::post('/icd10-codes/import', [Icd10CodeController::class, 'importExcel'])->name('icd10-codes.import');
    Route::get('/counters', [AdminController::class, 'counters'])->name('counters');
    Route::post('/counters', [AdminController::class, 'storeCounter'])->name('counters.store');
    Route::put('/counters/{counter}', [AdminController::class, 'updateCounter'])->name('counters.update');
    Route::delete('/counters/{counter}', [AdminController::class, 'destroyCounter'])->name('counters.destroy');
    Route::get('/kiosks', [AdminController::class, 'kiosks'])->name('kiosks');
    Route::post('/kiosks', [AdminController::class, 'storeKiosk'])->name('kiosks.store');
    Route::put('/kiosks/{kiosk}', [AdminController::class, 'updateKiosk'])->name('kiosks.update');
    Route::delete('/kiosks/{kiosk}', [AdminController::class, 'destroyKiosk'])->name('kiosks.destroy');
    Route::get('/screens', [AdminController::class, 'screens'])->name('screens');
    Route::post('/screens', [AdminController::class, 'storeScreen'])->name('screens.store');
    Route::put('/screens/{screen}', [AdminController::class, 'updateScreen'])->name('screens.update');
    Route::delete('/screens/{screen}', [AdminController::class, 'destroyScreen'])->name('screens.destroy');
    Route::get('/playlists', [AdminController::class, 'playlists'])->name('playlists');
    Route::post('/playlists', [AdminController::class, 'storePlaylist'])->name('playlists.store');
    Route::put('/playlists/{item}', [AdminController::class, 'updatePlaylist'])->name('playlists.update');
    Route::delete('/playlists/{item}', [AdminController::class, 'destroyPlaylist'])->name('playlists.destroy');
    Route::get('/report/export', [AdminController::class, 'exportReport'])->name('report.export');
});

Route::middleware(['auth', EnsureRole::class.':agent'])->group(function (): void {
    Route::get('/agent/dashboard', [AgentController::class, 'dashboard'])->name('agent.dashboard');
    Route::get('/agent/queue-status', [AgentController::class, 'queueStatus'])->name('agent.queue-status');
    Route::post('/agent/call-next', [AgentController::class, 'callNext'])->name('agent.call.next');
    Route::post('/agent/tickets/{ticket}/recall', [AgentController::class, 'recall'])->name('agent.ticket.recall');
    Route::post('/agent/tickets/{ticket}/transfer', [AgentController::class, 'transfer'])->name('agent.ticket.transfer');
    Route::post('/agent/tickets/{ticket}/served', [AgentController::class, 'markServed'])->name('agent.ticket.served');
    Route::post('/agent/tickets/{ticket}/absent', [AgentController::class, 'markAbsent'])->name('agent.ticket.absent');
});

Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
Route::get('/tickets/{ticket}/print', [TicketController::class, 'print'])->name('tickets.print');
Route::get('/tickets/track/{publicCode}', [TicketController::class, 'track'])->name('tickets.track');

Route::get('/display/open', [TicketController::class, 'openDisplay'])->name('display.open');
Route::get('/display/code/{code}', [TicketController::class, 'publicDisplayByCode'])->name('display.public.code');
Route::get('/display/{organization}', [TicketController::class, 'publicDisplay'])->name('display.public');
});
