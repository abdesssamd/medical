<?php

namespace Modules\Appointment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Queue\Models\Service as QueueService;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'appointment_settings';

    protected $fillable = [
        'professional_id',
        'default_commission_amount',
        'currency',
        'allow_secretary_edit',
        'allow_secretary_cancel',
        'timezone',
        'queue_service_id',
        'emergency_slots_per_day',
        'weekly_revenue_target',
        'capacity_exceptions',
        'external_sync_enabled',
        'external_sync_provider',
    ];

    protected $casts = [
        'default_commission_amount' => 'decimal:2',
        'allow_secretary_edit' => 'boolean',
        'allow_secretary_cancel' => 'boolean',
        'queue_service_id' => 'integer',
        'emergency_slots_per_day' => 'integer',
        'weekly_revenue_target' => 'decimal:2',
        'capacity_exceptions' => 'array',
        'external_sync_enabled' => 'boolean',
    ];

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function queueService(): BelongsTo
    {
        return $this->belongsTo(QueueService::class, 'queue_service_id');
    }
}
