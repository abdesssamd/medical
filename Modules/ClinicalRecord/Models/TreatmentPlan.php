<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'practitioner_id',
        'name',
        'objective',
        'status',
        'signature_channel',
        'signature_recipient',
        'signature_token',
        'signature_requested_at',
        'signature_expires_at',
        'signed_at',
        'signed_by_patient_name',
        'signature_ip',
        'signature_payload',
        'total_estimated_cost',
        'paid_amount',
        'start_date',
        'estimated_end_date',
        'actual_end_date',
        'phases',
        'notes',
    ];

    protected $casts = [
        'total_estimated_cost' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'start_date' => 'date',
        'estimated_end_date' => 'date',
        'actual_end_date' => 'date',
        'signature_requested_at' => 'datetime',
        'signature_expires_at' => 'datetime',
        'signed_at' => 'datetime',
        'signature_payload' => 'array',
        'phases' => 'array',
    ];

    /**
     * Treatment plan statuses.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_SIGNATURE = 'pending_signature';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * Get the patient this treatment plan belongs to.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the practitioner who created this plan.
     */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    /**
     * Get all procedures in this treatment plan.
     */
    public function procedures(): HasMany
    {
        return $this->hasMany(TreatmentPlanProcedure::class);
    }

    /**
     * Get all medical images linked to this plan.
     */
    public function medicalImages(): HasMany
    {
        return $this->hasMany(MedicalImage::class);
    }

    /**
     * Get the remaining amount to pay.
     */
    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->total_estimated_cost - (float) $this->paid_amount;
    }

    /**
     * Get the payment progress percentage.
     */
    public function getPaymentProgressAttribute(): float
    {
        if ((float) $this->total_estimated_cost <= 0) {
            return 0;
        }

        return round(((float) $this->paid_amount / (float) $this->total_estimated_cost) * 100, 2);
    }

    /**
     * Get the completion progress percentage.
     */
    public function getCompletionProgressAttribute(): float
    {
        $totalProcedures = $this->procedures()->count();

        if ($totalProcedures === 0) {
            return 0;
        }

        $completedProcedures = $this->procedures()
            ->whereHas('procedure', fn ($q) => $q->where('status', ClinicalProcedure::STATUS_COMPLETED))
            ->count();

        return round(($completedProcedures / $totalProcedures) * 100, 2);
    }

    /**
     * Approve the treatment plan.
     */
    public function approve(): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
        ]);
    }

    /**
     * Start the treatment plan.
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'start_date' => $this->start_date ?? now(),
        ]);
    }

    /**
     * Complete the treatment plan.
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'actual_end_date' => $this->actual_end_date ?? now(),
        ]);
    }

    /**
     * Archive the treatment plan.
     */
    public function archive(): void
    {
        $this->update([
            'status' => self::STATUS_ARCHIVED,
        ]);
    }

    /**
     * Add a phase to the treatment plan.
     */
    public function addPhase(string $name, int $order, array $procedureIds = []): void
    {
        $phases = $this->phases ?? [];
        $phases[] = [
            'name' => $name,
            'order' => $order,
            'procedure_ids' => $procedureIds,
        ];

        $this->update(['phases' => $phases]);
    }

    /**
     * Get procedures for a specific phase.
     */
    public function getPhaseProcedures(int $phaseNumber): HasMany
    {
        return $this->procedures()->where('phase_number', $phaseNumber);
    }

    /**
     * Check if all procedures are completed.
     */
    public function isFullyCompleted(): bool
    {
        return $this->procedures()
            ->whereHas('procedure', fn ($q) => $q->where('status', '!=', ClinicalProcedure::STATUS_COMPLETED))
            ->doesntExist();
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Active treatment plans (not draft/archived).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_APPROVED,
            self::STATUS_IN_PROGRESS,
        ]);
    }

    /**
     * Scope: For a specific patient.
     */
    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope: For a specific practitioner.
     */
    public function scopeForPractitioner($query, int $practitionerId)
    {
        return $query->where('practitioner_id', $practitionerId);
    }
}
