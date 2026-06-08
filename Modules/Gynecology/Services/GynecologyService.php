<?php

namespace Modules\Gynecology\Services;

use Carbon\Carbon;
use Modules\Gynecology\Models\GynecologicalExam;
use Modules\Gynecology\Models\GynecologicalHistory;
use Modules\Gynecology\Models\PregnancyRecord;
use Modules\Gynecology\Models\PrenatalVisit;
use Modules\Gynecology\Models\UltrasoundBiometry;

class GynecologyService
{
    public function patientDashboard(int $patientId): array
    {
        $history = GynecologicalHistory::where('patient_id', $patientId)
            ->latest('created_at')
            ->first();

        $activePregnancy = PregnancyRecord::where('patient_id', $patientId)
            ->where('pregnancy_status', PregnancyRecord::STATUS_ACTIVE)
            ->latest('lmp_date')
            ->first();

        $pregnancyHistory = PregnancyRecord::where('patient_id', $patientId)
            ->where('pregnancy_status', '!=', PregnancyRecord::STATUS_ACTIVE)
            ->orderByDesc('lmp_date')
            ->get();

        $recentExams = GynecologicalExam::where('patient_id', $patientId)
            ->orderByDesc('exam_date')
            ->limit(10)
            ->get();

        $recentUltrasounds = UltrasoundBiometry::where('patient_id', $patientId)
            ->orderByDesc('exam_date')
            ->limit(5)
            ->get();

        $obstetricDashboard = null;
        if ($activePregnancy) {
            $obstetricDashboard = $this->buildObstetricDashboard($activePregnancy);
        }

        $alerts = $this->generateAlerts($patientId, $history, $activePregnancy);

        return [
            'gynecological_history' => $history,
            'active_pregnancy' => $activePregnancy,
            'pregnancy_history' => $pregnancyHistory,
            'recent_exams' => $recentExams,
            'recent_ultrasounds' => $recentUltrasounds,
            'obstetric_dashboard' => $obstetricDashboard,
            'alerts' => $alerts,
        ];
    }

    public function buildObstetricDashboard(PregnancyRecord $pregnancy): array
    {
        $ga = $pregnancy->calculateGestationalAge();
        $edd = $pregnancy->corrected_delivery_date ?? $pregnancy->estimated_delivery_date;
        $daysUntilDelivery = $pregnancy->days_until_delivery;

        $visits = $pregnancy->prenatalVisits()->get();
        $ultrasounds = $pregnancy->ultrasoundBiometries()->get();
        $exams = $pregnancy->gynecologicalExams()->get();

        $weightGain = $this->calculateWeightGain($visits);
        $bloodPressureTrend = $this->buildBloodPressureTrend($visits);
        $fundalHeightTrend = $this->buildFundalHeightTrend($visits);

        $requiredExams = $this->getRequiredExamsByTrimester($ga['trimester']);
        $completedExams = $this->checkCompletedExams($pregnancy, $requiredExams);

        $nextMilestones = $this->getNextMilestones($ga['weeks'] ?? 0);

        return [
            'gestational_age' => $ga,
            'gestational_age_display' => $pregnancy->gestational_age_display,
            'estimated_delivery_date' => $edd,
            'days_until_delivery' => $daysUntilDelivery,
            'trimester' => $ga['trimester'],
            'trimester_label' => $this->trimesterLabel($ga['trimester']),
            'risk_level' => $pregnancy->risk_level,
            'visits' => $visits,
            'visit_count' => $visits->count(),
            'ultrasounds' => $ultrasounds,
            'exams' => $exams,
            'weight_gain' => $weightGain,
            'blood_pressure_trend' => $bloodPressureTrend,
            'fundal_height_trend' => $fundalHeightTrend,
            'required_exams' => $requiredExams,
            'completed_exams' => $completedExams,
            'next_milestones' => $nextMilestones,
            'serology_summary' => $this->buildSerologySummary($pregnancy),
        ];
    }

    public function storeGynecologicalHistory(int $patientId, array $data): GynecologicalHistory
    {
        return GynecologicalHistory::updateOrCreate(
            ['patient_id' => $patientId, 'id' => $data['id'] ?? null],
            array_merge($data, [
                'patient_id' => $patientId,
                'practitioner_id' => auth()->id(),
            ])
        );
    }

    public function storePregnancyRecord(int $patientId, array $data): PregnancyRecord
    {
        $lmp = isset($data['lmp_date']) ? Carbon::parse($data['lmp_date']) : null;

        if ($lmp && empty($data['estimated_delivery_date'])) {
            $data['estimated_delivery_date'] = PregnancyRecord::calculateEDD($lmp);
        }

        if ($lmp) {
            $ga = (new PregnancyRecord(['lmp_date' => $lmp]))->calculateGestationalAge();
            $data['gestational_age_weeks'] = $ga['weeks'];
            $data['gestational_age_days'] = $ga['days'];
            $data['trimester'] = $ga['trimester'];
        }

        return PregnancyRecord::updateOrCreate(
            ['id' => $data['id'] ?? null],
            array_merge($data, [
                'patient_id' => $patientId,
                'practitioner_id' => auth()->id(),
            ])
        );
    }

    public function storePrenatalVisit(int $pregnancyRecordId, array $data): PrenatalVisit
    {
        $pregnancy = PregnancyRecord::findOrFail($pregnancyRecordId);

        if (! empty($data['visit_date']) && $pregnancy->lmp_date) {
            $visitDate = Carbon::parse($data['visit_date']);
            $diffInDays = $pregnancy->lmp_date->diffInDays($visitDate);
            $data['gestational_weeks_at_visit'] = intdiv($diffInDays, 7);
            $data['gestational_days_at_visit'] = $diffInDays % 7;
        }

        $data['visit_number'] = $data['visit_number']
            ?? ($pregnancy->prenatalVisits()->count() + 1);

        return PrenatalVisit::create(array_merge($data, [
            'pregnancy_record_id' => $pregnancyRecordId,
            'practitioner_id' => auth()->id(),
        ]));
    }

    public function storeGynecologicalExam(int $patientId, array $data): GynecologicalExam
    {
        return GynecologicalExam::create(array_merge($data, [
            'patient_id' => $patientId,
            'practitioner_id' => auth()->id(),
        ]));
    }

    public function storeUltrasoundBiometry(int $patientId, array $data): UltrasoundBiometry
    {
        if (! empty($data['bip_mm']) && ! empty($data['hc_mm'])
            && ! empty($data['ac_mm']) && ! empty($data['fl_mm'])) {
            $data['efw_grams'] = $this->calculateHadlockEFW(
                (float) $data['bip_mm'],
                (float) $data['hc_mm'],
                (float) $data['ac_mm'],
                (float) $data['fl_mm']
            );
        }

        return UltrasoundBiometry::create(array_merge($data, [
            'patient_id' => $patientId,
            'practitioner_id' => auth()->id(),
        ]));
    }

    public function calculateHadlockEFW(float $bip, float $hc, float $ac, float $fl): int
    {
        $logEfw = 1.326
            - 0.00326 * $ac * $fl
            + 0.0107 * $hc
            + 0.0438 * $ac
            + 0.158 * $fl;

        return (int) round(pow(10, $logEfw));
    }

    private function calculateWeightGain($visits): array
    {
        if ($visits->isEmpty()) {
            return ['current' => null, 'total' => null, 'trend' => []];
        }

        $firstWeight = $visits->first()->weight_kg;
        $lastWeight = $visits->last()->weight_kg;

        $trend = $visits->filter(fn ($v) => $v->weight_kg !== null)
            ->map(fn ($v) => [
                'date' => $v->visit_date->format('d/m/Y'),
                'weight' => $v->weight_kg,
                'gain' => $firstWeight ? round($v->weight_kg - $firstWeight, 1) : null,
            ])
            ->values()
            ->all();

        return [
            'current' => $lastWeight,
            'total' => $firstWeight && $lastWeight ? round($lastWeight - $firstWeight, 1) : null,
            'trend' => $trend,
        ];
    }

    private function buildBloodPressureTrend($visits): array
    {
        return $visits->filter(fn ($v) => $v->blood_pressure_systolic && $v->blood_pressure_diastolic)
            ->map(fn ($v) => [
                'date' => $v->visit_date->format('d/m/Y'),
                'systolic' => $v->blood_pressure_systolic,
                'diastolic' => $v->blood_pressure_diastolic,
                'alert' => $v->blood_pressure_systolic >= 140 || $v->blood_pressure_diastolic >= 90,
            ])
            ->values()
            ->all();
    }

    private function buildFundalHeightTrend($visits): array
    {
        return $visits->filter(fn ($v) => $v->fundal_height_cm !== null)
            ->map(fn ($v) => [
                'date' => $v->visit_date->format('d/m/Y'),
                'weeks' => $v->gestational_weeks_at_visit,
                'height_cm' => $v->fundal_height_cm,
            ])
            ->values()
            ->all();
    }

    private function getRequiredExamsByTrimester(?string $trimester): array
    {
        $common = [
            'blood_group_rh' => 'Groupe sanguin + Rh',
            'rai' => 'RAI (Recherche d\'Agglutinines Irrégulières)',
            'serology_hiv' => 'Sérologie VIH',
            'serology_hepatitis_b' => 'Sérologie Hépatite B',
            'serology_syphilis' => 'Sérologie Syphilis (TPHA/VDRL)',
            'serology_toxoplasmosis' => 'Sérologie Toxoplasmose',
            'serology_rubella' => 'Sérologie Rubéole',
            'nfs' => 'NFS (Numération Formule Sanguine)',
            'urine_culture' => 'ECBU (Examen Cytobactériologique des Urines)',
        ];

        $byTrimester = [
            'first' => array_merge($common, [
                'ultrasound_t1' => 'Échographie T1 (Datation + T21)',
                'fcv' => 'FCV si pas à jour',
            ]),
            'second' => array_merge($common, [
                'ultrasound_t2' => 'Échographie T2 (Morphologique)',
                'og_sullivan' => 'Test O\'Sullivan (HGPO)',
                'serology_toxoplasmosis_monthly' => 'Séro Toxo mensuelle si négative',
            ]),
            'third' => array_merge($common, [
                'ultrasound_t3' => 'Échographie T3 (Croissance)',
                'streptococcus_b' => 'Prélèvement vaginal Streptocoque B (34-38 SA)',
                'serology_toxoplasmosis_monthly' => 'Séro Toxo mensuelle si négative',
            ]),
        ];

        return $byTrimester[$trimester] ?? $common;
    }

    private function checkCompletedExams(PregnancyRecord $pregnancy, array $required): array
    {
        $completed = [];

        foreach ($required as $key => $label) {
            $isCompleted = match ($key) {
                'blood_group_rh' => ! empty($pregnancy->blood_group_rh),
                'rai' => ! empty($pregnancy->rai_result),
                'serology_hiv' => ! empty($pregnancy->serology_hiv),
                'serology_hepatitis_b' => ! empty($pregnancy->serology_hepatitis_b),
                'serology_hepatitis_c' => ! empty($pregnancy->serology_hepatitis_c),
                'serology_syphilis' => ! empty($pregnancy->serology_syphilis),
                'serology_toxoplasmosis' => ! empty($pregnancy->serology_toxoplasmosis),
                'serology_rubella' => ! empty($pregnancy->serology_rubella),
                'og_sullivan' => ! empty($pregnancy->og_sullivan_result),
                'streptococcus_b' => ! empty($pregnancy->streptococcus_b_result),
                'ultrasound_t1', 'ultrasound_t2', 'ultrasound_t3' => $pregnancy->ultrasoundBiometries()
                    ->where('trimester', match ($key) {
                        'ultrasound_t1' => 1,
                        'ultrasound_t2' => 2,
                        'ultrasound_t3' => 3,
                    })
                    ->exists(),
                default => false,
            };

            $completed[$key] = [
                'label' => $label,
                'completed' => $isCompleted,
            ];
        }

        return $completed;
    }

    private function getNextMilestones(int $currentWeeks): array
    {
        $milestones = [
            12 => ['label' => 'Écho T1 + Dépistage T21', 'week' => 12],
            14 => ['label' => 'Déclaration de grossesse', 'week' => 14],
            16 => ['label' => '2ème visite prénatale', 'week' => 16],
            22 => ['label' => 'Écho morphologique T2', 'week' => 22],
            24 => ['label' => 'Viabilité fœtale', 'week' => 24],
            26 => ['label' => 'Test O\'Sullivan (24-28 SA)', 'week' => 26],
            28 => ['label' => 'Début 3ème trimestre', 'week' => 28],
            32 => ['label' => 'Écho T3 croissance', 'week' => 32],
            34 => ['label' => 'Prélèvement Strepto B', 'week' => 34],
            37 => ['label' => 'Terme atteint', 'week' => 37],
            39 => ['label' => 'Terme théorique', 'week' => 39],
            41 => ['label' => 'Terme dépassé', 'week' => 41],
        ];

        return collect($milestones)
            ->filter(fn ($m) => $m['week'] > $currentWeeks)
            ->take(4)
            ->values()
            ->all();
    }

    private function buildSerologySummary(PregnancyRecord $pregnancy): array
    {
        return [
            'hiv' => $pregnancy->serology_hiv ?? 'Non fait',
            'hepatitis_b' => $pregnancy->serology_hepatitis_b ?? 'Non fait',
            'hepatitis_c' => $pregnancy->serology_hepatitis_c ?? 'Non fait',
            'syphilis' => $pregnancy->serology_syphilis ?? 'Non fait',
            'toxoplasmosis' => $pregnancy->serology_toxoplasmosis ?? 'Non fait',
            'rubella' => $pregnancy->serology_rubella ?? 'Non fait',
            'cmv' => $pregnancy->serology_cmV ?? 'Non fait',
        ];
    }

    private function generateAlerts(int $patientId, ?GynecologicalHistory $history, ?PregnancyRecord $pregnancy): array
    {
        $alerts = [];

        if ($history) {
            $cancers = $history->family_history_cancers ?? [];
            if (in_array('breast', $cancers) || in_array('ovarian', $cancers)) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => 'Antécédents familiaux de cancer gynécologique - Surveillance renforcée recommandée',
                    'icon' => 'alert-triangle',
                ];
            }

            if ($history->last_fcv_date) {
                $monthsSinceFcv = $history->last_fcv_date->diffInMonths(now());
                if ($monthsSinceFcv > 36) {
                    $alerts[] = [
                        'type' => 'info',
                        'message' => "Dernier FCV il y a {$monthsSinceFcv} mois - Un nouveau frottis est recommandé",
                        'icon' => 'calendar',
                    ];
                }
            }
        }

        if ($pregnancy && $pregnancy->pregnancy_status === PregnancyRecord::STATUS_ACTIVE) {
            if ($pregnancy->risk_level === PregnancyRecord::RISK_HIGH) {
                $alerts[] = [
                    'type' => 'danger',
                    'message' => 'Grossesse à haut risque - Suivi spécialisé requis',
                    'icon' => 'alert-circle',
                ];
            }

            $ga = $pregnancy->calculateGestationalAge();
            if (($ga['weeks'] ?? 0) >= 41) {
                $alerts[] = [
                    'type' => 'danger',
                    'message' => 'Terme dépassé (≥ 41 SA) - Surveillance renforcée',
                    'icon' => 'clock',
                ];
            }
        }

        return $alerts;
    }

    private function trimesterLabel(?string $trimester): string
    {
        return match ($trimester) {
            'first' => '1er trimestre',
            'second' => '2ème trimestre',
            'third' => '3ème trimestre',
            default => '-',
        };
    }
}
