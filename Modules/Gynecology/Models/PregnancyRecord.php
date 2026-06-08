<?php

namespace Modules\Gynecology\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PregnancyRecord extends Model
{
    protected $fillable = [
        'patient_id',
        'practitioner_id',
        'consultation_id',
        'gynecological_history_id',
        'pregnancy_number',
        'lmp_date',
        'conception_date',
        'estimated_delivery_date',
        'corrected_delivery_date',
        'gestational_age_weeks',
        'gestational_age_days',
        'trimester',
        'pregnancy_status',
        'risk_level',
        'risk_factors',
        'blood_type',
        'rh_factor',
        'partner_blood_type',
        'partner_rh_factor',
        'serology_hiv',
        'serology_hepatitis_b',
        'serology_hepatitis_c',
        'serology_syphilis',
        'serology_toxoplasmosis',
        'serology_rubella',
        'serology_cmV',
        'blood_group_rh',
        'rai_result',
        'og_sullivan_result',
        'streptococcus_b_result',
        'delivery_date',
        'delivery_mode',
        'delivery_gestational_weeks',
        'newborn_sex',
        'newborn_weight_grams',
        'newborn_height_cm',
        'apgar_1min',
        'apgar_5min',
        'apgar_10min',
        'delivery_notes',
        'notes',
    ];

    protected $casts = [
        'lmp_date' => 'date',
        'conception_date' => 'date',
        'estimated_delivery_date' => 'date',
        'corrected_delivery_date' => 'date',
        'delivery_date' => 'date',
        'risk_factors' => 'array',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_MISSED = 'missed';
    const STATUS_TERMINATED = 'terminated';

    const RISK_LOW = 'low';
    const RISK_MODERATE = 'moderate';
    const RISK_HIGH = 'high';

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function gynecologicalHistory(): BelongsTo
    {
        return $this->belongsTo(GynecologicalHistory::class);
    }

    public function prenatalVisits(): HasMany
    {
        return $this->hasMany(PrenatalVisit::class)->orderBy('visit_date');
    }

    public function ultrasoundBiometries(): HasMany
    {
        return $this->hasMany(UltrasoundBiometry::class)->orderBy('exam_date');
    }

    public function gynecologicalExams(): HasMany
    {
        return $this->hasMany(GynecologicalExam::class)->orderBy('exam_date');
    }

    public function calculateGestationalAge(?Carbon $referenceDate = null): array
    {
        if (! $this->lmp_date) {
            return ['weeks' => null, 'days' => null, 'trimester' => null, 'display' => 'Non calculé', 'is_valid' => false];
        }

        return \Modules\Gynecology\Services\PregnancyCalculatorService::gestationalAge($this->lmp_date, $referenceDate);
    }

    public static function calculateEDD(Carbon $lmp): Carbon
    {
        return \Modules\Gynecology\Services\PregnancyCalculatorService::calculateEDD($lmp);
    }

    public static function estimateConception(Carbon $lmp): Carbon
    {
        return \Modules\Gynecology\Services\PregnancyCalculatorService::estimateConceptionDate($lmp);
    }

    public function getGestationalAgeDisplayAttribute(): string
    {
        $ga = $this->calculateGestationalAge();
        if ($ga['weeks'] === null) {
            return 'Non calculé';
        }

        return "{$ga['weeks']} SA + {$ga['days']} j";
    }

    public function getDaysUntilDeliveryAttribute(): ?int
    {
        $edd = $this->corrected_delivery_date ?? $this->estimated_delivery_date;
        if (! $edd) {
            return null;
        }

        return Carbon::now()->diffInDays($edd, false);
    }
}
