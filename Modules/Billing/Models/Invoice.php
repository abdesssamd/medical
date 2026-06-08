<?php

namespace Modules\Billing\Models;

use App\Models\Patient;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'organization_id',
        'practitioner_id',
        'treatment_plan_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total',
        'paid_amount',
        'remaining_amount',
        'payment_methods',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'payment_methods' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * Invoice statuses.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });

        static::saving(function (Invoice $invoice) {
            // Auto-calculate remaining amount
            $invoice->remaining_amount = (float) $invoice->total - (float) $invoice->paid_amount;

            // Update status based on payment
            if ((float) $invoice->paid_amount >= (float) $invoice->total && (float) $invoice->total > 0) {
                $invoice->status = self::STATUS_PAID;
                $invoice->paid_at = $invoice->paid_at ?? now();
            } elseif ((float) $invoice->paid_amount > 0) {
                $invoice->status = self::STATUS_PARTIALLY_PAID;
            }
        });
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $lastInvoice = self::whereYear('created_at', $year)->latest('id')->first();
        $sequence = $lastInvoice ? (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;

        return sprintf('FAC-%d-%04d', $year, $sequence);
    }

    /**
     * Get the patient this invoice belongs to.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the practitioner who created this invoice.
     */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    /**
     * Get the treatment plan this invoice is linked to.
     */
    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(\Modules\ClinicalRecord\Models\TreatmentPlan::class);
    }

    /**
     * Get all line items for this invoice.
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    /**
     * Get all payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all insurance claims for this invoice.
     */
    public function insuranceClaims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    /**
     * Get the payment progress percentage.
     */
    public function getPaymentProgressAttribute(): float
    {
        if ((float) $this->total <= 0) {
            return 0;
        }

        return round(((float) $this->paid_amount / (float) $this->total) * 100, 2);
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(): void
    {
        $this->update(['status' => self::STATUS_SENT]);
    }

    /**
     * Record a payment.
     */
    public function recordPayment(float $amount, string $method, ?string $reference = null, ?User $receivedBy = null): Payment
    {
        $payment = Payment::create([
            'invoice_id' => $this->id,
            'patient_id' => $this->patient_id,
            'method' => $method,
            'amount' => $amount,
            'reference' => $reference,
            'payment_date' => now(),
            'received_by' => $receivedBy?->id ?? auth()->id(),
        ]);

        // Update paid amount
        $this->increment('paid_amount', $amount);

        // Update payment methods array
        $paymentMethods = $this->payment_methods ?? [];
        $paymentMethods[] = [
            'method' => $method,
            'amount' => $amount,
            'reference' => $reference,
            'date' => now()->toDateTimeString(),
        ];
        $this->update(['payment_methods' => $paymentMethods]);

        return $payment;
    }

    /**
     * Cancel the invoice.
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $this->notes
                ? $this->notes."\n[Annulée: {$reason}]"
                : "[Annulée: {$reason}]",
        ]);
    }

    /**
     * Check if invoice is fully paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->isPaid() || $this->status === self::STATUS_CANCELLED) {
            return false;
        }

        return $this->due_date && now()->gt($this->due_date);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Unpaid invoices only.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
            self::STATUS_PARTIALLY_PAID,
        ]);
    }

    /**
     * Scope: Overdue invoices only.
     */
    public function scopeOverdue($query)
    {
        return $query->unpaid()
            ->where('due_date', '<', now());
    }

    /**
     * Scope: For a specific patient.
     */
    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('invoice_date', [$from, $to]);
    }
}
