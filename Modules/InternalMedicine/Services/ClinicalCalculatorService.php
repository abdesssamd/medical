<?php

namespace Modules\InternalMedicine\Services;

class ClinicalCalculatorService
{
    public function cockcroftGault(
        float $age,
        float $weightKg,
        float $creatinineMgdl,
        string $sex = 'male'
    ): float {
        $crCl = ((140 - $age) * $weightKg) / (72 * $creatinineMgdl);

        if ($sex === 'female') {
            $crCl *= 0.85;
        }

        return round($crCl, 1);
    }

    public function mdrd(
        float $age,
        float $creatinineMgdl,
        string $sex = 'male',
        string $race = 'non_black'
    ): float {
        $gfr = 175 * pow($creatinineMgdl, -1.154) * pow($age, -0.203);

        if ($sex === 'female') {
            $gfr *= 0.742;
        }

        if ($race === 'black') {
            $gfr *= 1.212;
        }

        return round($gfr, 1);
    }

    public function bmi(float $weightKg, float $heightCm): float
    {
        if ($heightCm <= 0) {
            return 0;
        }

        $heightM = $heightCm / 100;

        return round($weightKg / ($heightM * $heightM), 1);
    }

    public function bodySurfaceArea(float $weightKg, float $heightCm): float
    {
        return round(sqrt(($heightCm * $weightKg) / 3600), 2);
    }

    public function chads2Vasc(
        bool $heartFailure,
        bool $hypertension,
        bool $ageOver75,
        bool $diabetes,
        bool $stroke,
        bool $vascularDisease,
        bool $age65to74,
        string $sex
    ): array {
        $score = 0;
        $details = [];

        if ($heartFailure) { $score += 1; $details[] = 'Insuffisance cardiaque (+1)'; }
        if ($hypertension) { $score += 1; $details[] = 'Hypertension (+1)'; }
        if ($ageOver75) { $score += 2; $details[] = 'Age ≥ 75 ans (+2)'; }
        if ($diabetes) { $score += 1; $details[] = 'Diabète (+1)'; }
        if ($stroke) { $score += 2; $details[] = 'AVC/AIT (+2)'; }
        if ($vascularDisease) { $score += 1; $details[] = 'Maladie vasculaire (+1)'; }
        if ($age65to74) { $score += 1; $details[] = 'Age 65-74 ans (+1)'; }
        if ($sex === 'female') { $score += 1; $details[] = 'Sexe féminin (+1)'; }

        $risk = $score >= 3 ? 'high' : ($score >= 2 ? 'moderate' : 'low');

        return [
            'score' => $score,
            'details' => $details,
            'risk' => $risk,
            'anticoagulation' => $score >= 2 ? 'recommended' : 'not_indicated',
        ];
    }
}
