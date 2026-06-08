<?php

namespace Modules\Pediatrics\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BirthHistory extends Model
{
    protected $fillable = [
        'patient_id',
        'practitioner_id',
        'consultation_id',
        'delivery_type',
        'delivery_place',
        'gestational_age_weeks',
        'presentation_at_birth',
        'apgar_1min',
        'apgar_5min',
        'apgar_10min',
        'birth_weight_grams',
        'birth_length_cm',
        'birth_head_circumference_cm',
        'neonatal_resuscitation',
        'resuscitation_details',
        'nicu_admission',
        'nicu_days',
        'jaundice',
        'jaundice_type',
        'jaundice_onset_date',
        'jaundice_treatment',
        'breastfeeding',
        'feeding_type',
        'vitamin_k_given',
        'hepatitis_b_birth_dose',
        'newborn_screening_done',
        'newborn_screening_result',
        'maternal_complications',
        'neonatal_complications',
        'notes',
    ];

    protected $casts = [
        'neonatal_resuscitation' => 'boolean',
        'nicu_admission' => 'boolean',
        'jaundice' => 'boolean',
        'breastfeeding' => 'boolean',
        'hepatitis_b_birth_dose' => 'boolean',
        'newborn_screening_done' => 'boolean',
        'jaundice_onset_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function getApgarScoreAttribute(): string
    {
        return sprintf('%d/%d/%d',
            $this->apgar_1min ?? 0,
            $this->apgar_5min ?? 0,
            $this->apgar_10min ?? 0
        );
    }

    public function getBirthMeasurementsAttribute(): string
    {
        $parts = [];
        if ($this->birth_weight_grams) {
            $parts[] = round($this->birth_weight_grams) . 'g';
        }
        if ($this->birth_length_cm) {
            $parts[] = $this->birth_length_cm . 'cm';
        }
        if ($this->birth_head_circumference_cm) {
            $parts[] = 'PC ' . $this->birth_head_circumference_cm . 'cm';
        }

        return implode(' / ', $parts);
    }
}
