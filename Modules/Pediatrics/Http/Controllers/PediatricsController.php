<?php

namespace Modules\Pediatrics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Pediatrics\Models\BirthHistory;
use Modules\Pediatrics\Models\GrowthRecord;
use Modules\Pediatrics\Models\VaccinationRecord;
use Modules\Pediatrics\Services\PediatricGrowthService;
use Modules\Pediatrics\Services\VaccinationScheduleService;

class PediatricsController extends Controller
{
    public function __construct(
        private readonly PediatricGrowthService $growthService,
        private readonly VaccinationScheduleService $vaccinationService,
    ) {}

    public function dashboard(int $patientId): JsonResponse
    {
        $data = $this->growthService->patientDashboard($patientId);
        $vaccinationSchedule = $this->vaccinationService->generateSchedule($patientId);
        $vaccinationSummary = $this->vaccinationService->getVaccinationSummary($patientId);

        return response()->json(array_merge($data, [
            'vaccination_schedule' => $vaccinationSchedule,
            'vaccination_summary' => $vaccinationSummary,
        ]));
    }

    public function storeBirthHistory(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:birth_histories,id',
            'delivery_type' => 'nullable|string|in:spontaneous,assisted,cesarean,breech',
            'delivery_place' => 'nullable|string|max:100',
            'gestational_age_weeks' => 'nullable|integer|min:22|max:45',
            'presentation_at_birth' => 'nullable|string|max:30',
            'apgar_1min' => 'nullable|integer|min:0|max:10',
            'apgar_5min' => 'nullable|integer|min:0|max:10',
            'apgar_10min' => 'nullable|integer|min:0|max:10',
            'birth_weight_grams' => 'nullable|numeric|min:300|max:7000',
            'birth_length_cm' => 'nullable|numeric|min:20|max:70',
            'birth_head_circumference_cm' => 'nullable|numeric|min:20|max:50',
            'neonatal_resuscitation' => 'nullable|boolean',
            'resuscitation_details' => 'nullable|string|max:200',
            'nicu_admission' => 'nullable|boolean',
            'nicu_days' => 'nullable|integer|min:0|max:365',
            'jaundice' => 'nullable|boolean',
            'jaundice_type' => 'nullable|string|max:50',
            'jaundice_onset_date' => 'nullable|date',
            'jaundice_treatment' => 'nullable|string|max:100',
            'breastfeeding' => 'nullable|boolean',
            'feeding_type' => 'nullable|string|in:breast,formula,mixed',
            'vitamin_k_given' => 'nullable|string|max:20',
            'hepatitis_b_birth_dose' => 'nullable|boolean',
            'newborn_screening_done' => 'nullable|boolean',
            'newborn_screening_result' => 'nullable|string|max:100',
            'maternal_complications' => 'nullable|string|max:2000',
            'neonatal_complications' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $history = $this->growthService->storeBirthHistory($patientId, $validated);

        return response()->json([
            'message' => 'Antécédents néonataux enregistrés.',
            'birth_history' => $history->fresh(),
        ], 201);
    }

    public function storeGrowthRecord(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:growth_records,id',
            'measurement_date' => 'required|date',
            'age_months' => 'nullable|integer|min:0|max:240',
            'weight_kg' => 'nullable|numeric|min:0.3|max:200',
            'height_cm' => 'nullable|numeric|min:20|max:220',
            'head_circumference_cm' => 'nullable|numeric|min:20|max:70',
            'arm_circumference_cm' => 'nullable|numeric|min:5|max:50',
            'notes' => 'nullable|string|max:2000',
        ]);

        $record = $this->growthService->storeGrowthRecord($patientId, $validated);

        return response()->json([
            'message' => 'Mesure de croissance enregistrée.',
            'growth_record' => $record->fresh(),
        ], 201);
    }

    public function storeVaccinationRecord(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:vaccination_records,id',
            'vaccine_id' => 'required|integer|exists:vaccines,id',
            'scheduled_date' => 'nullable|date',
            'administered_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:50',
            'manufacturer' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
            'injection_site' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:pending,administered,overdue,refused,contraindicated',
            'adverse_reaction' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $record = $this->vaccinationService->storeVaccinationRecord($patientId, $validated);

        return response()->json([
            'message' => 'Vaccination enregistrée.',
            'vaccination_record' => $record->fresh(['vaccine']),
        ], 201);
    }

    public function vaccinationSchedule(int $patientId): JsonResponse
    {
        $schedule = $this->vaccinationService->generateSchedule($patientId);
        $summary = $this->vaccinationService->getVaccinationSummary($patientId);

        return response()->json([
            'schedule' => $schedule,
            'summary' => $summary,
        ]);
    }

    public function growthHistory(int $patientId): JsonResponse
    {
        $records = GrowthRecord::where('patient_id', $patientId)
            ->orderBy('measurement_date')
            ->get();

        return response()->json(['growth_records' => $records]);
    }
}
