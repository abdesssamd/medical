<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientSterilizationTrace extends Model
{
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'clinical_procedure_id',
        'sterilization_pouch_id',
        'scanned_by',
        'scanned_at',
        'is_conformity_ok',
        'conformity_issue',
        'notes',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'is_conformity_ok' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(\Modules\Appointment\Models\Appointment::class);
    }

    public function clinicalProcedure(): BelongsTo
    {
        return $this->belongsTo(\Modules\ClinicalRecord\Models\ClinicalProcedure::class, 'clinical_procedure_id');
    }

    public function pouch(): BelongsTo
    {
        return $this->belongsTo(SterilizationPouch::class, 'sterilization_pouch_id');
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'scanned_by');
    }
}
