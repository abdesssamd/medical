<?php

namespace Modules\Queue\Models;

use Modules\Appointment\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueuePriority extends Model
{
    use HasFactory;

    protected $table = 'queue_priorities';

    protected $fillable = [
        'appointment_id',
        'ticket_id',
        'priority_level',
        'override_reason',
        'overridden_by',
        'overridden_at',
        'position',
    ];

    protected $casts = [
        'overridden_at' => 'datetime',
    ];

    public const PRIORITY_CRITICAL = 'critical';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_LOW = 'low';

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'overridden_by');
    }

    public function getPriorityScore(): int
    {
        $scores = [
            self::PRIORITY_CRITICAL => 1,
            self::PRIORITY_HIGH => 2,
            self::PRIORITY_NORMAL => 3,
            self::PRIORITY_LOW => 4,
        ];
        return $scores[$this->priority_level] ?? 3;
    }
}
