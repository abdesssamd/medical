<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagingStudy extends Model
{
    protected $fillable = [
        'patient_id',
        'medical_image_id',
        'uploaded_by',
        'modality',
        'study_uid',
        'series_uid',
        'instance_uid',
        'file_path',
        'mime_type',
        'file_size_bytes',
        'metadata',
        'captured_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'captured_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function medicalImage(): BelongsTo
    {
        return $this->belongsTo(MedicalImage::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

