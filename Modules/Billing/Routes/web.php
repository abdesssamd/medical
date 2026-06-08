<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\BillingController;

Route::middleware(['web', 'auth'])->prefix('billing')->name('billing.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [BillingController::class, 'dashboard'])->name('dashboard');
    
    // Invoices
    Route::get('/invoices', [BillingController::class, 'invoices'])->name('invoices');
    Route::get('/invoices/{invoiceId}', [BillingController::class, 'showInvoice'])->name('invoices.show');
    Route::get('/patients/{patientId}/invoices/create', [BillingController::class, 'createInvoiceFromProcedures'])->name('invoices.create-from-procedures');
    Route::post('/patients/{patientId}/invoices/from-procedures', [BillingController::class, 'storeInvoiceFromProcedures'])->name('invoices.store-from-procedures');
    Route::post('/invoices/{invoiceId}/payments', [BillingController::class, 'recordPayment'])->name('invoices.record-payment');
    Route::post('/invoices/{invoiceId}/remind', [BillingController::class, 'remindInvoice'])->name('invoices.remind');
    Route::post('/teletransmission/generate', [BillingController::class, 'teletransmissionGenerate'])->name('teletransmission.generate');
    
    // Insurance
    Route::get('/insurance/companies', [BillingController::class, 'insuranceCompanies'])->name('insurance.companies');
    Route::get('/insurance/claims', [BillingController::class, 'insuranceClaims'])->name('insurance.claims');
    Route::post('/insurance/claims/{claimId}/submit', [BillingController::class, 'submitClaim'])->name('insurance.claims.submit');
    Route::post('/insurance/claims/{claimId}/approve', [BillingController::class, 'approveClaim'])->name('insurance.claims.approve');
    
    // API
    Route::get('/api/patients/{patientId}/balance', [BillingController::class, 'patientBalance'])->name('api.patient-balance');
});
