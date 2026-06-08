<?php

namespace Modules\Billing\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    use HasFactory;

    protected $table = 'cash_transactions';

    protected $fillable = [
        'cash_session_id',
        'invoice_id',
        'patient_id',
        'recorded_by',
        'method',
        'amount',
        'reference',
        'recorded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public const METHOD_CASH = 'cash';
    public const METHOD_CARD = 'card';
    public const METHOD_CHECK = 'check';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_INSURANCE = 'insurance';

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
