<?php

use App\Http\Middleware\EnsureRisIsEnabled;
use Illuminate\Support\Facades\Route;
use Modules\RIS\Http\Controllers\RisAiController;
use Modules\RIS\Http\Controllers\RisExamController;
use Modules\RIS\Http\Controllers\RisReportTemplateController;
use Modules\RIS\Http\Controllers\RisWebhookController;

Route::middleware(['web', 'auth', EnsureRisIsEnabled::class])
    ->prefix('ris')
    ->name('ris.')
    ->group(function (): void {
        Route::get('/examens', [RisExamController::class, 'index'])->name('exams.index');
        Route::post('/examens', [RisExamController::class, 'store'])->name('exams.store');
        Route::get('/patients/search', [RisExamController::class, 'searchPatients'])->name('patients.search');
        Route::get('/spotlight', [RisExamController::class, 'spotlight'])->name('spotlight');
        Route::get('/examens/live', [RisExamController::class, 'liveSnapshot'])->name('exams.live');
        Route::post('/patients/select', [RisExamController::class, 'selectPatient'])->name('patients.select');
        Route::post('/patients/clear', [RisExamController::class, 'clearPatient'])->name('patients.clear');
        Route::post('/patients/create', [RisExamController::class, 'createPatientJson'])->name('patients.create');
        Route::get('/examens/{order}', [RisExamController::class, 'show'])->name('exams.show');
        Route::get('/examens/{order}/previous-report', [RisExamController::class, 'previousReport'])->name('exams.previous-report');
        Route::patch('/examens/{order}/attente', [RisExamController::class, 'markAsWaiting'])->name('exams.waiting');
        Route::patch('/examens/{order}/images-recues', [RisExamController::class, 'markAsImagesReceived'])->name('exams.images-received');
        Route::patch('/examens/{order}/priorite', [RisExamController::class, 'updatePriority'])->name('exams.priority');
        Route::patch('/examens/{order}/terminer', [RisExamController::class, 'markAsCompleted'])->name('exams.complete');
        Route::post('/examens/{order}/signer', [RisExamController::class, 'signReport'])->name('exams.sign-report');
        Route::post('/examens/{order}/send-report', [RisExamController::class, 'sendReportCopy'])->name('exams.send-report');
        Route::get('/examens/{order}/report/pdf', [RisExamController::class, 'reportPdf'])->name('exams.report.pdf');
        Route::patch('/examens/{order}/annuler', [RisExamController::class, 'cancel'])->name('exams.cancel');
        Route::put('/examens/{order}/report', [RisExamController::class, 'saveReport'])->name('exams.report');
        Route::post('/examens/{order}/ai/analyze', [RisAiController::class, 'analyze'])->name('exams.ai.analyze');
        Route::post('/examens/{order}/worklist', [RisExamController::class, 'syncWorklist'])->name('exams.worklist');
        Route::post('/examens/synchroniser-pacs', [RisExamController::class, 'syncSelectedPatientWithOrthanc'])->name('exams.sync-pacs');

        Route::get('/templates', [RisReportTemplateController::class, 'index'])->name('templates.index');
        Route::post('/templates', [RisReportTemplateController::class, 'store'])->name('templates.store');
        Route::get('/templates/{template}/edit', [RisReportTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('/templates/{template}', [RisReportTemplateController::class, 'update'])->name('templates.update');
        Route::delete('/templates/{template}', [RisReportTemplateController::class, 'destroy'])->name('templates.destroy');
    });

Route::middleware(['web', EnsureRisIsEnabled::class])
    ->prefix('ris')
    ->name('ris.')
    ->group(function (): void {
        Route::post('/orthanc/webhook', [RisWebhookController::class, 'orthancStoredInstance'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('orthanc.webhook');

        Route::get('/reports/share/{token}', [RisExamController::class, 'sharedReport'])
            ->name('reports.shared');
    });
