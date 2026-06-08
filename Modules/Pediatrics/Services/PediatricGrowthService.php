<?php

namespace Modules\Pediatrics\Services;

use Carbon\Carbon;
use Modules\Pediatrics\Models\BirthHistory;
use Modules\Pediatrics\Models\GrowthRecord;

class PediatricGrowthService
{
    public function patientDashboard(int $patientId): array
    {
        $birthHistory = BirthHistory::where('patient_id', $patientId)
            ->latest('created_at')
            ->first();

        $growthRecords = GrowthRecord::where('patient_id', $patientId)
            ->orderBy('measurement_date')
            ->get();

        $latestGrowth = $growthRecords->last();

        $growthChartData = $this->buildGrowthChartData($growthRecords, $patientId);

        $alerts = $this->generateAlerts($patientId, $birthHistory, $latestGrowth);

        return [
            'birth_history' => $birthHistory,
            'growth_records' => $growthRecords,
            'latest_growth' => $latestGrowth,
            'growth_chart_data' => $growthChartData,
            'alerts' => $alerts,
        ];
    }

    public function storeBirthHistory(int $patientId, array $data): BirthHistory
    {
        return BirthHistory::updateOrCreate(
            ['patient_id' => $patientId, 'id' => $data['id'] ?? null],
            array_merge($data, [
                'patient_id' => $patientId,
                'practitioner_id' => auth()->id(),
            ])
        );
    }

    public function storeGrowthRecord(int $patientId, array $data): GrowthRecord
    {
        if (! empty($data['measurement_date']) && ! isset($data['age_months'])) {
            $patient = \App\Models\Patient::find($patientId);
            if ($patient && $patient->date_of_birth) {
                $measurementDate = Carbon::parse($data['measurement_date']);
                $birthDate = Carbon::parse($patient->date_of_birth);
                $data['age_months'] = $birthDate->diffInMonths($measurementDate);
            }
        }

        if (! empty($data['weight_kg']) && ! empty($data['height_cm'])) {
            $heightM = $data['height_cm'] / 100;
            $data['bmi'] = round($data['weight_kg'] / ($heightM * $heightM), 1);
        }

        $data['nutritional_status'] = $this->assessNutritionalStatus($data);

        return GrowthRecord::updateOrCreate(
            ['id' => $data['id'] ?? null],
            array_merge($data, [
                'patient_id' => $patientId,
                'practitioner_id' => auth()->id(),
            ])
        );
    }

    public function buildGrowthChartData($growthRecords, int $patientId): array
    {
        $patient = \App\Models\Patient::find($patientId);
        $sex = $patient?->sex === 'M' ? 'male' : 'female';

        $weightData = [];
        $heightData = [];
        $headCircumferenceData = [];
        $bmiData = [];

        foreach ($growthRecords as $record) {
            $ageMonths = $record->age_months;

            if ($record->weight_kg) {
                $weightData[] = [
                    'age_months' => $ageMonths,
                    'weight' => $record->weight_kg,
                    'p3' => $this->getWhoPercentile('weight', $sex, $ageMonths, 3),
                    'p15' => $this->getWhoPercentile('weight', $sex, $ageMonths, 15),
                    'p50' => $this->getWhoPercentile('weight', $sex, $ageMonths, 50),
                    'p85' => $this->getWhoPercentile('weight', $sex, $ageMonths, 85),
                    'p97' => $this->getWhoPercentile('weight', $sex, $ageMonths, 97),
                ];
            }

            if ($record->height_cm) {
                $heightData[] = [
                    'age_months' => $ageMonths,
                    'height' => $record->height_cm,
                    'p3' => $this->getWhoPercentile('height', $sex, $ageMonths, 3),
                    'p15' => $this->getWhoPercentile('height', $sex, $ageMonths, 15),
                    'p50' => $this->getWhoPercentile('height', $sex, $ageMonths, 50),
                    'p85' => $this->getWhoPercentile('height', $sex, $ageMonths, 85),
                    'p97' => $this->getWhoPercentile('height', $sex, $ageMonths, 97),
                ];
            }

            if ($record->head_circumference_cm) {
                $headCircumferenceData[] = [
                    'age_months' => $ageMonths,
                    'head_circumference' => $record->head_circumference_cm,
                    'p3' => $this->getWhoPercentile('head_circumference', $sex, $ageMonths, 3),
                    'p50' => $this->getWhoPercentile('head_circumference', $sex, $ageMonths, 50),
                    'p97' => $this->getWhoPercentile('head_circumference', $sex, $ageMonths, 97),
                ];
            }

            if ($record->bmi) {
                $bmiData[] = [
                    'age_months' => $ageMonths,
                    'bmi' => $record->bmi,
                    'p3' => $this->getWhoPercentile('bmi', $sex, $ageMonths, 3),
                    'p50' => $this->getWhoPercentile('bmi', $sex, $ageMonths, 50),
                    'p85' => $this->getWhoPercentile('bmi', $sex, $ageMonths, 85),
                    'p97' => $this->getWhoPercentile('bmi', $sex, $ageMonths, 97),
                ];
            }
        }

        return [
            'weight' => $weightData,
            'height' => $heightData,
            'head_circumference' => $headCircumferenceData,
            'bmi' => $bmiData,
            'sex' => $sex,
        ];
    }

    private function getWhoPercentile(string $type, string $sex, ?int $ageMonths, int $percentile): ?float
    {
        if ($ageMonths === null || $ageMonths < 0 || $ageMonths > 60) {
            return null;
        }

        $whoData = $this->getWhoReferenceData($type, $sex);

        if (! isset($whoData[$ageMonths])) {
            return null;
        }

        return $whoData[$ageMonths][$percentile] ?? null;
    }

    private function getWhoReferenceData(string $type, string $sex): array
    {
        $data = [
            'weight' => [
                'male' => [
                    0 => [3 => 2.5, 15 => 2.9, 50 => 3.3, 85 => 3.9, 97 => 4.4],
                    1 => [3 => 3.4, 15 => 3.9, 50 => 4.5, 85 => 5.1, 97 => 5.8],
                    2 => [3 => 4.3, 15 => 4.9, 50 => 5.6, 85 => 6.3, 97 => 7.1],
                    3 => [3 => 5.0, 15 => 5.7, 50 => 6.4, 85 => 7.2, 97 => 8.0],
                    6 => [3 => 6.4, 15 => 7.1, 50 => 7.9, 85 => 8.8, 97 => 9.8],
                    9 => [3 => 7.3, 15 => 8.1, 50 => 8.9, 85 => 9.9, 97 => 11.0],
                    12 => [3 => 7.9, 15 => 8.7, 50 => 9.6, 85 => 10.6, 97 => 11.8],
                    18 => [3 => 8.9, 15 => 9.8, 50 => 10.9, 85 => 12.0, 97 => 13.3],
                    24 => [3 => 9.9, 15 => 10.9, 50 => 12.2, 85 => 13.5, 97 => 15.0],
                    36 => [3 => 11.8, 15 => 13.0, 50 => 14.3, 85 => 15.9, 97 => 17.7],
                    48 => [3 => 13.5, 15 => 14.9, 50 => 16.3, 85 => 18.1, 97 => 20.2],
                    60 => [3 => 15.0, 15 => 16.6, 50 => 18.3, 85 => 20.3, 97 => 22.7],
                ],
                'female' => [
                    0 => [3 => 2.4, 15 => 2.8, 50 => 3.2, 85 => 3.7, 97 => 4.2],
                    1 => [3 => 3.2, 15 => 3.7, 50 => 4.2, 85 => 4.8, 97 => 5.5],
                    2 => [3 => 4.0, 15 => 4.5, 50 => 5.1, 85 => 5.8, 97 => 6.6],
                    3 => [3 => 4.6, 15 => 5.2, 50 => 5.8, 85 => 6.6, 97 => 7.5],
                    6 => [3 => 5.9, 15 => 6.5, 50 => 7.3, 85 => 8.2, 97 => 9.3],
                    9 => [3 => 6.7, 15 => 7.4, 50 => 8.2, 85 => 9.2, 97 => 10.4],
                    12 => [3 => 7.2, 15 => 8.0, 50 => 8.9, 85 => 9.9, 97 => 11.2],
                    18 => [3 => 8.2, 15 => 9.1, 50 => 10.2, 85 => 11.3, 97 => 12.7],
                    24 => [3 => 9.2, 15 => 10.2, 50 => 11.5, 85 => 12.8, 97 => 14.4],
                    36 => [3 => 11.1, 15 => 12.3, 50 => 13.9, 85 => 15.5, 97 => 17.5],
                    48 => [3 => 12.8, 15 => 14.2, 50 => 16.1, 85 => 18.0, 97 => 20.4],
                    60 => [3 => 14.3, 15 => 15.8, 50 => 18.2, 85 => 20.4, 97 => 23.2],
                ],
            ],
            'height' => [
                'male' => [
                    0 => [3 => 46.3, 15 => 48.0, 50 => 49.9, 85 => 51.8, 97 => 53.4],
                    3 => [3 => 54.7, 15 => 56.7, 50 => 59.1, 85 => 61.4, 97 => 63.5],
                    6 => [3 => 62.4, 15 => 64.6, 50 => 67.0, 85 => 69.4, 97 => 71.6],
                    12 => [3 => 70.2, 15 => 72.3, 50 => 75.0, 85 => 77.5, 97 => 79.9],
                    24 => [3 => 80.5, 15 => 83.0, 50 => 86.4, 85 => 89.6, 97 => 92.4],
                    36 => [3 => 88.7, 15 => 91.6, 50 => 95.1, 85 => 98.5, 97 => 101.5],
                    48 => [3 => 95.6, 15 => 98.8, 50 => 102.9, 85 => 106.8, 97 => 110.3],
                    60 => [3 => 101.7, 15 => 105.2, 50 => 109.4, 85 => 113.5, 97 => 117.2],
                ],
                'female' => [
                    0 => [3 => 45.6, 15 => 47.3, 50 => 49.1, 85 => 51.0, 97 => 52.7],
                    3 => [3 => 53.7, 15 => 55.6, 50 => 57.9, 85 => 60.1, 97 => 62.1],
                    6 => [3 => 61.2, 15 => 63.3, 50 => 65.7, 85 => 68.0, 97 => 70.1],
                    12 => [3 => 68.9, 15 => 71.0, 50 => 73.6, 85 => 76.2, 97 => 78.6],
                    24 => [3 => 79.3, 15 => 81.7, 50 => 85.1, 85 => 88.3, 97 => 91.2],
                    36 => [3 => 87.4, 15 => 90.3, 50 => 93.9, 85 => 97.3, 97 => 100.4],
                    48 => [3 => 94.5, 15 => 97.7, 50 => 101.7, 85 => 105.6, 97 => 109.1],
                    60 => [3 => 100.8, 15 => 104.2, 50 => 108.4, 85 => 112.4, 97 => 116.1],
                ],
            ],
            'head_circumference' => [
                'male' => [
                    0 => [3 => 32.4, 50 => 34.5, 97 => 36.9],
                    3 => [3 => 38.4, 50 => 40.5, 97 => 42.6],
                    6 => [3 => 41.5, 50 => 43.3, 97 => 45.5],
                    12 => [3 => 43.5, 50 => 45.2, 97 => 47.2],
                    24 => [3 => 45.5, 50 => 47.2, 97 => 49.2],
                    36 => [3 => 46.8, 50 => 48.5, 97 => 50.5],
                    60 => [3 => 48.5, 50 => 50.2, 97 => 52.2],
                ],
                'female' => [
                    0 => [3 => 31.7, 50 => 33.9, 97 => 36.1],
                    3 => [3 => 37.4, 50 => 39.5, 97 => 41.5],
                    6 => [3 => 40.3, 50 => 42.2, 97 => 44.2],
                    12 => [3 => 42.2, 50 => 44.0, 97 => 46.0],
                    24 => [3 => 44.2, 50 => 46.0, 97 => 48.0],
                    36 => [3 => 45.6, 50 => 47.3, 97 => 49.3],
                    60 => [3 => 47.3, 50 => 49.1, 97 => 51.1],
                ],
            ],
            'bmi' => [
                'male' => [
                    0 => [3 => 11.5, 50 => 13.4, 85 => 15.3, 97 => 17.0],
                    12 => [3 => 14.5, 50 => 16.5, 85 => 18.5, 97 => 20.5],
                    24 => [3 => 14.0, 50 => 16.0, 85 => 18.0, 97 => 20.0],
                    36 => [3 => 13.5, 50 => 15.5, 85 => 17.5, 97 => 19.5],
                    60 => [3 => 13.0, 50 => 15.0, 85 => 17.5, 97 => 20.0],
                ],
                'female' => [
                    0 => [3 => 11.2, 50 => 13.1, 85 => 15.0, 97 => 16.7],
                    12 => [3 => 14.2, 50 => 16.2, 85 => 18.2, 97 => 20.2],
                    24 => [3 => 13.7, 50 => 15.7, 85 => 17.7, 97 => 19.7],
                    36 => [3 => 13.2, 50 => 15.2, 85 => 17.2, 97 => 19.2],
                    60 => [3 => 12.8, 50 => 14.8, 85 => 17.2, 97 => 19.8],
                ],
            ],
        ];

        return $data[$type][$sex] ?? [];
    }

    private function assessNutritionalStatus(array $data): ?string
    {
        $weight = $data['weight_kg'] ?? null;
        $height = $data['height_cm'] ?? null;
        $ageMonths = $data['age_months'] ?? null;

        if (! $weight || ! $height || $ageMonths === null) {
            return null;
        }

        $heightM = $height / 100;
        $bmi = $weight / ($heightM * $heightM);

        if ($ageMonths < 24) {
            if ($bmi < 13) {
                return 'severe_wasting';
            }
            if ($bmi < 14.5) {
                return 'wasting';
            }
            if ($bmi > 18) {
                return 'overweight';
            }

            return 'normal';
        }

        if ($bmi < 14) {
            return 'severe_thinness';
        }
        if ($bmi < 16) {
            return 'thinness';
        }
        if ($bmi >= 25) {
            return 'overweight';
        }
        if ($bmi >= 30) {
            return 'obesity';
        }

        return 'normal';
    }

    private function generateAlerts(int $patientId, ?BirthHistory $birthHistory, ?GrowthRecord $latestGrowth): array
    {
        $alerts = [];

        if ($birthHistory) {
            if ($birthHistory->apgar_5min !== null && $birthHistory->apgar_5min < 7) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => 'Apgar à 5 min < 7 - Surveillance neurologique recommandée',
                ];
            }

            if ($birthHistory->birth_weight_grams && $birthHistory->birth_weight_grams < 2500) {
                $alerts[] = [
                    'type' => 'danger',
                    'message' => 'Poids de naissance < 2500g - Prématurité / RCIU',
                ];
            }

            if ($birthHistory->nicu_admission) {
                $alerts[] = [
                    'type' => 'info',
                    'message' => 'Admission en NICU - Antécédent néonatal significatif',
                ];
            }
        }

        if ($latestGrowth && $latestGrowth->nutritional_status) {
            if (in_array($latestGrowth->nutritional_status, ['severe_wasting', 'severe_thinness'])) {
                $alerts[] = [
                    'type' => 'danger',
                    'message' => 'Malnutrition sévère détectée - Prise en charge urgente',
                ];
            } elseif (in_array($latestGrowth->nutritional_status, ['wasting', 'thinness'])) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => 'Malnutrition modérée - Suivi nutritionnel requis',
                ];
            } elseif ($latestGrowth->nutritional_status === 'overweight') {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => 'Surpoids détecté - Conseil diététique recommandé',
                ];
            }
        }

        return $alerts;
    }
}
