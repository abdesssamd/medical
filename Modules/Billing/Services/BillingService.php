<?php

namespace Modules\Billing\Services;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\InsuranceClaim;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\InvoiceLineItem;
use Modules\Billing\Models\InsuranceCompany;
use Modules\Billing\Models\PatientInsuranceSubscription;
use Modules\Billing\Models\Payment;
use Modules\ClinicalRecord\Models\ClinicalProcedure;

class BillingService
{
    /**
     * Create an invoice from clinical procedures.
     */
    public function createInvoiceFromProcedures(
        int $patientId,
        array $procedureIds,
        ?int $practitionerId = null,
        ?int $treatmentPlanId = null,
        ?float $discountAmount = null,
        ?float $taxRate = null,
        ?string $notes = null
    ): Invoice {
        return DB::transaction(function () use (
            $patientId, $procedureIds, $practitionerId, $treatmentPlanId, $discountAmount, $taxRate, $notes
        ): Invoice {
            $procedures = ClinicalProcedure::whereIn('id', $procedureIds)->get();

            if ($procedures->isEmpty()) {
                throw new \InvalidArgumentException('No procedures found to invoice.');
            }

            // Calculate totals
            $subtotal = $procedures->sum('price');
            $taxRate = $taxRate ?? 0;
            $taxAmount = $subtotal * ($taxRate / 100);
            $discountAmount = $discountAmount ?? 0;
            $total = $subtotal + $taxAmount - $discountAmount;

            // Create invoice
            $invoice = Invoice::create([
                'patient_id' => $patientId,
                'practitioner_id' => $practitionerId,
                'treatment_plan_id' => $treatmentPlanId,
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => Invoice::STATUS_DRAFT,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'notes' => $notes,
            ]);

            // Create line items
            foreach ($procedures as $procedure) {
                InvoiceLineItem::create([
                    'invoice_id' => $invoice->id,
                    'procedure_id' => $procedure->id,
                    'description' => $procedure->name,
                    'procedure_code' => $procedure->procedure_code,
                    'quantity' => 1,
                    'unit_price' => $procedure->price,
                ]);
            }

            // Mark procedures as billed
            ClinicalProcedure::whereIn('id', $procedureIds)
                ->where('status', ClinicalProcedure::STATUS_PLANNED)
                ->update(['status' => ClinicalProcedure::STATUS_COMPLETED]);

            Log::info('invoice.created', [
                'invoice_id' => $invoice->id,
                'patient_id' => $patientId,
                'total' => $total,
                'procedure_count' => $procedures->count(),
            ]);

            return $invoice;
        });
    }

    /**
     * Record a payment for an invoice.
     */
    public function recordPayment(
        int $invoiceId,
        float $amount,
        string $method,
        ?string $reference = null,
        ?User $receivedBy = null
    ): Payment {
        $invoice = Invoice::findOrFail($invoiceId);

        if ($invoice->isPaid()) {
            throw new \InvalidArgumentException('Invoice is already fully paid.');
        }

        if ($amount > $invoice->remaining_amount) {
            throw new \InvalidArgumentException('Payment amount exceeds remaining balance.');
        }

        $payment = $invoice->recordPayment($amount, $method, $reference, $receivedBy);

        Log::info('invoice.payment_recorded', [
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'method' => $method,
            'remaining' => $invoice->fresh()->remaining_amount,
        ]);

        return $payment;
    }

    /**
     * Create an insurance claim for an invoice.
     */
    public function createInsuranceClaim(
        int $invoiceId,
        int $insuranceCompanyId,
        ?int $patientInsuranceId = null
    ): InsuranceClaim {
        $invoice = Invoice::findOrFail($invoiceId);
        $insuranceCompany = InsuranceCompany::findOrFail($insuranceCompanyId);

        // Calculate claimed amount (total of line items)
        $claimedAmount = $invoice->lineItems->sum('total_price');

        // Create the claim
        $claim = InsuranceClaim::create([
            'invoice_id' => $invoiceId,
            'insurance_company_id' => $insuranceCompanyId,
            'patient_insurance_id' => $patientInsuranceId,
            'claimed_amount' => $claimedAmount,
            'status' => InsuranceClaim::STATUS_PENDING,
        ]);

        Log::info('insurance_claim.created', [
            'claim_id' => $claim->id,
            'invoice_id' => $invoiceId,
            'insurance_company_id' => $insuranceCompanyId,
            'claimed_amount' => $claimedAmount,
        ]);

        return $claim;
    }

    /**
     * Submit a claim to the insurance company.
     */
    public function submitClaim(int $claimId): InsuranceClaim
    {
        $claim = InsuranceClaim::findOrFail($claimId);
        $claim->markSubmitted();

        Log::info('insurance_claim.submitted', [
            'claim_id' => $claimId,
            'claim_number' => $claim->claim_number,
        ]);

        return $claim;
    }

    /**
     * Approve a claim with the approved amount.
     */
    public function approveClaim(int $claimId, float $approvedAmount): InsuranceClaim
    {
        return DB::transaction(function () use ($claimId, $approvedAmount): InsuranceClaim {
            $claim = InsuranceClaim::findOrFail($claimId);
            $claim->markApproved($approvedAmount);

            // Record insurance payment
            $claim->invoice->recordPayment(
                $approvedAmount,
                Payment::METHOD_INSURANCE,
                "Insurance payment - {$claim->claim_number}"
            );

            Log::info('insurance_claim.approved', [
                'claim_id' => $claimId,
                'approved_amount' => $approvedAmount,
                'patient_remaining' => $claim->patient_remaining,
            ]);

            return $claim;
        });
    }

    /**
     * Get patient's outstanding balance.
     */
    public function getPatientOutstandingBalance(int $patientId): array
    {
        $invoices = Invoice::forPatient($patientId)->unpaid()->get();

        return [
            'total_outstanding' => $invoices->sum('remaining_amount'),
            'invoice_count' => $invoices->count(),
            'overdue_invoices' => $invoices->filter(fn ($inv) => $inv->isOverdue())->count(),
            'invoices' => $invoices->map(fn ($inv) => [
                'invoice_number' => $inv->invoice_number,
                'total' => $inv->total,
                'paid' => $inv->paid_amount,
                'remaining' => $inv->remaining_amount,
                'due_date' => $inv->due_date?->format('d/m/Y'),
                'is_overdue' => $inv->isOverdue(),
            ]),
        ];
    }

    /**
     * Get revenue statistics for a date range.
     */
    public function getRevenueStatistics(string $fromDate, string $toDate): array
    {
        $invoices = Invoice::betweenDates($fromDate, $toDate);
        $payments = Payment::betweenDates($fromDate, $toDate);

        return [
            'total_invoiced' => (float) (clone $invoices)->sum('total'),
            'total_paid' => (float) (clone $payments)->sum('amount'),
            'total_outstanding' => (float) (clone $invoices)->sum('remaining_amount'),
            'payment_count' => $payments->count(),
            'invoice_count' => (clone $invoices)->count(),
            'paid_invoice_count' => (clone $invoices)->where('status', Invoice::STATUS_PAID)->count(),
            'by_payment_method' => $payments->groupBy('method')->map(fn ($group) => [
                'count' => $group->count(),
                'total' => (float) $group->sum('amount'),
            ]),
        ];
    }

    /**
     * Get the primary insurance for a patient.
     */
    public function getPatientPrimaryInsurance(int $patientId): ?PatientInsuranceSubscription
    {
        return PatientInsuranceSubscription::forPatient($patientId)
            ->primary()
            ->active()
            ->first();
    }
}
