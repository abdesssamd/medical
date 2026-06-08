<?php

use App\Http\Middleware\EnsureRisIsEnabled;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;
use Modules\PatientPortal\Http\Controllers\PatientPortalAdminController;
use Modules\PatientPortal\Http\Controllers\PatientPortalAuthController;
use Modules\PatientPortal\Http\Controllers\PatientPortalDashboardController;
use Modules\PatientPortal\Http\Controllers\PatientPortalResultController;
use Modules\PatientPortal\Middleware\EnsurePatientPortalAuthenticated;

Route::middleware(['web', EnsureRisIsEnabled::class])
    ->prefix('portail-patient')
    ->name('patient-portal.')
    ->group(function (): void {
        Route::get('/', [PatientPortalAuthController::class, 'showLogin'])->name('login');
        Route::get('/acces/{token?}', [PatientPortalAuthController::class, 'showLogin'])->name('entry');
        Route::post('/connexion', [PatientPortalAuthController::class, 'authenticate'])->name('authenticate');
        Route::post('/deconnexion', [PatientPortalAuthController::class, 'logout'])->name('logout');

        Route::middleware(EnsurePatientPortalAuthenticated::class)->group(function (): void {
            Route::get('/tableau-de-bord', [PatientPortalDashboardController::class, 'index'])->name('dashboard');
            Route::get('/resultats', [PatientPortalResultController::class, 'show'])->name('results.show');
            Route::get('/resultats/pdf', [PatientPortalResultController::class, 'downloadPdf'])->name('results.pdf');
            Route::get('/resultats/images', [PatientPortalResultController::class, 'viewer'])->name('results.viewer');
        });
    });

Route::middleware(['web', 'auth', EnsureRisIsEnabled::class, EnsureRole::class.':super_admin,admin,professional,doctor,medecin,secretary,secretaire'])
    ->prefix('portail-patient/admin')
    ->name('patient-portal.admin.')
    ->group(function (): void {
        Route::get('/', [PatientPortalAdminController::class, 'index'])->name('index');
        Route::get('/{access}', [PatientPortalAdminController::class, 'show'])->name('show');
        Route::get('/{access}/memo', [PatientPortalAdminController::class, 'memo'])->name('memo');
    });
