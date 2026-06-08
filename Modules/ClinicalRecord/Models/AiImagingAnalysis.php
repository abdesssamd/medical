<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiImagingAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'imaging_study_id',
        'provider',
        'analysis_type',
        'status',
        'confidence',
        'findings',
        'raw_response',
        'requested_at',
        'completed_at',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'findings' => 'array',
        'raw_response' => 'array',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function imagingStudy(): BelongsTo
    {
        return $this->belongsTo(ImagingStudy::class, 'imaging_study_id');
    }
}
