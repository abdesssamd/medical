<?php

use App\Http\Controllers\Api\CoreAdminController;
use App\Http\Controllers\Api\OrthancWebhookController;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;
use Modules\Appointment\Http\Controllers\Api\PatientFlowController;
use Modules\ClinicalRecord\Http\Controllers\Api\ClinicalWorkflowApiController;
use Modules\RIS\Http\Controllers\RisWebhookController;
use Modules\Scheduling\Http\Controllers\Api\ApiSchedulingController;

// Main app API routes can be declared here.

Route::post('/orthanc/events', [OrthancWebhookController::class, 'handleStoredInstance'])
    ->name('api.orthanc.events');

Route::post('/ris/orthanc-webhook', [RisWebhookController::class, 'orthancStoredInstance'])
    ->name('api.ris.orthanc-webhook');

Route::middleware(['api', 'auth', EnsureRole::class.':super_admin,admin'])
    ->prefix('core-admin')
    ->group(function (): void {
        Route::get('/kpi', [CoreAdminController::class, 'dashboardKpi'])
            ->middleware(EnsurePermission::class.':kpi.view');
        Route::put('/users/{user}/roles', [CoreAdminController::class, 'assignRoles'])
            ->middleware(EnsurePermission::class.':settings.manage');
        Route::put('/users/{user}/permissions', [CoreAdminController::class, 'assignPermissions'])
            ->middleware(EnsurePermission::class.':settings.manage');
        Route::put('/users/{user}/accounting-profile', [CoreAdminController::class, 'upsertAccountingProfile'])
            ->middleware(EnsurePermission::class.':billing.manage');
    });

Route::middleware(['api', 'auth', EnsureRole::class.':super_admin,admin,professional,secretary'])
    ->prefix('patient-flow')
    ->group(function (): void {
        Route::get('/board', [PatientFlowController::class, 'board']);
        Route::post('/appointments/{appointment}/check-in', [PatientFlowController::class, 'checkIn']);
        Route::patch('/appointments/{appointment}/transition', [PatientFlowController::class, 'transition']);
    });

Route::middleware(['api', 'auth', EnsureRole::class.':super_admin,admin,professional,assistant'])
    ->prefix('clinical-workflow')
    ->group(function (): void {
        Route::get('/patients/{patientId}/odontogram', [ClinicalWorkflowApiController::class, 'odontogram']);
        Route::get('/patients/{patientId}/timeline', [ClinicalWorkflowApiController::class, 'timeline']);
        Route::post('/patients/{patientId}/imaging-studies', [ClinicalWorkflowApiController::class, 'storeImagingStudy']);
        Route::get('/patients/{patientId}/imaging-manifest', [ClinicalWorkflowApiController::class, 'imagingManifest']);
    });

Route::middleware(['api', 'auth', EnsureRole::class.':super_admin,admin,professional,secretary'])
    ->prefix('scheduling')
    ->group(function (): void {
        Route::get('/availability', [ApiSchedulingController::class, 'availability']);
        Route::get('/availability/range', [ApiSchedulingController::class, 'availabilityRange']);
        Route::post('/appointments', [ApiSchedulingController::class, 'storeAppointment']);
        Route::patch('/appointments/{appointment}/cancel', [ApiSchedulingController::class, 'cancelAppointment']);
        Route::patch('/appointments/{appointment}/no-show', [ApiSchedulingController::class, 'markNoShow']);
        Route::post('/coordination/find-optimal-day', [ApiSchedulingController::class, 'findOptimalDay']);
        Route::post('/coordination/book-grouped', [ApiSchedulingController::class, 'bookGrouped']);
        Route::get('/appointment-types', [ApiSchedulingController::class, 'getAppointmentTypes']);
        Route::post('/appointment-types', [ApiSchedulingController::class, 'storeAppointmentType']);
        Route::get('/rooms/available', [ApiSchedulingController::class, 'availableRooms']);
    });

