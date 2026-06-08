<?php

namespace Modules\Billing\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\Log;
use Modules\Appointment\Models\ReminderDispatchLog;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\PaymentRecoveryAction;
use Modules\Billing\Models\TeletransmissionBatch;

class FinancialOperationsService
{
    public function unpaidDashboardData(): array
    {
        $unpaid = Invoice::with('patient')
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID])
            ->orderBy('due_date')
            ->limit(200)
            ->get();

        return [
            'count' => $unpaid->count(),
            'overdue_count' => $unpaid->filter(fn (Invoice $inv) => $inv->isOverdue())->count(),
            'total_remaining' => (float) $unpaid->sum('remaining_amount'),
            'items' => $unpaid,
        ];
    }

    public function generateTeletransmissionBatch(?int $createdBy = null): TeletransmissionBatch
    {
        $invoices = Invoice::with('patient')
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID])
            ->where('remaining_amount', '>', 0)
            ->limit(200)
            ->get();

        $batch = TeletransmissionBatch::create([
            'batch_number' => 'TEL-'.now()->format('Ymd-His'),
            'created_by' => $createdBy,
            'generated_on' => now()->toDateString(),
            'status' => 'generated',
            'invoice_count' => $invoices->count(),
            'total_amount' => (float) $invoices->sum('remaining_amount'),
            'payload' => $invoices->map(fn (Invoice $inv) => [
                'invoice_id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'patient' => $inv->patient?->full_name,
                'remaining_amount' => (float) $inv->remaining_amount,
                'due_date' => $inv->due_date?->toDateString(),
            ])->values()->all(),
        ]);

        return $batch;
    }

    public function sendUnpaidReminder(Invoice $invoice, string $channel = 'sms', ?int $performedBy = null): PaymentRecoveryAction
    {
        $patient = $invoice->patient;
        $target = $channel === 'email' ? ($patient?->email ?? null) : ($patient?->phone ?? null);

        $message = sprintf(
            'Rappel facture %s: reste a payer %.2f MAD. Merci de contacter le cabinet.',
            $invoice->invoice_number,
            (float) $invoice->remaining_amount
        );

        Log::info('billing.unpaid_reminder.sent', [
            'invoice_id' => $invoice->id,
            'channel' => $channel,
            'target' => $target,
            'message' => $message,
        ]);

        ReminderDispatchLog::create([
            'patient_id' => $invoice->patient_id,
            'appointment_id' => null,
            'patient_recall_id' => null,
            'channel' => $channel,
            'context' => 'unpaid',
            'target' => $target,
            'status' => $target ? 'sent' : 'failed',
            'payload' => ['invoice_id' => $invoice->id, 'message' => $message],
            'sent_at' => now(),
        ]);

        return PaymentRecoveryAction::create([
            'invoice_id' => $invoice->id,
            'patient_id' => $invoice->patient_id,
            'performed_by' => $performedBy,
            'channel' => $channel,
            'status' => $target ? 'sent' : 'failed',
            'message' => $message,
            'performed_at' => now(),
        ]);
    }

    public function autoRemindOverdueInvoices(): int
    {
        $count = 0;
        $overdues = Invoice::with('patient')
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID])
            ->where('remaining_amount', '>', 0)
            ->whereDate('due_date', '<', now()->toDateString())
            ->limit(100)
            ->get();

        foreach ($overdues as $invoice) {
            $this->sendUnpaidReminder($invoice, 'sms');
            $count++;
        }

        return $count;
    }
}
