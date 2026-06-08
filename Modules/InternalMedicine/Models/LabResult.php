<?php

namespace Modules\InternalMedicine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    protected $fillable = [
        'patient_id',
        'consultation_id',
        'test_date',
        'parameters',
        'practitioner_id',
    ];

    protected function casts(): array
    {
        return [
            'test_date' => 'date',
            'parameters' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Patient::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(\Modules\ClinicalRecord\Models\PatientConsultation::class);
    }
}
