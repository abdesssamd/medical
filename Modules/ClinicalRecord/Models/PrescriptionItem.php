<?php

namespace Modules\ClinicalRecord\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'medication_id',
        'medication_name',
        'dosage',
        'unit',
        'frequency',
        'duration_days',
        'instructions',
        'interaction_level',
        'alerts',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'alerts' => 'array',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }
}
