<?php

namespace Modules\Billing\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'patient_id',
        'insurance_claim_id',
        'payment_number',
        'method',
        'amount',
        'currency',
        'reference',
        'payment_date',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Payment methods.
     */
    public const METHOD_CASH = 'cash';
    public const METHOD_CARD = 'card';
    public const METHOD_CHECK = 'check';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_INSURANCE = 'insurance';

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber();
            }
        });
    }

    /**
     * Generate a unique payment number.
     */
    public static function generatePaymentNumber(): string
    {
        $year = now()->year;
        $lastPayment = self::whereYear('created_at', $year)->latest('id')->first();
        $sequence = $lastPayment ? (int) substr($lastPayment->payment_number, -4) + 1 : 1;

        return sprintf('PAY-%d-%04d', $year, $sequence);
    }

    /**
     * Get the invoice this payment belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the patient who made the payment.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the insurance claim this payment is linked to.
     */
    public function insuranceClaim(): BelongsTo
    {
        return $this->belongsTo(InsuranceClaim::class);
    }

    /**
     * Get the user who received the payment.
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Scope: Filter by method.
     */
    public function scopeMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    /**
     * Scope: For a specific invoice.
     */
    public function scopeForInvoice($query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('payment_date', [$from, $to]);
    }

    /**
     * Scope: Total amount for a date range.
     */
    public function scopeTotalAmount($query, string $from, string $to): float
    {
        return (float) (clone $query)->whereBetween('payment_date', [$from, $to])->sum('amount');
    }
}
