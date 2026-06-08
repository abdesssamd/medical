<?php

namespace Modules\ClinicalRecord\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_template_id',
        'medication_id',
        'medication_name',
        'dosage',
        'unit',
        'frequency',
        'duration_days',
        'instructions',
    ];

    protected $casts = [
        'duration_days' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(PrescriptionTemplate::class, 'prescription_template_id');
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }
}
