<?php

namespace Modules\Gynecology\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrenatalVisit extends Model
{
    protected $fillable = [
        'pregnancy_record_id',
        'consultation_id',
        'practitioner_id',
        'visit_date',
        'visit_number',
        'gestational_weeks_at_visit',
        'gestational_days_at_visit',
        'weight_kg',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'fundal_height_cm',
        'fetal_heart_rate',
        'fetal_presentation',
        'fetal_position',
        'fetal_movements',
        'urine_protein',
        'urine_glucose',
        'edema',
        'cervical_status',
        'prescribed_exams',
        'prescribed_supplements',
        'observations',
        'recommendations',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'weight_kg' => 'float',
        'blood_pressure_systolic' => 'integer',
        'blood_pressure_diastolic' => 'integer',
        'fundal_height_cm' => 'float',
        'prescribed_exams' => 'array',
        'prescribed_supplements' => 'array',
    ];

    public function pregnancyRecord(): BelongsTo
    {
        return $this->belongsTo(PregnancyRecord::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function getBloodPressureDisplayAttribute(): string
    {
        if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
            return "{$this->blood_pressure_systolic}/{$this->blood_pressure_diastolic}";
        }

        return '-';
    }

    public function getGestationalAgeAtVisitDisplayAttribute(): string
    {
        $w = $this->gestational_weeks_at_visit;
        $d = $this->gestational_days_at_visit;

        if ($w === null) {
            return '-';
        }

        return "{$w} SA + {$d} j";
    }
}
