<?php

namespace Modules\ClinicalRecord\Services;

use Illuminate\Support\Facades\DB;
use Modules\Billing\Models\TreatmentQuote;
use Modules\Billing\Models\TreatmentQuoteItem;
use Modules\ClinicalRecord\Models\TreatmentPlan;

class AdvancedTreatmentQuoteService
{
    public function createQuoteFromPlan(int $treatmentPlanId, float $insuranceRate = 70.0, float $mutualRate = 0.0): TreatmentQuote
    {
        return DB::transaction(function () use ($treatmentPlanId, $insuranceRate, $mutualRate): TreatmentQuote {
            $plan = TreatmentPlan::with(['procedures.procedure'])->findOrFail($treatmentPlanId);

            $quote = TreatmentQuote::create([
                'treatment_plan_id' => $plan->id,
                'patient_id' => $plan->patient_id,
                'practitioner_id' => $plan->practitioner_id,
                'quote_number' => TreatmentQuote::generateQuoteNumber(),
                'quote_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'insurance_rate' => $insuranceRate,
                'status' => 'draft',
                'consent_status' => 'pending',
            ]);

            $subtotal = 0.0;
            $insuranceAmount = 0.0;
            $mutualAmount = 0.0;

            foreach ($plan->procedures as $entry) {
                $price = (float) ($entry->procedure?->price ?? 0);
                $total = $price;
                $insuranceShare = round($total * ($insuranceRate / 100), 2);
                $remainingAfterInsurance = max(0, $total - $insuranceShare);
                $mutualShare = round($remainingAfterInsurance * ($mutualRate / 100), 2);
                $patientShare = max(0, $total - $insuranceShare - $mutualShare);

                TreatmentQuoteItem::create([
                    'treatment_quote_id' => $quote->id,
                    'procedure_id' => $entry->procedure_id,
                    'code' => $entry->procedure?->procedure_code,
                    'label' => $entry->procedure?->name ?? 'Acte',
                    'phase_number' => (int) ($entry->phase_number ?? 1),
                    'quantity' => 1,
                    'unit_price' => $price,
                    'total_price' => $total,
                    'insurance_coverage_rate' => $insuranceRate,
                    'insurance_share' => $insuranceShare,
                    'patient_share' => $patientShare,
                ]);

                $subtotal += $total;
                $insuranceAmount += $insuranceShare;
                $mutualAmount += $mutualShare;
            }

            $patientAmount = max(0, $subtotal - $insuranceAmount - $mutualAmount);

            $quote->update([
                'subtotal' => $subtotal,
                'insurance_amount' => $insuranceAmount,
                'mutual_amount' => $mutualAmount,
                'patient_amount' => $patientAmount,
            ]);

            return $quote->fresh(['items', 'patient', 'practitioner', 'treatmentPlan']);
        });
    }

    public function signQuoteOnTablet(TreatmentQuote $quote, string $patientName, string $signatureData, ?string $ip = null): TreatmentQuote
    {
        $quote->update([
            'status' => 'accepted',
            'consent_status' => 'signed',
            'signed_at' => now(),
            'signed_by_patient_name' => $patientName,
            'signature_payload' => [
                'data_url' => $signatureData,
                'signed_ip' => $ip,
                'signed_at' => now()->toDateTimeString(),
            ],
        ]);

        return $quote->fresh();
    }

    public function buildPdfData(TreatmentQuote $quote): array
    {
        $quote->loadMissing(['patient', 'practitioner', 'items', 'treatmentPlan']);

        return [
            'quote' => $quote,
            'patient' => $quote->patient,
            'practitioner' => $quote->practitioner,
            'items' => $quote->items->sortBy(['phase_number', 'id'])->values(),
            'generated_at' => now(),
        ];
    }
}
