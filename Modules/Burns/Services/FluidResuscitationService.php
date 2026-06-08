<?php

namespace Modules\Burns\Services;

use Carbon\Carbon;

class FluidResuscitationService
{
    /**
     * Calcule la réanimation hydrique selon la formule de Parkland.
     *
     * Formule de Parkland :
     * Volume total (ml) = 4 × Poids (kg) × %SCB
     *
     * Protocole d'administration :
     * - 50% du volume total dans les 8 premières heures suivant l'accident
     * - 50% restant sur les 16 heures suivantes
     *
     * @param  float  $weightKg  Poids du patient en kg
     * @param  float  $burnSurfaceAreaPercent  Surface corporelle brûlée en %
     * @param  Carbon  $accidentDatetime  Date et heure de l'accident
     * @param  string  $formula  Formule utilisée (parkland par défaut)
     * @return array  Plan de réanimation complet
     */
    public function calculateParkland(
        float $weightKg,
        float $burnSurfaceAreaPercent,
        Carbon $accidentDatetime,
        string $formula = 'parkland'
    ): array {
        if ($weightKg <= 0 || $weightKg > 300) {
            throw new \InvalidArgumentException('Poids invalide (doit être entre 0 et 300 kg)');
        }

        if ($burnSurfaceAreaPercent <= 0 || $burnSurfaceAreaPercent > 100) {
            throw new \InvalidArgumentException('Surface brûlée invalide (doit être entre 0 et 100%)');
        }

        $multiplier = match ($formula) {
            'parkland' => 4,
            'modified_brooke' => 2,
            'consensus' => 3,
            default => 4,
        };

        $totalVolume = $multiplier * $weightKg * $burnSurfaceAreaPercent;
        $first8hVolume = $totalVolume * 0.5;
        $next16hVolume = $totalVolume * 0.5;

        $first8hRate = $first8hVolume / 8;
        $next16hRate = $next16hVolume / 16;

        $resuscitationStart = $accidentDatetime;
        $first8hEnd = $resuscitationStart->copy()->addHours(8);
        $next16hEnd = $first8hEnd->copy()->addHours(16);

        $urineOutputTarget = $this->calculateUrineOutputTarget($weightKg, $burnSurfaceAreaPercent);

        $maintenanceFluid = $this->calculateMaintenanceFluid($weightKg);

        return [
            'formula_used' => $formula,
            'patient_weight_kg' => $weightKg,
            'burn_surface_area_percent' => $burnSurfaceAreaPercent,
            'multiplier' => $multiplier,
            'total_volume_ml' => round($totalVolume, 2),
            'first_8h_volume_ml' => round($first8hVolume, 2),
            'next_16h_volume_ml' => round($next16hVolume, 2),
            'first_8h_rate_ml_per_hour' => round($first8hRate, 2),
            'next_16h_rate_ml_per_hour' => round($next16hRate, 2),
            'resuscitation_start_time' => $resuscitationStart,
            'first_8h_end_time' => $first8hEnd,
            'next_16h_end_time' => $next16hEnd,
            'fluid_type' => 'ringer_lactate',
            'maintenance_fluid_ml_per_hour' => round($maintenanceFluid, 2),
            'urine_output_target_ml_per_hour' => round($urineOutputTarget, 2),
            'alerts' => $this->generateAlerts($burnSurfaceAreaPercent, $weightKg),
        ];
    }

    /**
     * Calcule l'objectif de diurèse horaire.
     *
     * Protocole standard :
     * - Brûlures thermiques : 0.5 ml/kg/heure
     * - Brûlures électriques : 1-1.5 ml/kg/heure (risque de rhabdomyolyse)
     * - Enfants : 1 ml/kg/heure
     */
    private function calculateUrineOutputTarget(float $weightKg, float $burnPercent): float
    {
        if ($burnPercent > 50) {
            return $weightKg * 1.0;
        }

        return $weightKg * 0.5;
    }

    /**
     * Calcule les fluides de maintenance selon la formule de Holliday-Segar.
     *
     * Formule :
     * - 0-10 kg : 4 ml/kg/heure
     * - 11-20 kg : 2 ml/kg/heure pour les kg au-dessus de 10
     * - >20 kg : 1 ml/kg/heure pour les kg au-dessus de 20
     */
    private function calculateMaintenanceFluid(float $weightKg): float
    {
        if ($weightKg <= 10) {
            return $weightKg * 4;
        }

        if ($weightKg <= 20) {
            return 40 + (($weightKg - 10) * 2);
        }

        return 60 + (($weightKg - 20) * 1);
    }

    /**
     * Génère des alertes cliniques basées sur les paramètres.
     */
    private function generateAlerts(float $burnPercent, float $weightKg): array
    {
        $alerts = [];

        if ($burnPercent >= 40) {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'Brûlure critique (>40% SCB) - Transfert en centre spécialisé recommandé',
                'priority' => 1,
            ];
        } elseif ($burnPercent >= 20) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Brûlure sévère (20-40% SCB) - Surveillance rapprochée requise',
                'priority' => 2,
            ];
        }

        if ($burnPercent >= 15) {
            $alerts[] = [
                'type' => 'info',
                'message' => 'Risque de syndrome compartimental abdominal - Surveiller la pression intra-abdominale',
                'priority' => 3,
            ];
        }

        if ($weightKg < 30) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Patient pédiatrique - Adapter les objectifs de diurèse (1 ml/kg/h)',
                'priority' => 2,
            ];
        }

        return $alerts;
    }

    /**
     * Calcule le statut actuel de la réanimation.
     */
    public function calculateCurrentStatus($fluidResuscitation): array
    {
        if (! $fluidResuscitation) {
            return ['status' => 'not_started'];
        }

        $now = now();
        $phase = $fluidResuscitation->current_phase;

        if ($phase === 'completed') {
            return [
                'status' => 'completed',
                'phase' => 'completed',
                'message' => 'Réanimation hydrique terminée',
            ];
        }

        $timeRemaining = $fluidResuscitation->time_remaining_in_current_phase_minutes;
        $currentRate = $fluidResuscitation->current_rate;

        $phaseLabel = $phase === 'first_8h'
            ? 'Phase 1 (8 premières heures)'
            : 'Phase 2 (16 heures suivantes)';

        return [
            'status' => 'active',
            'phase' => $phase,
            'phase_label' => $phaseLabel,
            'current_rate_ml_per_hour' => $currentRate,
            'time_remaining_minutes' => $timeRemaining,
            'time_remaining_display' => $this->formatTimeRemaining($timeRemaining),
        ];
    }

    private function formatTimeRemaining(?int $minutes): string
    {
        if ($minutes === null) {
            return '-';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$mins}min";
        }

        return "{$mins}min";
    }
}
