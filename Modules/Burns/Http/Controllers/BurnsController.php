<?php

namespace Modules\Burns\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Burns\Models\BurnAdmission;
use Modules\Burns\Models\BurnAssessment;
use Modules\Burns\Models\FluidResuscitation;
use Modules\Burns\Models\WoundEvolution;
use Modules\Burns\Services\BurnsService;
use Modules\Burns\Services\FluidResuscitationService;

class BurnsController extends Controller
{
    public function __construct(
        private readonly BurnsService $burnsService,
        private readonly FluidResuscitationService $fluidService
    ) {}

    public function dashboard(int $patientId): JsonResponse
    {
        $data = $this->burnsService->patientDashboard($patientId);

        return response()->json($data);
    }

    public function storeAdmission(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'burn_type' => 'required|string|in:thermal,chemical,electrical,radiation',
            'burn_cause' => 'nullable|string|max:200',
            'accident_datetime' => 'required|date',
            'admission_datetime' => 'nullable|date',
            'admission_weight_kg' => 'required|numeric|min:1|max:300',
            'admission_height_cm' => 'nullable|numeric|min:30|max:250',
            'admission_location' => 'nullable|string|max:100',
            'mechanism_description' => 'nullable|string',
            'inhalation_injury_suspected' => 'nullable|boolean',
            'inhalation_injury_confirmed' => 'nullable|boolean',
            'inhalation_severity' => 'nullable|string|in:mild,moderate,severe',
            'associated_injuries' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $admission = $this->burnsService->storeAdmission($patientId, $validated);

        return response()->json([
            'message' => 'Admission enregistrée.',
            'admission' => $admission->fresh(),
        ], 201);
    }

    public function storeAssessment(Request $request, int $admissionId): JsonResponse
    {
        $validated = $request->validate([
            'assessment_datetime' => 'nullable|date',
            'partial_thickness_area' => 'nullable|numeric|min:0|max:100',
            'full_thickness_area' => 'nullable|numeric|min:0|max:100',
            'superficial_area' => 'nullable|numeric|min:0|max:100',
            'head_face_percent' => 'nullable|numeric|min:0|max:100',
            'neck_percent' => 'nullable|numeric|min:0|max:100',
            'anterior_trunk_percent' => 'nullable|numeric|min:0|max:100',
            'posterior_trunk_percent' => 'nullable|numeric|min:0|max:100',
            'right_arm_percent' => 'nullable|numeric|min:0|max:100',
            'left_arm_percent' => 'nullable|numeric|min:0|max:100',
            'right_forearm_hand_percent' => 'nullable|numeric|min:0|max:100',
            'left_forearm_hand_percent' => 'nullable|numeric|min:0|max:100',
            'right_thigh_percent' => 'nullable|numeric|min:0|max:100',
            'left_thigh_percent' => 'nullable|numeric|min:0|max:100',
            'right_leg_foot_percent' => 'nullable|numeric|min:0|max:100',
            'left_leg_foot_percent' => 'nullable|numeric|min:0|max:100',
            'genitalia_percent' => 'nullable|numeric|min:0|max:100',
            'depth_dominant' => 'nullable|string|in:superficial,partial_superficial,partial_deep,full_thickness',
            'circumferential_burns' => 'nullable|boolean',
            'circumferential_locations' => 'nullable|string',
            'escharotomy_needed' => 'nullable|boolean',
            'escharotomy_locations' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $assessment = $this->burnsService->storeAssessment($admissionId, $validated);

            return response()->json([
                'message' => 'Évaluation enregistrée.',
                'assessment' => $assessment->fresh(),
                'total_burn_surface_area' => $assessment->total_burn_surface_area,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function storeFluidResuscitation(Request $request, int $admissionId): JsonResponse
    {
        $validated = $request->validate([
            'patient_weight_kg' => 'required|numeric|min:1|max:300',
            'burn_surface_area_percent' => 'required|numeric|min:1|max:100',
            'formula_used' => 'nullable|string|in:parkland,modified_brooke,consensus',
        ]);

        try {
            $fluidResuscitation = $this->burnsService->storeFluidResuscitation($admissionId, $validated);

            return response()->json([
                'message' => 'Réanimation hydrique calculée et enregistrée.',
                'fluid_resuscitation' => $fluidResuscitation->fresh(),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function storeWoundEvolution(Request $request, int $admissionId): JsonResponse
    {
        $validated = $request->validate([
            'burn_assessment_id' => 'nullable|integer|exists:burn_assessments,id',
            'evolution_datetime' => 'nullable|date',
            'body_region' => 'required|string|max:100',
            'wound_status' => 'required|string|in:healing,stable,deteriorating,infected,grafted,closed',
            'depth_current' => 'nullable|string|in:superficial,partial_superficial,partial_deep,full_thickness',
            'wound_description' => 'nullable|string',
            'graft_planned' => 'nullable|boolean',
            'graft_planned_date' => 'nullable|date',
            'graft_type' => 'nullable|string|in:split_thickness,full_thickness,cultured_epithelial,dermal_substitute',
            'graft_donor_site' => 'nullable|string|max:100',
            'graft_completed' => 'nullable|boolean',
            'graft_completed_date' => 'nullable|date',
            'graft_outcome' => 'nullable|string|in:success,partial_failure,failure',
            'flap_planned' => 'nullable|boolean',
            'flap_type' => 'nullable|string|max:100',
            'flap_planned_date' => 'nullable|date',
            'dressing_type' => 'nullable|string|max:100',
            'dressing_instructions' => 'nullable|string',
            'dressing_change_frequency_hours' => 'nullable|integer|min:1|max:168',
            'pharmacy_order_needed' => 'nullable|boolean',
            'pharmacy_order_items' => 'nullable|string',
            'infection_signs' => 'nullable|string|max:100',
            'infection_confirmed' => 'nullable|boolean',
            'infection_organism' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $woundEvolution = $this->burnsService->storeWoundEvolution($admissionId, $validated);

        return response()->json([
            'message' => 'Évolution de plaie enregistrée.',
            'wound_evolution' => $woundEvolution->fresh(),
        ], 201);
    }

    public function calculateParkland(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'weight_kg' => 'required|numeric|min:1|max:300',
            'burn_surface_area_percent' => 'required|numeric|min:1|max:100',
            'accident_datetime' => 'required|date',
            'formula' => 'nullable|string|in:parkland,modified_brooke,consensus',
        ]);

        try {
            $calculation = $this->fluidService->calculateParkland(
                $validated['weight_kg'],
                $validated['burn_surface_area_percent'],
                \Carbon\Carbon::parse($validated['accident_datetime']),
                $validated['formula'] ?? 'parkland'
            );

            return response()->json($calculation);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getLundBrowderPercentages(Request $request): JsonResponse
    {
        $ageYears = $request->query('age', 20);

        $percentages = $this->burnsService->getLundBrowderPercentages((int) $ageYears);

        return response()->json($percentages);
    }

    public function sendPharmacyOrder(Request $request, int $woundEvolutionId): JsonResponse
    {
        $woundEvolution = WoundEvolution::findOrFail($woundEvolutionId);

        $woundEvolution->update([
            'pharmacy_order_sent' => true,
            'pharmacy_order_sent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Bon de commande envoyé à la pharmacie centrale.',
            'wound_evolution' => $woundEvolution->fresh(),
        ]);
    }
}
