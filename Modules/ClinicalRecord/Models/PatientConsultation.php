<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Billing\Models\Invoice;
use Modules\Appointment\Models\Planning;
use Modules\ClinicalRecord\Models\ClinicalProcedure;

class PatientConsultation extends Model
{
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'planning_id',
        'practitioner_id',
        'consultation_date',
        'consultation_reason',
        'consultation_type',
        'consultation_status',
        'chief_complaint',
        'anamnesis',
        'observations',
        'clinical_exam',
        'diagnosis',
        'diagnosis_code',
        'diagnosis_label',
        'prescription',
        'recommendations',
        'vital_signs',
        'invoice_id',
        'payment_status',
        'paid_at',
        'source',
        'notes',
    ];

    protected $casts = [
        'consultation_date' => 'date',
        'paid_at' => 'datetime',
        'vital_signs' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(\Modules\Appointment\Models\Appointment::class);
    }

    public function planning(): BelongsTo
    {
        return $this->belongsTo(Planning::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PatientConsultationAttachment::class, 'consultation_id');
    }

    public function procedures(): HasMany
    {
        return $this->hasMany(ClinicalProcedure::class, 'consultation_id');
    }
}
