<?php

namespace Modules\Pediatrics\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrowthRecord extends Model
{
    protected $fillable = [
        'patient_id',
        'practitioner_id',
        'consultation_id',
        'measurement_date',
        'age_months',
        'weight_kg',
        'height_cm',
        'head_circumference_cm',
        'arm_circumference_cm',
        'weight_percentile',
        'height_percentile',
        'head_circumference_percentile',
        'bmi',
        'weight_for_height_percentile',
        'nutritional_status',
        'notes',
    ];

    protected $casts = [
        'measurement_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function getBmiCalculatedAttribute(): ?float
    {
        if ($this->weight_kg && $this->height_cm) {
            $heightM = $this->height_cm / 100;

            return round($this->weight_kg / ($heightM * $heightM), 1);
        }

        return null;
    }
}
