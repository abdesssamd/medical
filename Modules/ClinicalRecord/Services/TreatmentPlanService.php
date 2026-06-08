<?php

namespace Modules\ClinicalRecord\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ClinicalRecord\Models\ClinicalProcedure;
use Modules\ClinicalRecord\Models\TreatmentPlan;
use Modules\ClinicalRecord\Models\TreatmentPlanProcedure;

class TreatmentPlanService
{
    /**
     * Create a new treatment plan.
     */
    public function createTreatmentPlan(
        int $patientId,
        int $practitionerId,
        string $name,
        ?string $objective = null
    ): TreatmentPlan {
        $plan = TreatmentPlan::create([
            'patient_id' => $patientId,
            'practitioner_id' => $practitionerId,
            'name' => $name,
            'objective' => $objective,
            'status' => TreatmentPlan::STATUS_DRAFT,
            'total_estimated_cost' => 0,
            'paid_amount' => 0,
        ]);

        Log::info('treatment_plan.created', [
            'plan_id' => $plan->id,
            'patient_id' => $patientId,
            'practitioner_id' => $practitionerId,
        ]);

        return $plan;
    }

    /**
     * Add a procedure to a treatment plan.
     */
    public function addProcedureToPlan(
        int $treatmentPlanId,
        int $patientId,
        int $practitionerId,
        int $specialtyId,
        string $procedureCode,
        string $procedureName,
        float $price,
        int $phaseNumber,
        int $orderInPhase,
        ?int $toothNumber = null,
        ?array $surfaces = null,
        ?string $description = null
    ): ClinicalProcedure {
        return DB::transaction(function () use (
            $treatmentPlanId, $patientId, $practitionerId, $specialtyId,
            $procedureCode, $procedureName, $price, $phaseNumber, $orderInPhase,
            $toothNumber, $surfaces, $description
        ): ClinicalProcedure {
            // Create the clinical procedure
            $procedure = ClinicalProcedure::create([
                'patient_id' => $patientId,
                'practitioner_id' => $practitionerId,
                'specialty_id' => $specialtyId,
                'tooth_number' => $toothNumber,
                'procedure_code' => $procedureCode,
                'name' => $procedureName,
                'description' => $description,
                'tooth_surfaces' => $surfaces,
                'price' => $price,
                'status' => ClinicalProcedure::STATUS_PLANNED,
            ]);

            // Link to treatment plan
            TreatmentPlanProcedure::create([
                'treatment_plan_id' => $treatmentPlanId,
                'procedure_id' => $procedure->id,
                'phase_number' => $phaseNumber,
                'order_in_phase' => $orderInPhase,
                'status' => TreatmentPlanProcedure::STATUS_PLANNED,
            ]);

            // Update treatment plan total cost
            $plan = TreatmentPlan::find($treatmentPlanId);
            $this->recalculateTotalCost($plan);

            return $procedure;
        });
    }

    /**
     * Add a phase to a treatment plan.
     */
    public function addPhase(
        int $treatmentPlanId,
        string $phaseName,
        int $order
    ): TreatmentPlan {
        $plan = TreatmentPlan::findOrFail($treatmentPlanId);
        $plan->addPhase($phaseName, $order);

        Log::info('treatment_plan.phase_added', [
            'plan_id' => $plan->id,
            'phase_name' => $phaseName,
            'order' => $order,
        ]);

        return $plan;
    }

    /**
     * Approve a treatment plan.
     */
    public function approvePlan(int $treatmentPlanId): TreatmentPlan
    {
        $plan = TreatmentPlan::findOrFail($treatmentPlanId);
        $plan->approve();

        Log::info('treatment_plan.approved', [
            'plan_id' => $plan->id,
        ]);

        return $plan;
    }

    /**
     * Start a treatment plan.
     */
    public function startPlan(int $treatmentPlanId): TreatmentPlan
    {
        $plan = TreatmentPlan::findOrFail($treatmentPlanId);
        $plan->start();

        Log::info('treatment_plan.started', [
            'plan_id' => $plan->id,
        ]);

        return $plan;
    }

    /**
     * Complete a treatment plan.
     */
    public function completePlan(int $treatmentPlanId): TreatmentPlan
    {
        $plan = TreatmentPlan::findOrFail($treatmentPlanId);
        $plan->complete();

        Log::info('treatment_plan.completed', [
            'plan_id' => $plan->id,
        ]);

        return $plan;
    }

    /**
     * Record a payment against a treatment plan.
     */
    public function recordPayment(int $treatmentPlanId, float $amount, ?string $method = null): TreatmentPlan
    {
        $plan = TreatmentPlan::findOrFail($treatmentPlanId);
        $plan->increment('paid_amount', $amount);

        Log::info('treatment_plan.payment_recorded', [
            'plan_id' => $plan->id,
            'amount' => $amount,
            'method' => $method,
        ]);

        return $plan->fresh();
    }

    /**
     * Get the progress of a treatment plan.
     */
    public function getPlanProgress(int $treatmentPlanId): array
    {
        $plan = TreatmentPlan::findOrFail($treatmentPlanId);
        $phases = $plan->phases ?? [];

        $phaseProgress = [];
        foreach ($phases as $phase) {
            $phaseNumber = $phase['order'] ?? $phase['phase_number'] ?? 0;
            $totalInPhase = $plan->procedures()->where('phase_number', $phaseNumber)->count();
            $completedInPhase = $plan->procedures()
                ->where('phase_number', $phaseNumber)
                ->whereHas('procedure', fn ($q) => $q->where('status', ClinicalProcedure::STATUS_COMPLETED))
                ->count();

            $phaseProgress[] = [
                'phase_name' => $phase['name'] ?? "Phase {$phaseNumber}",
                'phase_number' => $phaseNumber,
                'total' => $totalInPhase,
                'completed' => $completedInPhase,
                'progress_percentage' => $totalInPhase > 0 ? round(($completedInPhase / $totalInPhase) * 100, 2) : 0,
            ];
        }

        return [
            'plan' => $plan,
            'overall_progress' => $plan->completion_progress,
            'payment_progress' => $plan->payment_progress,
            'remaining_amount' => $plan->remaining_amount,
            'phases' => $phaseProgress,
        ];
    }

    /**
     * Recalculate the total estimated cost of a treatment plan.
     */
    private function recalculateTotalCost(TreatmentPlan $plan): void
    {
        $total = $plan->procedures()
            ->with('procedure')
            ->get()
            ->sum(fn ($entry) => (float) ($entry->procedure?->price ?? 0));

        $plan->update(['total_estimated_cost' => $total]);
    }

    /**
     * Archive a completed treatment plan.
     */
    public function archivePlan(int $treatmentPlanId): TreatmentPlan
    {
        $plan = TreatmentPlan::findOrFail($treatmentPlanId);
        $plan->archive();

        Log::info('treatment_plan.archived', [
            'plan_id' => $plan->id,
        ]);

        return $plan;
    }

    /**
     * Get all active treatment plans for a patient.
     */
    public function getActivePlansForPatient(int $patientId): array
    {
        return TreatmentPlan::forPatient($patientId)
            ->active()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'status' => $plan->status,
                'total_cost' => $plan->total_estimated_cost,
                'paid_amount' => $plan->paid_amount,
                'remaining_amount' => $plan->remaining_amount,
                'payment_progress' => $plan->payment_progress,
                'completion_progress' => $plan->completion_progress,
                'created_at' => $plan->created_at->format('d/m/Y'),
            ])
            ->all();
    }
}
