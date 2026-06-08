<?php

namespace Modules\Gynecology\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UltrasoundBiometry extends Model
{
    protected $fillable = [
        'patient_id',
        'pregnancy_record_id',
        'practitioner_id',
        'consultation_id',
        'exam_date',
        'ultrasound_type',
        'trimester',
        'exam_indication',
        'fetal_presentation',
        'fetal_position',
        'fetal_heart_rate',
        'fetal_movements',
        'fetus_count',
        'bip_mm',
        'hc_mm',
        'ac_mm',
        'fl_mm',
        'efw_grams',
        'efw_percentile',
        'amniotic_fluid_index_mm',
        'amniotic_fluid_assessment',
        'placenta_location',
        'placenta_grade',
        'placenta_distance_from_os_mm',
        'umbilical_artery_pi',
        'umbilical_artery_ri',
        'umbilical_artery_sd_ratio',
        'middle_cerebral_artery_pi',
        'ductus_venosus_pi',
        'crl_mm',
        'nt_mm',
        'nasal_bone',
        'fetal_sex',
        'morphological_findings',
        'structural_anomaly_detected',
        'anomaly_description',
        'cervical_length_mm',
        'ovarian_findings',
        'uterine_findings',
        'conclusion',
        'recommendations',
        'follow_up_plan',
        'image_path',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'bip_mm' => 'float',
        'hc_mm' => 'float',
        'ac_mm' => 'float',
        'fl_mm' => 'float',
        'efw_grams' => 'integer',
        'amniotic_fluid_index_mm' => 'float',
        'structural_anomaly_detected' => 'boolean',
        'morphological_findings' => 'array',
        'ovarian_findings' => 'array',
        'uterine_findings' => 'array',
    ];

    const TYPE_OBSTETRIC = 'obstetric';
    const TYPE_GYNECOLOGICAL = 'gynecological';
    const TYPE_DOPPLER = 'doppler';
    const TYPE_MORPHOLOGICAL = 'morphological';

    public static function ultrasoundTypes(): array
    {
        return [
            self::TYPE_OBSTETRIC => 'Échographie obstétricale',
            self::TYPE_GYNECOLOGICAL => 'Échographie gynécologique',
            self::TYPE_DOPPLER => 'Doppler fœtal',
            self::TYPE_MORPHOLOGICAL => 'Échographie morphologique',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function pregnancyRecord(): BelongsTo
    {
        return $this->belongsTo(PregnancyRecord::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function getBiometrySummaryAttribute(): string
    {
        $parts = [];

        if ($this->bip_mm) $parts[] = "BIP: {$this->bip_mm} mm";
        if ($this->hc_mm) $parts[] = "PC: {$this->hc_mm} mm";
        if ($this->ac_mm) $parts[] = "PA: {$this->ac_mm} mm";
        if ($this->fl_mm) $parts[] = "LF: {$this->fl_mm} mm";
        if ($this->efw_grams) $parts[] = "EPF: {$this->efw_grams} g";

        return implode(' | ', $parts) ?: 'Biométrie non renseignée';
    }

    public function getTrimesterLabelAttribute(): string
    {
        return match ($this->trimester) {
            1 => '1er trimestre (T1)',
            2 => '2ème trimestre (T2)',
            3 => '3ème trimestre (T3)',
            default => '-',
        };
    }
}
