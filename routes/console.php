<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\ClinicalRecord\Services\OfficialMedicationApiClient;
use Modules\Appointment\Services\RecallAutomationService;
use Modules\Billing\Services\FinancialOperationsService;
use Modules\RIS\Models\RisOrder;
use Modules\RIS\Services\OrthancService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('health:dispatch-reminders', function (RecallAutomationService $recall, FinancialOperationsService $financial) {
    $sent24h = $recall->dispatchAppointment24hReminders();
    $sentRecall = $recall->dispatchDueRecalls();
    $sentUnpaid = $financial->autoRemindOverdueInvoices();

    $this->info("Reminders sent - appointment_24h: {$sent24h}, recall: {$sentRecall}, unpaid: {$sentUnpaid}");
})->purpose('Dispatch automated reminders for appointments, recall and unpaid invoices.');

Schedule::command('health:dispatch-reminders')->hourly();

Artisan::command('medication:provider-ping', function (OfficialMedicationApiClient $client) {
    $result = $client->ping();
    if (! ($result['ok'] ?? false)) {
        $this->error('Medication provider ping failed.');
        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return 1;
    }

    $this->info('Medication provider ping OK.');
    $this->line('HTTP status: '.($result['status'] ?? 'n/a'));
    return 0;
})->purpose('Test medication provider connectivity (VIDAL-ready).');

Artisan::command('ris:sync-orthanc', function (OrthancService $orthancService) {
    $orders = RisOrder::query()
        ->with(['patient', 'procedure', 'modality', 'requestedBy'])
        ->whereIn('status', [RisOrder::STATUS_ORDONNE, RisOrder::STATUS_EN_ATTENTE])
        ->orderBy('requested_at')
        ->orderBy('id')
        ->get();

    $scanned = 0;
    $updated = 0;
    $skipped = 0;

    foreach ($orders as $order) {
        $scanned++;
        $result = $orthancService->reconcileOrderFromOrthanc($order);

        if (! ($result['ok'] ?? false)) {
            $skipped++;
            $this->warn("RIS {$order->accession_number}: " . ($result['message'] ?? 'synchronisation impossible'));
            continue;
        }

        if ($result['matched'] ?? false) {
            $updated++;
            $this->info("RIS {$order->accession_number}: Images recues");
        }
    }

    $this->info("Synchronisation RIS/Orthanc terminee. Examens analyses: {$scanned}, mises a jour: {$updated}, sans correspondance: {$skipped}.");

    return 0;
})->purpose('Synchronize active RIS orders with Orthanc studies using PatientID (MRN) as the primary key.');
