<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientConsultationAttachment extends Model
{
    protected $fillable = [
        'consultation_id',
        'patient_id',
        'uploaded_by',
        'file_path',
        'original_name',
        'mime_type',
        'file_size_bytes',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(PatientConsultation::class, 'consultation_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

