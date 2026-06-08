<?php

namespace Modules\ClinicalRecord\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanProcedure extends Model
{
    use HasFactory;

    protected $table = 'treatment_plan_procedures';

    protected $fillable = [
        'treatment_plan_id',
        'procedure_id',
        'phase_number',
        'order_in_phase',
        'status',
    ];

    protected $casts = [
        'phase_number' => 'integer',
        'order_in_phase' => 'integer',
    ];

    /**
     * Procedure statuses.
     */
    public const STATUS_PLANNED = 'planned';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';

    /**
     * Get the treatment plan this entry belongs to.
     */
    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    /**
     * Get the clinical procedure.
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(ClinicalProcedure::class);
    }

    /**
     * Mark as scheduled.
     */
    public function markScheduled(): void
    {
        $this->update(['status' => self::STATUS_SCHEDULED]);
    }

    /**
     * Mark as completed.
     */
    public function markCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
        $this->procedure?->markCompleted();
    }

    /**
     * Mark as skipped.
     */
    public function markSkipped(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'notes' => $reason,
        ]);
    }

    /**
     * Scope: Filter by phase.
     */
    public function scopePhase($query, int $phaseNumber)
    {
        return $query->where('phase_number', $phaseNumber);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Order by phase and order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('phase_number')->orderBy('order_in_phase');
    }
}
