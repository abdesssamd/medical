<?php

namespace Modules\Gynecology\Services;

use Carbon\Carbon;

class PregnancyCalculatorService
{
    public static function calculateEDD(Carbon $lmp): Carbon
    {
        return $lmp->copy()->addDays(280);
    }

    public static function calculateEDDByNaegele(Carbon $lmp): Carbon
    {
        return $lmp->copy()->addDays(7)->addMonths(9);
    }

    public static function estimateConceptionDate(Carbon $lmp): Carbon
    {
        return $lmp->copy()->addDays(14);
    }

    public static function gestationalAge(Carbon $lmp, ?Carbon $referenceDate = null): array
    {
        $reference = $referenceDate ?? Carbon::now();

        if ($lmp->isFuture()) {
            return [
                'weeks' => null,
                'days' => null,
                'total_days' => 0,
                'trimester' => null,
                'display' => 'DDR dans le futur',
                'is_valid' => false,
            ];
        }

        $diffInDays = $lmp->diffInDays($reference);
        $weeks = intdiv($diffInDays, 7);
        $days = $diffInDays % 7;

        $trimester = match (true) {
            $weeks < 14 => 'first',
            $weeks < 28 => 'second',
            default => 'third',
        };

        $trimesterLabel = match ($trimester) {
            'first' => '1er trimestre',
            'second' => '2ème trimestre',
            'third' => '3ème trimestre',
            default => '-',
        };

        return [
            'weeks' => $weeks,
            'days' => $days,
            'total_days' => $diffInDays,
            'trimester' => $trimester,
            'trimester_label' => $trimesterLabel,
            'display' => "{$weeks} SA + {$days} j",
            'is_valid' => true,
        ];
    }

    public static function daysUntilDelivery(Carbon $edd, ?Carbon $referenceDate = null): int
    {
        $reference = $referenceDate ?? Carbon::now();

        return $reference->diffInDays($edd, false);
    }

    public static function pregnancyMilestones(int $currentWeeks): array
    {
        $milestones = [
            12 => ['label' => 'Écho T1 + Dépistage T21', 'week' => 12, 'category' => 'exam'],
            14 => ['label' => 'Déclaration de grossesse', 'week' => 14, 'category' => 'admin'],
            16 => ['label' => '2ème visite prénatale', 'week' => 16, 'category' => 'visit'],
            22 => ['label' => 'Écho morphologique T2', 'week' => 22, 'category' => 'exam'],
            24 => ['label' => 'Viabilité fœtale', 'week' => 24, 'category' => 'milestone'],
            26 => ['label' => "Test O'Sullivan (24-28 SA)", 'week' => 26, 'category' => 'exam'],
            28 => ['label' => 'Début 3ème trimestre', 'week' => 28, 'category' => 'milestone'],
            32 => ['label' => 'Écho T3 croissance', 'week' => 32, 'category' => 'exam'],
            34 => ['label' => 'Prélèvement Strepto B', 'week' => 34, 'category' => 'exam'],
            37 => ['label' => 'Terme atteint', 'week' => 37, 'category' => 'milestone'],
            39 => ['label' => 'Terme théorique', 'week' => 39, 'category' => 'milestone'],
            41 => ['label' => 'Terme dépassé', 'week' => 41, 'category' => 'alert'],
        ];

        return collect($milestones)
            ->filter(fn ($m) => $m['week'] > $currentWeeks)
            ->take(5)
            ->values()
            ->all();
    }

    public static function pregnancyProgressPercent(int $weeks): int
    {
        return min(100, (int) round($weeks / 42 * 100));
    }

    public static function riskAssessment(array $riskFactors): string
    {
        $highRiskFactors = ['preeclampsia', 'gestational_diabetes', 'placenta_previa', 'multiple_pregnancy', 'previous_cesarean'];
        $moderateRiskFactors = ['age_over_35', 'bmi_over_30', 'smoking', 'hypertension'];

        $hasHigh = collect($riskFactors)->intersect($highRiskFactors)->isNotEmpty();
        $hasModerate = collect($riskFactors)->intersect($moderateRiskFactors)->isNotEmpty();

        if ($hasHigh) {
            return 'high';
        }

        if ($hasModerate) {
            return 'moderate';
        }

        return 'low';
    }

    public static function fullCalculation(Carbon $lmp, ?Carbon $referenceDate = null): array
    {
        $edd = self::calculateEDD($lmp);
        $conception = self::estimateConceptionDate($lmp);
        $ga = self::gestationalAge($lmp, $referenceDate);
        $daysUntil = self::daysUntilDelivery($edd, $referenceDate);
        $milestones = self::pregnancyMilestones($ga['weeks'] ?? 0);
        $progress = self::pregnancyProgressPercent($ga['weeks'] ?? 0);

        return [
            'lmp' => $lmp,
            'edd' => $edd,
            'conception_date' => $conception,
            'gestational_age' => $ga,
            'days_until_delivery' => $daysUntil,
            'milestones' => $milestones,
            'progress_percent' => $progress,
        ];
    }
}
