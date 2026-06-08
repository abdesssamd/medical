<?php

namespace Modules\InternalMedicine\Services;

use Modules\InternalMedicine\Models\ChronicCondition;
use Modules\InternalMedicine\Models\LabResult;
use Modules\InternalMedicine\Models\ClinicalScore;

class InternalMedicineService
{
    public function __construct(
        private readonly ClinicalCalculatorService $calculator
    ) {}

    public function patientDashboard(int $patientId): array
    {
        $chronicConditions = ChronicCondition::where('patient_id', $patientId)
            ->orderBy('discovery_date', 'desc')
            ->get();

        $labResults = LabResult::where('patient_id', $patientId)
            ->orderBy('test_date', 'desc')
            ->limit(50)
            ->get();

        $clinicalScores = ClinicalScore::where('patient_id', $patientId)
            ->orderBy('date', 'desc')
            ->limit(20)
            ->get();

        $latestLabResult = $labResults->first();

        $scoresSummary = $this->calculateCurrentScores($patientId, $latestLabResult);

        $labChartData = $this->buildLabChartData($patientId);

        return [
            'chronic_conditions' => $chronicConditions,
            'lab_results' => $labResults,
            'clinical_scores' => $clinicalScores,
            'scores_summary' => $scoresSummary,
            'lab_chart_data' => $labChartData,
        ];
    }

    private function calculateCurrentScores(int $patientId, ?LabResult $latestLab): array
    {
        $patient = \App\Models\Patient::find($patientId);

        if (! $patient || ! $latestLab) {
            return [];
        }

        $params = $latestLab->parameters ?? [];
        $age = $patient->date_of_birth ? $patient->date_of_birth->age : 40;
        $weight = $patient->weight_kg ?? 70;
        $height = $patient->height_cm ?? 170;
        $sex = $patient->gender ?? 'male';
        $creatinine = $params['creatinine'] ?? 1.0;

        $bmi = $this->calculator->bmi($weight, $height);
        $bsa = $this->calculator->bodySurfaceArea($weight, $height);
        $cockcroft = $this->calculator->cockcroftGault($age, $weight, (float)$creatinine, $sex);
        $mdrd = $this->calculator->mdrd($age, (float)$creatinine, $sex);

        return [
            'bmi' => $bmi,
            'body_surface_area' => $bsa,
            'cockcroft_gault' => $cockcroft,
            'mdrd' => $mdrd,
        ];
    }

    private function buildLabChartData(int $patientId): array
    {
        $results = LabResult::where('patient_id', $patientId)
            ->orderBy('test_date', 'asc')
            ->get();

        $parameters = [];

        foreach ($results as $result) {
            $date = $result->test_date->format('Y-m-d');

            foreach (($result->parameters ?? []) as $key => $value) {
                if (! isset($parameters[$key])) {
                    $parameters[$key] = [];
                }

                $parameters[$key][] = [
                    'date' => $date,
                    'value' => (float) $value,
                ];
            }
        }

        return $parameters;
    }

    public function getLabParameterHistory(int $patientId, string $parameter): array
    {
        return LabResult::where('patient_id', $patientId)
            ->whereNotNull('parameters->' . $parameter)
            ->orderBy('test_date', 'asc')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->test_date->format('Y-m-d'),
                'value' => (float) ($r->parameters[$parameter] ?? 0),
            ])
            ->values()
            ->toArray();
    }
}
