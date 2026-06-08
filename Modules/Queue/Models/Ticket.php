<?php

namespace Modules\Queue\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'service_id',
        'counter_id',
        'agent_id',
        'transferred_to_service_id',
        'ticket_date',
        'sequence_number',
        'ticket_number',
        'public_code',
        'status',
        'is_appointment',
        'appointment_at',
        'estimated_wait_minutes',
        'arrived_at',
        'called_at',
        'served_at',
    ];

    protected $casts = [
        'ticket_date' => 'date',
        'is_appointment' => 'boolean',
        'appointment_at' => 'datetime',
        'arrived_at' => 'datetime',
        'called_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function transferredToService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'transferred_to_service_id');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }
}

