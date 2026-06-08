<?php

use App\Http\Controllers\Web\CareSuiteController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasAnyRole(['agent'])) {
            return redirect()->route('agent.dashboard');
        }
        if ($user->hasAnyRole(['professional', 'doctor', 'medecin'])) {
            return redirect()->route('appointment.pro.dashboard');
        }
        if ($user->hasAnyRole(['secretary', 'secretaire'])) {
            $professionalId = \App\Models\User::query()
                ->whereIn('role', ['professional', 'doctor'])
                ->value('id') ?? auth()->id();
            return redirect()->route('appointment.sec.dashboard', ['professional_id' => $professionalId]);
        }

        return redirect()->route('tickets.create');
    }

    return redirect()->route('login');
})->name('home');

Route::get('/tabler/dashboard', function () {
    $stats = [
        'patients_today' => 42,
        'commissions_total' => 12450.00,
        'rdv_pending' => 8,
        'rdv_done' => 34,
    ];

    $rows = [
        ['patient' => 'Amina El Idrissi', 'slot' => '09:00', 'status' => 'Terminé'],
        ['patient' => 'Youssef B.', 'slot' => '09:20', 'status' => 'En attente'],
        ['patient' => 'Kenza M.', 'slot' => '09:40', 'status' => 'Annulé'],
        ['patient' => 'Rachid T.', 'slot' => '10:00', 'status' => 'Terminé'],
    ];

    return view('dashboard', compact('stats', 'rows'));
})->name('tabler.dashboard');

Route::middleware(['web', 'auth', EnsureRole::class.':super_admin,admin'])->prefix('care/module-1')->name('care.module1.')->group(function (): void {
    Route::get('/', [CareSuiteController::class, 'module1'])->name('index');
    Route::post('/users/{user}/roles', [CareSuiteController::class, 'assignRoles'])->name('roles');
    Route::post('/users/{user}/permissions', [CareSuiteController::class, 'assignPermissions'])->name('permissions');
    Route::post('/users/{user}/accounting-profile', [CareSuiteController::class, 'accountingProfile'])->name('accounting');
});

Route::middleware(['web', 'auth', EnsureRole::class.':super_admin,admin,professional,secretary'])->prefix('care/module-2')->name('care.module2.')->group(function (): void {
    Route::get('/', [CareSuiteController::class, 'module2'])->name('index');
    Route::get('/board-data', [CareSuiteController::class, 'boardData'])->name('board-data');
    Route::get('/patients/search', [CareSuiteController::class, 'searchPatients'])->name('patients.search');
    Route::post('/appointments', [CareSuiteController::class, 'storeAppointment'])->name('appointments.store');
    Route::post('/availability-blocks', [CareSuiteController::class, 'storeAvailabilityBlock'])->name('availability-blocks.store');
    Route::post('/appointments/{appointment}/check-in', [CareSuiteController::class, 'checkIn'])->name('checkin');
    Route::post('/appointments/{appointment}/transition', [CareSuiteController::class, 'transitionFlow'])->name('transition');
    Route::post('/appointments/{appointment}/call-room', [CareSuiteController::class, 'callToRoom'])->name('call-room');
    Route::post('/appointments/{appointment}/reschedule', [CareSuiteController::class, 'rescheduleAppointment'])->name('reschedule');
    Route::post('/appointments/{appointment}/notify-delay', [CareSuiteController::class, 'notifyDelay'])->name('notify-delay');
    Route::post('/appointments/{appointment}/close', [CareSuiteController::class, 'closeJourney'])->name('close');
    Route::post('/grouped-suggestion', [CareSuiteController::class, 'groupedSuggestion'])->name('grouped-suggestion');
    Route::post('/grouped-auto-suggestion', [CareSuiteController::class, 'groupedAutoSuggestion'])->name('grouped-auto-suggestion');
    Route::post('/grouped-book', [CareSuiteController::class, 'groupedBook'])->name('grouped-book');
});

Route::middleware(['web', 'auth', EnsureRole::class.':super_admin,admin,professional,assistant'])->prefix('care/module-3')->name('care.module3.')->group(function (): void {
    Route::get('/', [CareSuiteController::class, 'module3'])->name('index');
    Route::get('/patients', static function (\Illuminate\Http\Request $request) {
        $query = $request->only(['patient_id', 'tab']);

        return redirect()->route('care.module3.index', $query);
    })->name('patients.index');
    Route::get('/patients/{patientId}/export', [CareSuiteController::class, 'exportPatientReport'])->name('export');
    Route::get('/patients/{patientId}', [CareSuiteController::class, 'showPatient'])->name('patients.show');
    Route::post('/patients', [CareSuiteController::class, 'createPatient'])->name('patients.store');
    Route::put('/patients/{patientId}', [CareSuiteController::class, 'updatePatient'])->name('patients.update');
    Route::post('/patients/{patientId}/consultations', [CareSuiteController::class, 'storeConsultation'])->name('consultations.store');
    Route::post('/patients/{patientId}/history-items', [CareSuiteController::class, 'addPatientHistoryItem'])->name('patients.history-items.store');
    Route::match(['post', 'delete'], '/patients/{patientId}/history', [CareSuiteController::class, 'managePatientHistory'])->name('patients.history.manage');
    Route::post('/patients/{patientId}/procedures', [CareSuiteController::class, 'storeClinicalProcedure'])->name('procedures.store');
    // Return patient's procedures as JSON for AJAX refresh
    Route::get('/patients/{patientId}/procedures', [CareSuiteController::class, 'patientProcedures'])->name('procedures.list');
    Route::get('/patients/{patientId}/procedure-form-data', [CareSuiteController::class, 'getProcedureFormData'])->name('procedures.form-data');
    Route::post('/patients/{patientId}/imaging', [CareSuiteController::class, 'storeImaging'])->name('imaging.store');
    Route::post('/patients/{patientId}/radiology-requests', [CareSuiteController::class, 'storeRadiologyRequest'])->name('radiology-requests.store');
    Route::post('/patients/{patientId}/treatment-plan', [CareSuiteController::class, 'storeTreatmentPlan'])->name('treatment-plan.store');
    Route::post('/treatment-plan/{treatmentPlan}/request-signature', [CareSuiteController::class, 'requestTreatmentPlanSignature'])->name('plan.request-signature');
    Route::post('/treatment-plan/{plan}/quote', [CareSuiteController::class, 'createTreatmentQuote'])->name('quote.create');
    Route::get('/treatment-quote/{quote}/pdf', [CareSuiteController::class, 'treatmentQuotePdf'])->name('quote.pdf');
    Route::post('/treatment-quote/{quote}/sign', [CareSuiteController::class, 'signTreatmentQuote'])->name('quote.sign');
    Route::post('/patients/{patientId}/periodontal-chart', [CareSuiteController::class, 'storePeriodontalChart'])->name('periodontal.store');
    Route::post('/patients/{patientId}/ortho-photo-set', [CareSuiteController::class, 'storeOrthodonticPhotoSet'])->name('ortho-photos.store');
    Route::post('/patients/{patientId}/legal-document', [CareSuiteController::class, 'storeLegalDocument'])->name('legal.store');
    Route::post('/patients/{patientId}/health-questionnaire', [CareSuiteController::class, 'storeHealthQuestionnaire'])->name('questionnaire.store');
    Route::post('/questionnaires', [CareSuiteController::class, 'storeQuestionnaireTemplate'])->name('questionnaires.store');
    Route::post('/patients/{patientId}/questionnaires/{questionnaire}/responses', [CareSuiteController::class, 'storeQuestionnaireResponse'])->name('questionnaires.responses.store');
    Route::post('/patients/{patientId}/ai-analysis', [CareSuiteController::class, 'requestAiImagingAnalysis'])->name('ai-analysis.store');
    Route::post('/patients/{patientId}/recalls/generate', [CareSuiteController::class, 'generatePatientRecalls'])->name('recalls.generate');
    Route::get('/medications/search', [CareSuiteController::class, 'searchMedications'])->name('medications.search');
    Route::get('/prescription-templates/{template}', [CareSuiteController::class, 'prescriptionTemplateData'])->name('prescriptions.template');
    Route::post('/patients/{patientId}/prescriptions', [CareSuiteController::class, 'storePrescription'])->name('prescriptions.store');
    Route::get('/prescriptions/{prescription}/pdf', [CareSuiteController::class, 'prescriptionPdf'])->name('prescriptions.pdf');
    Route::post('/prescriptions/{prescription}/send-email', [CareSuiteController::class, 'sendPrescriptionEmail'])->name('prescriptions.send-email');
});

Route::middleware(['web'])->prefix('care/module-3')->name('care.module3.')->group(function (): void {
    Route::get('/prescriptions/verify/{token}', [CareSuiteController::class, 'verifyPrescription'])->name('prescriptions.verify');
    Route::post('/orthanc/webhook', [CareSuiteController::class, 'orthancWebhook'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('orthanc.webhook');
});

Route::middleware(['web', 'auth', EnsureRole::class.':super_admin,admin,professional,assistant,secretary'])->prefix('care/module-4')->name('care.module4.')->group(function (): void {
    Route::get('/', [CareSuiteController::class, 'module4'])->name('index');
    Route::post('/batch', [CareSuiteController::class, 'createBatch'])->name('batch.store');
    Route::post('/batch/{batch}/validate', [CareSuiteController::class, 'validateBatch'])->name('batch.validate');
    Route::get('/batch/{batch}/labels', [CareSuiteController::class, 'batchLabels'])->name('batch.labels');
    Route::post('/trace', [CareSuiteController::class, 'scanPouch'])->name('trace.store');
    Route::post('/trace/ajax', [CareSuiteController::class, 'scanPouchAjax'])->name('trace.ajax');
    Route::post('/stock-item', [CareSuiteController::class, 'createStockItem'])->name('stock-item.store');
    Route::post('/stock-movement', [CareSuiteController::class, 'createStockMovement'])->name('stock-movement.store');
    Route::post('/stock-movement/scan-to-out', [CareSuiteController::class, 'scanStockOut'])->name('stock-movement.scan-out');
    Route::post('/lab-order', [CareSuiteController::class, 'createLabOrder'])->name('lab-order.store');
    Route::post('/lab-order/{labOrder}/status', [CareSuiteController::class, 'updateLabOrderStatus'])->name('lab-order.status');
    Route::post('/lab-order/{labOrder}/event', [CareSuiteController::class, 'addLabOrderEvent'])->name('lab-order.event');
    Route::get('/lab-order/{labOrder}/feed', [CareSuiteController::class, 'labOrderFeed'])->name('lab-order.feed');
});

Route::middleware(['web', 'signed'])->group(function (): void {
    Route::get('/care/module-3/treatment-plan/{plan}/signature', [CareSuiteController::class, 'treatmentPlanSignatureForm'])->name('care.module3.plan.signature.form');
    Route::post('/care/module-3/treatment-plan/{plan}/signature', [CareSuiteController::class, 'treatmentPlanSign'])->name('care.module3.plan.signature.sign');
    Route::get('/patient/waiting-room/{appointment}', [CareSuiteController::class, 'waitingRoom'])->name('care.waiting-room');
    Route::get('/patient/waiting-room/{appointment}/data', [CareSuiteController::class, 'waitingRoomData'])->name('care.waiting-room.data');
});

Route::middleware(['web', 'auth', EnsureRole::class.':super_admin,admin,professional,assistant', 'specialty:GYNECO,OMNI'])
    ->prefix('care/gynecology')
    ->name('care.gynecology.')
    ->group(function (): void {
        $ctrl = \Modules\Gynecology\Http\Controllers\GynecologyController::class;

        Route::get('/patients/{patientId}/dashboard', [$ctrl, 'dashboard'])->name('dashboard');

        Route::post('/patients/{patientId}/gynecological-history', [$ctrl, 'storeGynecologicalHistory'])->name('history.store');
        Route::post('/patients/{patientId}/pregnancy-record', [$ctrl, 'storePregnancyRecord'])->name('pregnancy.store');
        Route::post('/pregnancy-records/{pregnancyRecordId}/prenatal-visits', [$ctrl, 'storePrenatalVisit'])->name('prenatal-visit.store');
        Route::post('/patients/{patientId}/gynecological-exams', [$ctrl, 'storeGynecologicalExam'])->name('exam.store');
        Route::post('/patients/{patientId}/ultrasound-biometries', [$ctrl, 'storeUltrasoundBiometry'])->name('ultrasound.store');

        Route::get('/patients/{patientId}/pregnancy-history', [$ctrl, 'pregnancyHistory'])->name('pregnancy.history');
        Route::get('/patients/{patientId}/exam-history', [$ctrl, 'examHistory'])->name('exam.history');
        Route::get('/patients/{patientId}/ultrasound-history', [$ctrl, 'ultrasoundHistory'])->name('ultrasound.history');

        Route::patch('/patients/{patientId}/pregnancy/{pregnancyId}/quick-edit', [$ctrl, 'quickEditPregnancy'])->name('pregnancy.quick-edit');
        Route::get('/pregnancy-records/{pregnancyRecordId}/prenatal-visits', [$ctrl, 'prenatalVisits'])->name('prenatal-visits.list');
        Route::get('/patients/{patientId}/biometry-chart', [$ctrl, 'biometryChartData'])->name('biometry.chart');
    });

Route::middleware(['web', 'auth', EnsureRole::class.':super_admin,admin,professional,assistant', 'specialty:PEDIA,OMNI'])
    ->prefix('care/pediatrics')
    ->name('care.pediatrics.')
    ->group(function (): void {
        $ctrl = \Modules\Pediatrics\Http\Controllers\PediatricsController::class;

        Route::get('/patients/{patientId}/dashboard', [$ctrl, 'dashboard'])->name('dashboard');

        Route::post('/patients/{patientId}/birth-history', [$ctrl, 'storeBirthHistory'])->name('birth-history.store');
        Route::post('/patients/{patientId}/growth-record', [$ctrl, 'storeGrowthRecord'])->name('growth-record.store');
        Route::post('/patients/{patientId}/vaccination-record', [$ctrl, 'storeVaccinationRecord'])->name('vaccination-record.store');

        Route::get('/patients/{patientId}/vaccination-schedule', [$ctrl, 'vaccinationSchedule'])->name('vaccination-schedule');
        Route::get('/patients/{patientId}/growth-history', [$ctrl, 'growthHistory'])->name('growth-history');
    });

Route::middleware(['web', 'auth', EnsureRole::class.':super_admin,admin,professional,assistant', 'specialty:BURNS,OMNI'])
    ->prefix('care/burns')
    ->name('care.burns.')
    ->group(function (): void {
        $ctrl = \Modules\Burns\Http\Controllers\BurnsController::class;

        Route::get('/patients/{patientId}/dashboard', [$ctrl, 'dashboard'])->name('dashboard');

        Route::post('/patients/{patientId}/admissions', [$ctrl, 'storeAdmission'])->name('admission.store');
        Route::post('/admissions/{admissionId}/assessments', [$ctrl, 'storeAssessment'])->name('assessment.store');
        Route::post('/admissions/{admissionId}/fluid-resuscitation', [$ctrl, 'storeFluidResuscitation'])->name('fluid-resuscitation.store');
        Route::post('/admissions/{admissionId}/wound-evolutions', [$ctrl, 'storeWoundEvolution'])->name('wound-evolution.store');

        Route::post('/calculate-parkland', [$ctrl, 'calculateParkland'])->name('calculate-parkland');
        Route::get('/lund-browder', [$ctrl, 'getLundBrowderPercentages'])->name('lund-browder');
        Route::post('/wound-evolutions/{woundEvolutionId}/pharmacy-order', [$ctrl, 'sendPharmacyOrder'])->name('pharmacy-order.send');
    });

    Route::middleware(['web', 'auth', EnsureRole::class.':super_admin,admin,professional,assistant'])->prefix('care/rehab')->name('care.rehab.')->group(function (): void {
        $ctrl = \Modules\Rehab\Http\Controllers\RehabController::class;

        Route::get('/patients/{patientId}/dashboard', [$ctrl, 'dashboard'])->name('dashboard');
        Route::post('/patients/{patientId}/prescriptions', [$ctrl, 'storePrescription'])->name('prescription.store');
        Route::put('/prescriptions/{prescriptionId}', [$ctrl, 'updatePrescription'])->name('prescription.update');
        Route::post('/prescriptions/{prescriptionId}/evaluations', [$ctrl, 'storeEvaluation'])->name('evaluation.store');
        Route::post('/prescriptions/{prescriptionId}/sessions', [$ctrl, 'storeSession'])->name('session.store');
        Route::get('/prescriptions/{prescriptionId}/progress', [$ctrl, 'progressStats'])->name('progress');
    });

Route::middleware(['web', 'auth', EnsureRole::class.':super_admin,admin,professional,assistant', 'specialty:INTMED,OMNI'])
    ->prefix('care/internal-medicine')
    ->name('care.internal-medicine.')
    ->group(function (): void {
        $ctrl = \Modules\InternalMedicine\Http\Controllers\InternalMedicineController::class;

        Route::get('/patients/{patientId}/dashboard', [$ctrl, 'dashboard'])->name('dashboard');
        Route::post('/patients/{patientId}/conditions', [$ctrl, 'storeChronicCondition'])->name('condition.store');
        Route::post('/conditions/{conditionId}', [$ctrl, 'updateChronicCondition'])->name('condition.update');
        Route::delete('/conditions/{conditionId}', [$ctrl, 'destroyChronicCondition'])->name('condition.destroy');
        Route::post('/patients/{patientId}/lab-results', [$ctrl, 'storeLabResult'])->name('lab.store');
        Route::get('/patients/{patientId}/lab-history/{parameter}', [$ctrl, 'getLabHistory'])->name('lab.history');
        Route::post('/patients/{patientId}/scores', [$ctrl, 'storeScore'])->name('score.store');
        Route::post('/calculate/cockcroft', [$ctrl, 'calculateCockcroft'])->name('calc.cockcroft');
        Route::post('/calculate/mdrd', [$ctrl, 'calculateMdrd'])->name('calc.mdrd');
        Route::post('/calculate/bmi', [$ctrl, 'calculateBmi'])->name('calc.bmi');
        Route::post('/calculate/chads2-vasc', [$ctrl, 'calculateChads2Vasc'])->name('calc.chads2vasc');
    });
