<?php

namespace Modules\Appointment\Listeners;

use Modules\Appointment\Models\SecretaryTask;

class CreatePaymentTask
{
    /**
     * Crée une tâche de paiement lors de création de facture.
     * Écoute: InvoiceCreated.
     */
    public function handle($event): void
    {
        $invoice = $event->invoice;
        $appointment = $invoice->appointment;

        if (!$appointment) {
            return;
        }

        // Vérifier si paiement immédiat
        if ($invoice->paid_amount >= $invoice->amount) {
            return; // Déjà payée
        }

        // Créer tâche paiement
        SecretaryTask::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'task_type' => SecretaryTask::TYPE_PAYMENT_DUE,
            'status' => SecretaryTask::STATUS_OPEN,
            'priority' => SecretaryTask::PRIORITY_HIGH,
            'title' => 'Encaisser paiement',
            'description' => 'Montant: ' . number_format($invoice->amount - $invoice->paid_amount, 2) . '€',
            'due_at' => $appointment->appointment_date->copy()->addDay(),
            'metadata' => [
                'invoice_id' => $invoice->id,
                'amount_due' => $invoice->amount - $invoice->paid_amount,
            ],
        ]);

        \Log::info('task.payment_created', [
            'invoice_id' => $invoice->id,
            'appointment_id' => $appointment->id,
        ]);
    }
}
