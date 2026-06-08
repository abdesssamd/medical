<?php

namespace Modules\Gynecology\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Gynecology\Models\GynecologicalExam;
use Modules\Gynecology\Models\GynecologicalHistory;
use Modules\Gynecology\Models\PregnancyRecord;
use Modules\Gynecology\Models\PrenatalVisit;
use Modules\Gynecology\Models\UltrasoundBiometry;
use Modules\Gynecology\Services\GynecologyService;

class GynecologyController extends Controller
{
    public function __construct(
        private readonly GynecologyService $gynecologyService,
    ) {}

    public function dashboard(int $patientId): JsonResponse
    {
        $data = $this->gynecologyService->patientDashboard($patientId);

        return response()->json($data);
    }

    public function storeGynecologicalHistory(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:gynecological_histories,id',
            'gestity' => 'required|integer|min:0|max:30',
            'parity' => 'required|integer|min:0|max:30',
            'abortions' => 'required|integer|min:0|max:30',
            'living_children' => 'required|integer|min:0|max:30',
            'cesarean_sections' => 'nullable|integer|min:0|max:30',
            'ectopic_pregnancies' => 'nullable|integer|min:0|max:10',
            'menarche_age' => 'nullable|integer|min:8|max:20',
            'menopause_age' => 'nullable|integer|min:30|max:65',
            'cycle_duration_days' => 'nullable|integer|min:15|max:60',
            'menstruation_duration_days' => 'nullable|integer|min:1|max:15',
            'cycle_regularity' => 'nullable|string|in:regular,irregular,amenorrhea',
            'contraception_method' => 'nullable|string|max:100',
            'last_menstrual_period' => 'nullable|date',
            'last_fcv_date' => 'nullable|date',
            'last_fcv_result' => 'nullable|string|max:100',
            'family_history_cancers' => 'nullable|array',
            'family_history_cancers.*' => 'string',
            'gynecological_conditions' => 'nullable|array',
            'gynecological_conditions.*' => 'string',
            'obstetric_complications_history' => 'nullable|array',
            'obstetric_complications_history.*' => 'string',
            'notes' => 'nullable|string|max:2000',
        ]);

        $history = $this->gynecologyService->storeGynecologicalHistory($patientId, $validated);

        return response()->json([
            'message' => 'Antécédents gynécologiques enregistrés.',
            'history' => $history->fresh(),
        ], 201);
    }

    public function storePregnancyRecord(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:pregnancy_records,id',
            'gynecological_history_id' => 'nullable|integer|exists:gynecological_histories,id',
            'pregnancy_number' => 'nullable|string|max:10',
            'lmp_date' => 'nullable|date',
            'estimated_delivery_date' => 'nullable|date',
            'corrected_delivery_date' => 'nullable|date',
            'pregnancy_status' => 'nullable|string|in:active,delivered,missed,terminated',
            'risk_level' => 'nullable|string|in:low,moderate,high',
            'risk_factors' => 'nullable|array',
            'risk_factors.*' => 'string',
            'blood_type' => 'nullable|string|max:10',
            'rh_factor' => 'nullable|string|in:positive,negative',
            'partner_blood_type' => 'nullable|string|max:10',
            'partner_rh_factor' => 'nullable|string|in:positive,negative',
            'serology_hiv' => 'nullable|string|max:20',
            'serology_hepatitis_b' => 'nullable|string|max:20',
            'serology_hepatitis_c' => 'nullable|string|max:20',
            'serology_syphilis' => 'nullable|string|max:20',
            'serology_toxoplasmosis' => 'nullable|string|max:20',
            'serology_rubella' => 'nullable|string|max:20',
            'serology_cmV' => 'nullable|string|max:20',
            'blood_group_rh' => 'nullable|string|max:20',
            'rai_result' => 'nullable|string|max:30',
            'og_sullivan_result' => 'nullable|string|max:50',
            'streptococcus_b_result' => 'nullable|string|max:30',
            'delivery_date' => 'nullable|date',
            'delivery_mode' => 'nullable|string|max:30',
            'delivery_gestational_weeks' => 'nullable|integer|min:20|max:45',
            'newborn_sex' => 'nullable|string|in:M,F',
            'newborn_weight_grams' => 'nullable|integer|min:300|max:7000',
            'newborn_height_cm' => 'nullable|integer|min:20|max:70',
            'apgar_1min' => 'nullable|integer|min:0|max:10',
            'apgar_5min' => 'nullable|integer|min:0|max:10',
            'apgar_10min' => 'nullable|integer|min:0|max:10',
            'delivery_notes' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $record = $this->gynecologyService->storePregnancyRecord($patientId, $validated);

        return response()->json([
            'message' => 'Dossier obstétrical enregistré.',
            'pregnancy_record' => $record->fresh(['prenatalVisits', 'ultrasoundBiometries']),
        ], 201);
    }

    public function storePrenatalVisit(Request $request, int $pregnancyRecordId): JsonResponse
    {
        $validated = $request->validate([
            'visit_date' => 'required|date',
            'weight_kg' => 'nullable|numeric|min:30|max:250',
            'blood_pressure_systolic' => 'nullable|integer|min:60|max:250',
            'blood_pressure_diastolic' => 'nullable|integer|min:30|max:160',
            'fundal_height_cm' => 'nullable|numeric|min:5|max:55',
            'fetal_heart_rate' => 'nullable|integer|min:60|max:220',
            'fetal_presentation' => 'nullable|string|max:30',
            'fetal_position' => 'nullable|string|max:30',
            'fetal_movements' => 'nullable|string|in:present,reduced,absent',
            'urine_protein' => 'nullable|string|max:20',
            'urine_glucose' => 'nullable|string|max:20',
            'edema' => 'nullable|string|in:absent,mild,moderate,severe',
            'cervical_status' => 'nullable|string|max:50',
            'prescribed_exams' => 'nullable|array',
            'prescribed_supplements' => 'nullable|array',
            'observations' => 'nullable|string|max:2000',
            'recommendations' => 'nullable|string|max:2000',
        ]);

        $visit = $this->gynecologyService->storePrenatalVisit($pregnancyRecordId, $validated);

        return response()->json([
            'message' => 'Visite prénatale enregistrée.',
            'visit' => $visit,
        ], 201);
    }

    public function storeGynecologicalExam(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'pregnancy_record_id' => 'nullable|integer|exists:pregnancy_records,id',
            'exam_date' => 'required|date',
            'exam_type' => 'required|string|in:fcv,breast_exam,vaginal_exam,pelvimetry,complete',
            'fcv_type' => 'nullable|string|max:30',
            'fcv_result' => 'nullable|string|max:100',
            'fcv_bethesda_classification' => 'nullable|string|max:50',
            'fcv_sample_date' => 'nullable|date',
            'fcv_sample_quality' => 'nullable|string|max:30',
            'hpv_test_done' => 'nullable|boolean',
            'hpv_result' => 'nullable|string|max:30',
            'hpv_genotype' => 'nullable|string|max:50',
            'breast_exam_findings' => 'nullable|array',
            'breast_exam_conclusion' => 'nullable|string|max:50',
            'last_mammography_date' => 'nullable|date',
            'mammography_result' => 'nullable|string|max:100',
            'vaginal_exam_findings' => 'nullable|array',
            'cervix_appearance' => 'nullable|string|max:50',
            'cervix_consistency' => 'nullable|string|max:30',
            'cervix_position' => 'nullable|string|max:30',
            'cervix_dilation_cm' => 'nullable|string|max:10',
            'cervix_effacement_percent' => 'nullable|string|max:10',
            'uterus_size' => 'nullable|string|max:50',
            'uterus_position' => 'nullable|string|max:30',
            'uterus_mobility' => 'nullable|string|max:30',
            'adnexal_findings' => 'nullable|array',
            'douglas_pouch' => 'nullable|string|max:30',
            'pelvimetry' => 'nullable|array',
            'conclusion' => 'nullable|string|max:2000',
            'recommendations' => 'nullable|string|max:2000',
            'follow_up_plan' => 'nullable|string|max:100',
        ]);

        $exam = $this->gynecologyService->storeGynecologicalExam($patientId, $validated);

        return response()->json([
            'message' => 'Examen gynécologique enregistré.',
            'exam' => $exam,
        ], 201);
    }

    public function storeUltrasoundBiometry(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'pregnancy_record_id' => 'nullable|integer|exists:pregnancy_records,id',
            'exam_date' => 'required|date',
            'ultrasound_type' => 'nullable|string|in:obstetric,gynecological,doppler,morphological',
            'trimester' => 'nullable|integer|in:1,2,3',
            'exam_indication' => 'nullable|string|max:100',
            'fetal_presentation' => 'nullable|string|max:30',
            'fetal_position' => 'nullable|string|max:30',
            'fetal_heart_rate' => 'nullable|integer|min:60|max:220',
            'fetal_movements' => 'nullable|string|in:present,reduced,absent',
            'fetus_count' => 'nullable|integer|min:1|max:5',
            'bip_mm' => 'nullable|numeric|min:0|max:120',
            'hc_mm' => 'nullable|numeric|min:0|max:400',
            'ac_mm' => 'nullable|numeric|min:0|max:450',
            'fl_mm' => 'nullable|numeric|min:0|max:90',
            'efw_grams' => 'nullable|integer|min:0|max:6000',
            'efw_percentile' => 'nullable|integer|min:0|max:100',
            'amniotic_fluid_index_mm' => 'nullable|numeric|min:0|max:400',
            'amniotic_fluid_assessment' => 'nullable|string|in:normal,oligohydramnios,polyhydramnios',
            'placenta_location' => 'nullable|string|max:30',
            'placenta_grade' => 'nullable|string|max:10',
            'placenta_distance_from_os_mm' => 'nullable|integer|min:0|max:100',
            'umbilical_artery_pi' => 'nullable|string|max:20',
            'umbilical_artery_ri' => 'nullable|string|max:20',
            'umbilical_artery_sd_ratio' => 'nullable|string|max:20',
            'middle_cerebral_artery_pi' => 'nullable|string|max:20',
            'ductus_venosus_pi' => 'nullable|string|max:20',
            'crl_mm' => 'nullable|integer|min:0|max:100',
            'nt_mm' => 'nullable|string|max:10',
            'nasal_bone' => 'nullable|string|in:present,absent,not_assessed',
            'fetal_sex' => 'nullable|string|in:M,F,undetermined',
            'morphological_findings' => 'nullable|array',
            'structural_anomaly_detected' => 'nullable|boolean',
            'anomaly_description' => 'nullable|string|max:2000',
            'cervical_length_mm' => 'nullable|string|max:10',
            'ovarian_findings' => 'nullable|array',
            'uterine_findings' => 'nullable|array',
            'conclusion' => 'nullable|string|max:2000',
            'recommendations' => 'nullable|string|max:2000',
            'follow_up_plan' => 'nullable|string|max:200',
        ]);

        $biometry = $this->gynecologyService->storeUltrasoundBiometry($patientId, $validated);

        return response()->json([
            'message' => 'Biométrie échographique enregistrée.',
            'biometry' => $biometry,
        ], 201);
    }

    public function pregnancyHistory(int $patientId): JsonResponse
    {
        $records = PregnancyRecord::where('patient_id', $patientId)
            ->withCount('prenatalVisits')
            ->withCount('ultrasoundBiometries')
            ->orderByDesc('lmp_date')
            ->get();

        return response()->json(['pregnancy_records' => $records]);
    }

    public function examHistory(int $patientId, Request $request): JsonResponse
    {
        $type = $request->query('type');

        $query = GynecologicalExam::where('patient_id', $patientId);

        if ($type) {
            $query->where('exam_type', $type);
        }

        $exams = $query->orderByDesc('exam_date')->limit(50)->get();

        return response()->json(['exams' => $exams]);
    }

    public function ultrasoundHistory(int $patientId): JsonResponse
    {
        $ultrasounds = UltrasoundBiometry::where('patient_id', $patientId)
            ->orderByDesc('exam_date')
            ->limit(20)
            ->get();

        return response()->json(['ultrasounds' => $ultrasounds]);
    }

    public function quickEditPregnancy(Request $request, int $patientId, int $pregnancyId): JsonResponse
    {
        $record = PregnancyRecord::where('patient_id', $patientId)->findOrFail($pregnancyId);

        $validated = $request->validate([
            'risk_level' => 'nullable|string|in:low,moderate,high',
            'notes' => 'nullable|string|max:2000',
        ]);

        $record->update(array_filter($validated, fn ($v) => $v !== null));

        return response()->json([
            'message' => 'Mise à jour effectuée.',
            'pregnancy_record' => $record->fresh(),
        ]);
    }

    public function prenatalVisits(int $pregnancyRecordId): JsonResponse
    {
        $visits = PrenatalVisit::where('pregnancy_record_id', $pregnancyRecordId)
            ->orderBy('visit_date')
            ->get();

        return response()->json(['visits' => $visits]);
    }

    public function biometryChartData(int $patientId): JsonResponse
    {
        $ultrasounds = UltrasoundBiometry::where('patient_id', $patientId)
            ->whereNotNull('exam_date')
            ->orderBy('exam_date')
            ->get();

        $pregnancy = PregnancyRecord::where('patient_id', $patientId)
            ->where('pregnancy_status', 'active')
            ->latest('lmp_date')
            ->first();

        $chartData = [];
        foreach ($ultrasounds as $us) {
            $gaWeeks = null;
            if ($pregnancy && $pregnancy->lmp_date) {
                $gaWeeks = round($pregnancy->lmp_date->diffInDays($us->exam_date) / 7, 1);
            }

            $chartData[] = [
                'exam_date' => $us->exam_date->format('d/m/Y'),
                'ga_weeks' => $gaWeeks,
                'bip_mm' => $us->bip_mm,
                'hc_mm' => $us->hc_mm,
                'ac_mm' => $us->ac_mm,
                'fl_mm' => $us->fl_mm,
                'efw_grams' => $us->efw_grams,
            ];
        }

        return response()->json([
            'chart_data' => $chartData,
            'reference_curves' => $this->getBiometryReferenceCurves(),
        ]);
    }

    private function getBiometryReferenceCurves(): array
    {
        return [
            'bip' => [
                12 => ['p10' => 21, 'p50' => 24, 'p90' => 27],
                14 => ['p10' => 27, 'p50' => 30, 'p90' => 33],
                16 => ['p10' => 33, 'p50' => 36, 'p90' => 39],
                18 => ['p10' => 38, 'p50' => 42, 'p90' => 46],
                20 => ['p10' => 44, 'p50' => 48, 'p90' => 52],
                22 => ['p10' => 50, 'p50' => 54, 'p90' => 58],
                24 => ['p10' => 56, 'p50' => 60, 'p90' => 64],
                26 => ['p10' => 62, 'p50' => 66, 'p90' => 70],
                28 => ['p10' => 68, 'p50' => 72, 'p90' => 76],
                30 => ['p10' => 73, 'p50' => 77, 'p90' => 81],
                32 => ['p10' => 78, 'p50' => 82, 'p90' => 86],
                34 => ['p10' => 82, 'p50' => 86, 'p90' => 90],
                36 => ['p10' => 85, 'p50' => 89, 'p90' => 93],
                38 => ['p10' => 88, 'p50' => 92, 'p90' => 96],
                40 => ['p10' => 90, 'p50' => 94, 'p90' => 98],
            ],
            'efw' => [
                12 => ['p10' => 40, 'p50' => 55, 'p90' => 70],
                14 => ['p10' => 70, 'p50' => 95, 'p90' => 120],
                16 => ['p10' => 110, 'p50' => 150, 'p90' => 190],
                18 => ['p10' => 170, 'p50' => 230, 'p90' => 290],
                20 => ['p10' => 260, 'p50' => 340, 'p90' => 420],
                22 => ['p10' => 380, 'p50' => 490, 'p90' => 600],
                24 => ['p10' => 530, 'p50' => 680, 'p90' => 830],
                26 => ['p10' => 720, 'p50' => 910, 'p90' => 1100],
                28 => ['p10' => 950, 'p50' => 1180, 'p90' => 1410],
                30 => ['p10' => 1210, 'p50' => 1490, 'p90' => 1770],
                32 => ['p10' => 1500, 'p50' => 1830, 'p90' => 2160],
                34 => ['p10' => 1810, 'p50' => 2190, 'p90' => 2570],
                36 => ['p10' => 2130, 'p50' => 2560, 'p90' => 2990],
                38 => ['p10' => 2440, 'p50' => 2920, 'p90' => 3400],
                40 => ['p10' => 2720, 'p50' => 3250, 'p90' => 3780],
            ],
        ];
    }
}
