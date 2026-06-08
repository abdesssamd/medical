<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalProcedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'consultation_id',
        'practitioner_id',
        'specialty_id',
        'tooth_number',
        'procedure_code',
        'name',
        'description',
        'tooth_surfaces',
        'price',
        'status',
        'planned_date',
        'performed_at',
        'notes',
        'materials_used',
    ];

    protected $casts = [
        'tooth_surfaces' => 'array',
        'price' => 'decimal:2',
        'planned_date' => 'date',
        'performed_at' => 'datetime',
        'notes' => 'array',
        'materials_used' => 'array',
    ];

    /**
     * Procedure statuses.
     */
    public const STATUS_PLANNED = 'planned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the patient this procedure belongs to.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the appointment this procedure is linked to.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(\Modules\Appointment\Models\Appointment::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(PatientConsultation::class, 'consultation_id');
    }

    /**
     * Get the practitioner who performed this procedure.
     */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    /**
     * Get the specialty this procedure belongs to.
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /**
     * Get the treatment plan procedure entries.
     */
    public function treatmentPlanEntries(): HasMany
    {
        return $this->hasMany(TreatmentPlanProcedure::class);
    }

    /**
     * Mark procedure as in progress.
     */
    public function markInProgress(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'performed_at' => now(),
        ]);
    }

    /**
     * Mark procedure as completed.
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'performed_at' => $this->performed_at ?? now(),
        ]);
    }

    /**
     * Mark procedure as cancelled.
     */
    public function markCancelled(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => array_merge(
                $this->notes ?? [],
                ['cancelled_reason' => $reason]
            ),
        ]);
    }

    /**
     * Check if procedure is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if procedure is planned.
     */
    public function isPlanned(): bool
    {
        return $this->status === self::STATUS_PLANNED;
    }

    /**
     * Add material used during procedure.
     */
    public function addMaterial(string $materialName, array $details = []): void
    {
        $materials = $this->materials_used ?? [];
        $materials[] = array_merge([
            'name' => $materialName,
            'added_at' => now()->toDateTimeString(),
        ], $details);

        $this->update(['materials_used' => $materials]);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by patient.
     */
    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope: Filter by practitioner.
     */
    public function scopeForPractitioner($query, int $practitionerId)
    {
        return $query->where('practitioner_id', $practitionerId);
    }

    /**
     * Scope: Filter by tooth number.
     */
    public function scopeForTooth($query, int $toothNumber)
    {
        return $query->where('tooth_number', $toothNumber);
    }

    /**
     * Scope: Completed procedures only.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('performed_at', [$from, $to]);
    }

    /**
     * Scope: Procedures linked to appointments.
     */
    public function scopeWithAppointment($query)
    {
        return $query->whereNotNull('appointment_id');
    }
}
