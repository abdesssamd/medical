<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InsuranceClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'insurance_company_id',
        'patient_insurance_id',
        'claim_number',
        'external_reference',
        'claimed_amount',
        'approved_amount',
        'rejected_amount',
        'patient_remaining',
        'status',
        'submitted_at',
        'response_at',
        'paid_at',
        'rejection_reason',
        'line_items',
    ];

    protected $casts = [
        'claimed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'rejected_amount' => 'decimal:2',
        'patient_remaining' => 'decimal:2',
        'submitted_at' => 'date',
        'response_at' => 'date',
        'paid_at' => 'date',
        'line_items' => 'array',
    ];

    /**
     * Claim statuses.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_REJECTED = 'rejected';

    protected static function booted(): void
    {
        static::creating(function (InsuranceClaim $claim) {
            if (empty($claim->claim_number)) {
                $claim->claim_number = self::generateClaimNumber();
            }
        });

        static::saving(function (InsuranceClaim $claim) {
            // Calculate patient remaining
            if ($claim->approved_amount !== null && $claim->claimed_amount !== null) {
                $claim->patient_remaining = (float) $claim->claimed_amount - (float) $claim->approved_amount;
            }
        });
    }

    /**
     * Generate a unique claim number.
     */
    public static function generateClaimNumber(): string
    {
        $year = now()->year;
        $lastClaim = self::whereYear('created_at', $year)->latest('id')->first();
        $sequence = $lastClaim ? (int) substr($lastClaim->claim_number, -4) + 1 : 1;

        return sprintf('CLM-%d-%04d', $year, $sequence);
    }

    /**
     * Get the invoice this claim belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the insurance company.
     */
    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    /**
     * Get the patient insurance subscription.
     */
    public function patientInsurance(): BelongsTo
    {
        return $this->belongsTo(PatientInsuranceSubscription::class);
    }

    /**
     * Mark claim as submitted.
     */
    public function markSubmitted(): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Mark claim as approved.
     */
    public function markApproved(float $approvedAmount): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_amount' => $approvedAmount,
            'response_at' => now(),
        ]);
    }

    /**
     * Mark claim as paid.
     */
    public function markPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark claim as rejected.
     */
    public function markRejected(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'response_at' => now(),
            'approved_amount' => 0,
        ]);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Pending claims only.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_SUBMITTED]);
    }

    /**
     * Scope: For a specific insurance company.
     */
    public function scopeForInsurance($query, int $insuranceCompanyId)
    {
        return $query->where('insurance_company_id', $insuranceCompanyId);
    }

    /**
     * Scope: Overdue claims (submitted but no response after 30 days).
     */
    public function scopeOverdue($query)
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW])
            ->where('submitted_at', '<', now()->subDays(30));
    }
}
