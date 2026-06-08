<?php

namespace Modules\Appointment\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Billing\Models\Invoice;
use Modules\Queue\Models\Ticket;

class PatientJourney extends Model
{
    public const STATUS_BOOKED = 'booked';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_IN_CARE = 'in_care';
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'queue_ticket_id',
        'invoice_id',
        'public_tracking_code',
        'estimated_wait_minutes',
        'assigned_room_label',
        'current_status',
        'arrived_at',
        'in_care_at',
        'awaiting_payment_at',
        'completed_at',
    ];

    protected $casts = [
        'arrived_at' => 'datetime',
        'in_care_at' => 'datetime',
        'awaiting_payment_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_wait_minutes' => 'integer',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function queueTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'queue_ticket_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
