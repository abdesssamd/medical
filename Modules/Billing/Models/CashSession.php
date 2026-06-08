<?php

namespace Modules\Billing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    use HasFactory;

    protected $table = 'cash_sessions';

    protected $fillable = [
        'user_id',
        'opened_at',
        'closed_at',
        'initial_balance',
        'theoretical_total',
        'actual_total',
        'difference',
        'variance_reason',
        'status',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'initial_balance' => 'decimal:2',
        'theoretical_total' => 'decimal:2',
        'actual_total' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_RECONCILED = 'reconciled';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function calculateTheoretical(): float
    {
        return (float) ($this->initial_balance + $this->transactions()->sum('amount'));
    }

    public function calculateDifference(): void
    {
        $this->difference = (float) ($this->actual_total - $this->theoretical_total);
    }

    public function close(float $actualTotal, ?string $reason = null): void
    {
        $this->actual_total = $actualTotal;
        $this->calculateDifference();
        $this->variance_reason = $reason;
        $this->closed_at = now();
        $this->status = self::STATUS_CLOSED;
        $this->save();
    }
}
