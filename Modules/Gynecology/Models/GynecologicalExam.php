<?php

namespace Modules\Gynecology\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GynecologicalExam extends Model
{
    protected $fillable = [
        'patient_id',
        'practitioner_id',
        'consultation_id',
        'pregnancy_record_id',
        'exam_date',
        'exam_type',
        'fcv_type',
        'fcv_result',
        'fcv_bethesda_classification',
        'fcv_sample_date',
        'fcv_sample_quality',
        'hpv_test_done',
        'hpv_result',
        'hpv_genotype',
        'breast_exam_findings',
        'breast_exam_conclusion',
        'last_mammography_date',
        'mammography_result',
        'vaginal_exam_findings',
        'cervix_appearance',
        'cervix_consistency',
        'cervix_position',
        'cervix_dilation_cm',
        'cervix_effacement_percent',
        'uterus_size',
        'uterus_position',
        'uterus_mobility',
        'adnexal_findings',
        'douglas_pouch',
        'pelvimetry',
        'conclusion',
        'recommendations',
        'follow_up_plan',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'fcv_sample_date' => 'date',
        'last_mammography_date' => 'date',
        'hpv_test_done' => 'boolean',
        'breast_exam_findings' => 'array',
        'vaginal_exam_findings' => 'array',
        'adnexal_findings' => 'array',
        'pelvimetry' => 'array',
    ];

    const TYPE_FCV = 'fcv';
    const TYPE_BREAST = 'breast_exam';
    const TYPE_VAGINAL = 'vaginal_exam';
    const TYPE_PELVIMETRY = 'pelvimetry';
    const TYPE_COMPLETE = 'complete';

    public static function examTypes(): array
    {
        return [
            self::TYPE_FCV => 'Frottis cervico-vaginal (FCV)',
            self::TYPE_BREAST => 'Examen des seins',
            self::TYPE_VAGINAL => 'Toucher vaginal',
            self::TYPE_PELVIMETRY => 'Pelvimétrie',
            self::TYPE_COMPLETE => 'Examen gynécologique complet',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function pregnancyRecord(): BelongsTo
    {
        return $this->belongsTo(PregnancyRecord::class);
    }

    public function getExamTypeLabelAttribute(): string
    {
        return self::examTypes()[$this->exam_type] ?? $this->exam_type;
    }
}
