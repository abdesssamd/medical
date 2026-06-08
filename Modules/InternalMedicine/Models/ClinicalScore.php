<?php

namespace Modules\InternalMedicine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalScore extends Model
{
    protected $fillable = [
        'patient_id',
        'score_type',
        'calculated_value',
        'date',
        'score_data',
        'practitioner_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'score_data' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Patient::class);
    }
}
