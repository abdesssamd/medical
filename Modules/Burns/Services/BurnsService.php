<?php

namespace Modules\Burns\Services;

use Carbon\Carbon;
use Modules\Burns\Models\BurnAdmission;
use Modules\Burns\Models\BurnAssessment;
use Modules\Burns\Models\FluidResuscitation;
use Modules\Burns\Models\WoundEvolution;

class BurnsService
{
    public function __construct(
        private readonly FluidResuscitationService $fluidService
    ) {}

    public function patientDashboard(int $patientId): array
    {
        $admission = BurnAdmission::where('patient_id', $patientId)
            ->where('admission_status', 'active')
            ->latest('admission_datetime')
            ->first();

        if (! $admission) {
            $admission = $this->ensureActiveAdmission($patientId);
        }

        $latestAssessment = $admission->latestAssessment;
        $activeFluidResuscitation = $admission->activeFluidResuscitation;
        $fluidStatus = $this->fluidService->calculateCurrentStatus($activeFluidResuscitation);
        $woundEvolutions = $admission->woundEvolutions()->limit(20)->get();

        $alerts = $this->generateDashboardAlerts($admission, $latestAssessment, $activeFluidResuscitation);

        return [
            'admission' => $admission,
            'latest_assessment' => $latestAssessment,
            'fluid_resuscitation' => $activeFluidResuscitation,
            'fluid_status' => $fluidStatus,
            'wound_evolutions' => $woundEvolutions,
            'alerts' => $alerts,
        ];
    }

    public function storeAdmission(int $patientId, array $data): BurnAdmission
    {
        return BurnAdmission::create(array_merge($data, [
            'patient_id' => $patientId,
            'practitioner_id' => auth()->id(),
            'admission_datetime' => $data['admission_datetime'] ?? now(),
        ]));
    }

    public function storeAssessment(int $admissionId, array $data): BurnAssessment
    {
        $totalBurnSurfaceArea = $this->calculateTotalBurnSurfaceArea($data);

        if ($totalBurnSurfaceArea > 100) {
            throw new \InvalidArgumentException('La surface corporelle brûlée totale ne peut pas dépasser 100%');
        }

        return BurnAssessment::create(array_merge($data, [
            'burn_admission_id' => $admissionId,
            'practitioner_id' => auth()->id(),
            'total_burn_surface_area' => $totalBurnSurfaceArea,
            'assessment_datetime' => $data['assessment_datetime'] ?? now(),
        ]));
    }

    public function storeFluidResuscitation(int $admissionId, array $data): FluidResuscitation
    {
        $admission = BurnAdmission::findOrFail($admissionId);

        $calculation = $this->fluidService->calculateParkland(
            $data['patient_weight_kg'],
            $data['burn_surface_area_percent'],
            $admission->accident_datetime,
            $data['formula_used'] ?? 'parkland'
        );

        return FluidResuscitation::create(array_merge($calculation, [
            'burn_admission_id' => $admissionId,
            'practitioner_id' => auth()->id(),
            'status' => 'active',
        ]));
    }

    public function storeWoundEvolution(int $admissionId, array $data): WoundEvolution
    {
        return WoundEvolution::create(array_merge($data, [
            'burn_admission_id' => $admissionId,
            'practitioner_id' => auth()->id(),
            'evolution_datetime' => $data['evolution_datetime'] ?? now(),
        ]));
    }

    private function calculateTotalBurnSurfaceArea(array $data): float
    {
        $regions = [
            'head_face_percent',
            'neck_percent',
            'anterior_trunk_percent',
            'posterior_trunk_percent',
            'right_arm_percent',
            'left_arm_percent',
            'right_forearm_hand_percent',
            'left_forearm_hand_percent',
            'right_thigh_percent',
            'left_thigh_percent',
            'right_leg_foot_percent',
            'left_leg_foot_percent',
            'genitalia_percent',
        ];

        $total = 0;

        foreach ($regions as $region) {
            if (isset($data[$region]) && is_numeric($data[$region])) {
                $total += (float) $data[$region];
            }
        }

        return $total;
    }

    private function generateDashboardAlerts(
        BurnAdmission $admission,
        ?BurnAssessment $assessment,
        ?FluidResuscitation $fluidResuscitation
    ): array {
        $alerts = [];

        if ($admission->inhalation_injury_confirmed) {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'Lésion d\'inhalation confirmée - Intubation précoce recommandée',
                'icon' => 'alert-circle',
            ];
        } elseif ($admission->inhalation_injury_suspected) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Lésion d\'inhalation suspectée - Surveillance respiratoire rapprochée',
                'icon' => 'alert-triangle',
            ];
        }

        if ($assessment && $assessment->circumferential_burns) {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'Brûlures circonférentielles détectées - Évaluer la nécessité d\'escarotomie',
                'icon' => 'alert-circle',
            ];
        }

        if ($assessment && $assessment->total_burn_surface_area >= 40) {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'SCB ≥ 40% - Risque élevé de défaillance multiviscérale',
                'icon' => 'alert-circle',
            ];
        }

        if ($fluidResuscitation && $fluidResuscitation->current_phase === 'first_8h') {
            $timeRemaining = $fluidResuscitation->time_remaining_in_current_phase_minutes;
            if ($timeRemaining !== null && $timeRemaining < 60) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => 'Phase 1 se termine dans moins d\'1 heure - Préparer la transition',
                    'icon' => 'clock',
                ];
            }
        }

        return $alerts;
    }

    public function getLundBrowderPercentages(int $ageYears): array
    {
        if ($ageYears < 1) {
            return [
                'head_face' => 19,
                'neck' => 2,
                'anterior_trunk' => 13,
                'posterior_trunk' => 13,
                'right_arm' => 4,
                'left_arm' => 4,
                'right_forearm_hand' => 5.5,
                'left_forearm_hand' => 5.5,
                'right_thigh' => 5.5,
                'left_thigh' => 5.5,
                'right_leg_foot' => 5,
                'left_leg_foot' => 5,
                'genitalia' => 1,
            ];
        }

        if ($ageYears < 5) {
            return [
                'head_face' => 17,
                'neck' => 2,
                'anterior_trunk' => 13,
                'posterior_trunk' => 13,
                'right_arm' => 4,
                'left_arm' => 4,
                'right_forearm_hand' => 5.5,
                'left_forearm_hand' => 5.5,
                'right_thigh' => 6.5,
                'left_thigh' => 6.5,
                'right_leg_foot' => 5.5,
                'left_leg_foot' => 5.5,
                'genitalia' => 1,
            ];
        }

        if ($ageYears < 10) {
            return [
                'head_face' => 13,
                'neck' => 2,
                'anterior_trunk' => 13,
                'posterior_trunk' => 13,
                'right_arm' => 4,
                'left_arm' => 4,
                'right_forearm_hand' => 5.5,
                'left_forearm_hand' => 5.5,
                'right_thigh' => 8,
                'left_thigh' => 8,
                'right_leg_foot' => 6.5,
                'left_leg_foot' => 6.5,
                'genitalia' => 1,
            ];
        }

        if ($ageYears < 15) {
            return [
                'head_face' => 11,
                'neck' => 2,
                'anterior_trunk' => 13,
                'posterior_trunk' => 13,
                'right_arm' => 4,
                'left_arm' => 4,
                'right_forearm_hand' => 5.5,
                'left_forearm_hand' => 5.5,
                'right_thigh' => 8.5,
                'left_thigh' => 8.5,
                'right_leg_foot' => 7,
                'left_leg_foot' => 7,
                'genitalia' => 1,
            ];
        }

        return [
            'head_face' => 9,
            'neck' => 2,
            'anterior_trunk' => 13,
            'posterior_trunk' => 13,
            'right_arm' => 4,
            'left_arm' => 4,
            'right_forearm_hand' => 5.5,
            'left_forearm_hand' => 5.5,
            'right_thigh' => 9.5,
            'left_thigh' => 9.5,
            'right_leg_foot' => 7,
            'left_leg_foot' => 7,
            'genitalia' => 1,
        ];
    }

    private function ensureActiveAdmission(int $patientId): BurnAdmission
    {
        return BurnAdmission::create([
            'patient_id' => $patientId,
            'practitioner_id' => auth()->id(),
            'burn_type' => 'thermal',
            'accident_datetime' => now(),
            'admission_datetime' => now(),
            'admission_status' => 'active',
        ]);
    }
}
