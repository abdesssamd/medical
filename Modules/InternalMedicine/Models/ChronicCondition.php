<?php

namespace Modules\InternalMedicine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChronicCondition extends Model
{
    protected $fillable = [
        'patient_id',
        'icd10_code',
        'diagnosis_name',
        'discovery_date',
        'status',
        'notes',
        'practitioner_id',
    ];

    protected function casts(): array
    {
        return [
            'discovery_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Patient::class);
    }
}
