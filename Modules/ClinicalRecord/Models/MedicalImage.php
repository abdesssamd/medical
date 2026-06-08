<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'treatment_plan_id',
        'procedure_id',
        'type',
        'file_path',
        'thumbnail_path',
        'file_size',
        'dicom_uid',
        'associated_teeth',
        'taken_by',
        'taken_at',
        'notes',
    ];

    protected $casts = [
        'associated_teeth' => 'array',
        'taken_at' => 'datetime',
    ];

    /**
     * Image types.
     */
    public const TYPE_XRAY = 'xray';
    public const TYPE_CBCT = 'cbct';
    public const TYPE_INTRORAL_PHOTO = 'intraoral_photo';
    public const TYPE_STL_SCAN = 'stl_scan';
    public const TYPE_DICOM = 'dicom';

    /**
     * Get the patient this image belongs to.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the treatment plan this image is linked to.
     */
    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    /**
     * Get the clinical procedure this image is linked to.
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(ClinicalProcedure::class);
    }

    public function imagingStudies(): HasMany
    {
        return $this->hasMany(ImagingStudy::class);
    }

    /**
     * Check if image is a DICOM file.
     */
    public function isDicom(): bool
    {
        return $this->type === self::TYPE_DICOM;
    }

    /**
     * Check if image is a 3D scan (CBCT or STL).
     */
    public function is3D(): bool
    {
        return in_array($this->type, [self::TYPE_CBCT, self::TYPE_STL_SCAN]);
    }

    /**
     * Get the file size in human-readable format.
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    /**
     * Scope: Filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by patient.
     */
    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope: Images associated with specific teeth.
     */
    public function scopeForTooth($query, int $toothNumber)
    {
        return $query->whereJsonContains('associated_teeth', $toothNumber);
    }

    /**
     * Scope: Recent images first.
     */
    public function scopeRecentFirst($query)
    {
        return $query->orderByDesc('taken_at')->orderByDesc('created_at');
    }

    /**
     * Scope: DICOM images only.
     */
    public function scopeDicom($query)
    {
        return $query->where('type', self::TYPE_DICOM);
    }

    /**
     * Scope: 3D images only.
     */
    public function scope3D($query)
    {
        return $query->whereIn('type', [self::TYPE_CBCT, self::TYPE_STL_SCAN]);
    }
}
