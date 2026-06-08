<?php

namespace Modules\ClinicalRecord\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ClinicalRecord\Models\DentalChart;
use Modules\ClinicalRecord\Models\ClinicalProcedure;

class DentalChartService
{
    /**
     * Create a new dental chart for a patient.
     */
    public function createChart(int $patientId, string $version = 'adult', ?int $userId = null): DentalChart
    {
        $chart = DentalChart::create([
            'patient_id' => $patientId,
            'version' => $version,
            'created_by' => $userId,
            'teeth_status' => [],
        ]);

        // Initialize with default status
        $chart->initializeWithDefaults($version);

        Log::info('dental_chart.created', [
            'chart_id' => $chart->id,
            'patient_id' => $patientId,
            'version' => $version,
        ]);

        return $chart;
    }

    /**
     * Get or create the latest dental chart for a patient.
     */
    public function getOrCreateLatestChart(int $patientId, string $version = 'adult', ?int $userId = null): DentalChart
    {
        $chart = DentalChart::forPatient($patientId)
            ->where('version', $version)
            ->latestFirst()
            ->first();

        if (! $chart) {
            $chart = $this->createChart($patientId, $version, $userId);
        }

        return $chart;
    }

    /**
     * Update tooth status in the dental chart.
     */
    public function updateToothStatus(
        DentalChart $chart,
        int $toothNumber,
        string $status,
        int $userId,
        ?array $procedureDetails = null
    ): DentalChart {
        $chart->updateToothStatus($toothNumber, $status, $userId, $procedureDetails);

        Log::info('dental_chart.tooth_updated', [
            'chart_id' => $chart->id,
            'tooth_number' => $toothNumber,
            'status' => $status,
            'user_id' => $userId,
        ]);

        return $chart->fresh();
    }

    /**
     * Record a clinical procedure on a tooth and update the chart.
     */
    public function recordProcedureOnTooth(
        int $patientId,
        int $toothNumber,
        string $procedureCode,
        string $procedureName,
        float $price,
        int $practitionerId,
        int $specialtyId,
        ?int $appointmentId = null,
        ?array $surfaces = null,
        ?array $materialsUsed = null,
        ?string $description = null
    ): ClinicalProcedure {
        return DB::transaction(function () use (
            $patientId, $toothNumber, $procedureCode, $procedureName, $price,
            $practitionerId, $specialtyId, $appointmentId, $surfaces, $materialsUsed, $description
        ): ClinicalProcedure {
            // Create the clinical procedure
            $procedure = ClinicalProcedure::create([
                'patient_id' => $patientId,
                'appointment_id' => $appointmentId,
                'practitioner_id' => $practitionerId,
                'specialty_id' => $specialtyId,
                'tooth_number' => $toothNumber,
                'procedure_code' => $procedureCode,
                'name' => $procedureName,
                'description' => $description,
                'tooth_surfaces' => $surfaces,
                'price' => $price,
                'status' => ClinicalProcedure::STATUS_PLANNED,
                'materials_used' => $materialsUsed,
            ]);

            // Update the dental chart
            $chart = $this->getOrCreateLatestChart($patientId);
            $this->updateToothStatus(
                $chart,
                $toothNumber,
                $this->mapProcedureCodeToStatus($procedureCode),
                $practitionerId,
                [
                    'type' => $procedureCode,
                    'details' => [
                        'procedure_id' => $procedure->id,
                        'name' => $procedureName,
                        'price' => $price,
                    ],
                ]
            );

            return $procedure;
        });
    }

    /**
     * Get the complete dental history for a patient.
     */
    public function getDentalHistory(int $patientId): array
    {
        $chart = $this->getOrCreateLatestChart($patientId);
        $procedures = ClinicalProcedure::forPatient($patientId)
            ->completed()
            ->orderByDesc('performed_at')
            ->get();

        return [
            'chart' => $chart,
            'teeth_summary' => $chart->getTeethSummary(),
            'procedures' => $procedures->map(fn ($proc) => [
                'id' => $proc->id,
                'tooth_number' => $proc->tooth_number,
                'procedure_code' => $proc->procedure_code,
                'name' => $proc->name,
                'price' => $proc->price,
                'performed_at' => $proc->performed_at,
                'practitioner_name' => $proc->practitioner?->display_name,
                'materials_used' => $proc->materials_used,
            ]),
        ];
    }

    /**
     * Get the estimated cost for planned procedures.
     */
    public function getEstimatedCostForPlannedProcedures(int $patientId): float
    {
        return ClinicalProcedure::forPatient($patientId)
            ->status(ClinicalProcedure::STATUS_PLANNED)
            ->sum('price');
    }

    /**
     * Map a procedure code to a tooth status.
     */
    private function mapProcedureCodeToStatus(string $procedureCode): string
    {
        $mapping = [
            'D0120' => 'present', // Evaluation
            'D1110' => 'present', // Detartrage
            'D2140' => 'filling', // Composite
            'D2750' => 'crown', // Crown
            'D3310' => 'root_canal', // Root canal
            'D5110' => 'present', // Denture
            'D6010' => 'implant', // Implant
            'D7140' => 'extracted', // Extraction
        ];

        return $mapping[$procedureCode] ?? 'present';
    }

    /**
     * Export dental chart data as array (for printing or PDF).
     */
    public function exportChartAsArray(DentalChart $chart): array
    {
        return [
            'patient_name' => $chart->patient->full_name,
            'patient_mrn' => $chart->patient->medical_record_number,
            'chart_version' => $chart->version,
            'created_at' => $chart->created_at->format('d/m/Y H:i'),
            'teeth_summary' => $chart->getTeethSummary(),
            'full_chart_data' => $chart->teeth_status,
        ];
    }
}
