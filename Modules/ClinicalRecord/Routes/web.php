<?php

use Illuminate\Support\Facades\Route;
use Modules\ClinicalRecord\Http\Controllers\ClinicalRecordController;
use Modules\ClinicalRecord\Http\Controllers\QuestionnaireTemplateController;
use Modules\ClinicalRecord\Http\Controllers\QuestionnaireResponseController;
use Modules\ClinicalRecord\Http\Controllers\QuestionnaireStatsController;

Route::middleware(['web', 'auth'])->prefix('clinical')->name('clinical.')->group(function () {
    // Patients list
    Route::get('/patients', [ClinicalRecordController::class, 'index'])->name('patients');
    
    // Patient clinical record
    Route::get('/patients/{patientId}', [ClinicalRecordController::class, 'show'])->name('patient.show');
    Route::get('/patients/{patientId}/chart', [ClinicalRecordController::class, 'dentalChart'])->name('patient.chart');
    Route::post('/patients/{patientId}/teeth/{toothNumber}/status', [ClinicalRecordController::class, 'updateToothStatus'])->name('tooth.update');
    Route::post('/patients/{patientId}/procedures', [ClinicalRecordController::class, 'storeProcedure'])->name('procedures.store');
    
    // Treatment plans
    Route::get('/patients/{patientId}/treatment-plans', [ClinicalRecordController::class, 'treatmentPlans'])->name('treatment-plans');
    Route::post('/patients/{patientId}/treatment-plans', [ClinicalRecordController::class, 'storeTreatmentPlan'])->name('treatment-plans.store');
    Route::post('/treatment-plans/{planId}/procedures', [ClinicalRecordController::class, 'addProcedureToPlan'])->name('treatment-plans.add-procedure');
    
    // Medical images
    Route::get('/patients/{patientId}/images', [ClinicalRecordController::class, 'medicalImages'])->name('patient.images');

    // Questionnaire Templates Management (Super Admin only)
    Route::prefix('questionnaire-templates')->name('questionnaire-templates.')->group(function () {
        Route::get('/', [QuestionnaireTemplateController::class, 'index'])->name('index');
        Route::get('/create', [QuestionnaireTemplateController::class, 'create'])->name('create');
        Route::post('/', [QuestionnaireTemplateController::class, 'store'])->name('store');
        Route::get('/{id}', [QuestionnaireTemplateController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [QuestionnaireTemplateController::class, 'edit'])->name('edit');
        Route::put('/{id}', [QuestionnaireTemplateController::class, 'update'])->name('update');
        Route::delete('/{id}', [QuestionnaireTemplateController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/duplicate', [QuestionnaireTemplateController::class, 'duplicateRedirect'])->name('duplicate.redirect');
        Route::post('/{id}/duplicate', [QuestionnaireTemplateController::class, 'duplicate'])->name('duplicate');
        Route::post('/{id}/toggle-active', [QuestionnaireTemplateController::class, 'toggleActive'])->name('toggle-active');
        Route::get('/{id}/export', [QuestionnaireTemplateController::class, 'export'])->name('export');
        Route::post('/import', [QuestionnaireTemplateController::class, 'import'])->name('import');
    });

    // Questionnaire Responses (for practitioners and admins)
    Route::prefix('patients/{patientId}/questionnaires')->name('questionnaire-response.')->group(function () {
        Route::get('/available', [QuestionnaireResponseController::class, 'getAvailableQuestionnaires'])->name('available');
        Route::get('/{questionnaireId}', [QuestionnaireResponseController::class, 'show'])->name('show');
        Route::post('/{questionnaireId}', [QuestionnaireResponseController::class, 'store'])->name('store');
        Route::get('/responses', [QuestionnaireResponseController::class, 'history'])->name('history');
        Route::post('/{responseId}/validate', [QuestionnaireResponseController::class, 'validateResponse'])->name('validate');
        Route::delete('/{responseId}', [QuestionnaireResponseController::class, 'destroy'])->name('destroy');
    });

    // Questionnaire Statistics
    Route::prefix('questionnaires')->name('questionnaire-stats.')->group(function () {
        Route::get('/stats', [QuestionnaireStatsController::class, 'dashboard'])->name('dashboard');
        Route::get('/stats/list', [QuestionnaireStatsController::class, 'index'])->name('index');
        Route::get('/{questionnaireId}/stats/questions', [QuestionnaireStatsController::class, 'questions'])->name('questions');
        Route::get('/{questionnaireId}/stats/overview', [QuestionnaireStatsController::class, 'overview'])->name('overview');
        Route::get('/{questionnaireId}/stats/analyze/{questionKey}', [QuestionnaireStatsController::class, 'analyze'])->name('analyze');
    });
});
