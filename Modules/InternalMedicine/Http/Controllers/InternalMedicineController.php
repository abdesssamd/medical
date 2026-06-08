<?php

namespace Modules\InternalMedicine\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\InternalMedicine\Models\ChronicCondition;
use Modules\InternalMedicine\Models\LabResult;
use Modules\InternalMedicine\Models\ClinicalScore;
use Modules\InternalMedicine\Services\ClinicalCalculatorService;
use Modules\InternalMedicine\Services\InternalMedicineService;

class InternalMedicineController extends Controller
{
    public function __construct(
        private readonly InternalMedicineService $internalMedicineService,
        private readonly ClinicalCalculatorService $calculator
    ) {}

    public function dashboard(int $patientId): JsonResponse
    {
        return response()->json(
            $this->internalMedicineService->patientDashboard($patientId)
        );
    }

    public function storeChronicCondition(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'icd10_code' => 'nullable|string|max:20',
            'diagnosis_name' => 'required|string|max:255',
            'discovery_date' => 'nullable|date',
            'status' => 'nullable|string|in:active,controlled,uncontrolled,resolved',
            'notes' => 'nullable|string',
        ]);

        $condition = ChronicCondition::create(array_merge($validated, [
            'patient_id' => $patientId,
            'practitioner_id' => auth()->id(),
            'status' => $validated['status'] ?? 'active',
        ]));

        return response()->json([
            'message' => __('internal_med.condition_saved'),
            'condition' => $condition->fresh(),
        ], 201);
    }

    public function updateChronicCondition(Request $request, int $conditionId): JsonResponse
    {
        $condition = ChronicCondition::findOrFail($conditionId);

        $validated = $request->validate([
            'icd10_code' => 'nullable|string|max:20',
            'diagnosis_name' => 'required|string|max:255',
            'discovery_date' => 'nullable|date',
            'status' => 'nullable|string|in:active,controlled,uncontrolled,resolved',
            'notes' => 'nullable|string',
        ]);

        $condition->update($validated);

        return response()->json([
            'message' => __('internal_med.condition_updated'),
            'condition' => $condition->fresh(),
        ]);
    }

    public function destroyChronicCondition(int $conditionId): JsonResponse
    {
        ChronicCondition::findOrFail($conditionId)->delete();

        return response()->json(['message' => __('internal_med.condition_deleted')]);
    }

    public function storeLabResult(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'consultation_id' => 'nullable|integer|exists:patient_consultations,id',
            'test_date' => 'required|date',
            'parameters' => 'required|array',
        ]);

        $result = LabResult::create([
            'patient_id' => $patientId,
            'consultation_id' => $validated['consultation_id'] ?? null,
            'test_date' => $validated['test_date'],
            'parameters' => $validated['parameters'],
            'practitioner_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => __('internal_med.lab_saved'),
            'result' => $result->fresh(),
        ], 201);
    }

    public function getLabHistory(int $patientId, string $parameter): JsonResponse
    {
        $history = $this->internalMedicineService->getLabParameterHistory($patientId, $parameter);

        return response()->json($history);
    }

    public function calculateCockcroft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'age' => 'required|numeric|min:1|max:130',
            'weight' => 'required|numeric|min:1|max:300',
            'creatinine' => 'required|numeric|min:0.1|max:50',
            'sex' => 'required|string|in:male,female',
        ]);

        $value = $this->calculator->cockcroftGault(
            $validated['age'],
            $validated['weight'],
            $validated['creatinine'],
            $validated['sex']
        );

        return response()->json(['value' => $value]);
    }

    public function calculateMdrd(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'age' => 'required|numeric|min:1|max:130',
            'creatinine' => 'required|numeric|min:0.1|max:50',
            'sex' => 'required|string|in:male,female',
            'race' => 'nullable|string|in:black,non_black',
        ]);

        $value = $this->calculator->mdrd(
            $validated['age'],
            $validated['creatinine'],
            $validated['sex'],
            $validated['race'] ?? 'non_black'
        );

        return response()->json(['value' => $value]);
    }

    public function calculateBmi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'weight' => 'required|numeric|min:1|max:300',
            'height' => 'required|numeric|min:30|max:250',
        ]);

        $bmi = $this->calculator->bmi($validated['weight'], $validated['height']);
        $bsa = $this->calculator->bodySurfaceArea($validated['weight'], $validated['height']);

        return response()->json([
            'bmi' => $bmi,
            'body_surface_area' => $bsa,
        ]);
    }

    public function calculateChads2Vasc(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'heart_failure' => 'nullable|boolean',
            'hypertension' => 'nullable|boolean',
            'age_over_75' => 'nullable|boolean',
            'diabetes' => 'nullable|boolean',
            'stroke' => 'nullable|boolean',
            'vascular_disease' => 'nullable|boolean',
            'age_65_to_74' => 'nullable|boolean',
            'sex' => 'required|string|in:male,female',
        ]);

        $result = $this->calculator->chads2Vasc(
            (bool)($validated['heart_failure'] ?? false),
            (bool)($validated['hypertension'] ?? false),
            (bool)($validated['age_over_75'] ?? false),
            (bool)($validated['diabetes'] ?? false),
            (bool)($validated['stroke'] ?? false),
            (bool)($validated['vascular_disease'] ?? false),
            (bool)($validated['age_65_to_74'] ?? false),
            $validated['sex']
        );

        return response()->json($result);
    }

    public function storeScore(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'score_type' => 'required|string|max:50',
            'calculated_value' => 'required|numeric',
            'date' => 'required|date',
            'score_data' => 'nullable|array',
        ]);

        $score = ClinicalScore::create(array_merge($validated, [
            'patient_id' => $patientId,
            'practitioner_id' => auth()->id(),
        ]));

        return response()->json([
            'message' => __('internal_med.score_saved'),
            'score' => $score->fresh(),
        ], 201);
    }
}
