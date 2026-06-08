<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabOrder extends Model
{
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'clinical_procedure_id',
        'practitioner_id',
        'lab_name',
        'lab_contact',
        'order_number',
        'type',
        'status',
        'due_date',
        'delivered_at',
        'external_file_paths',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'delivered_at' => 'datetime',
        'external_file_paths' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (LabOrder $order) {
            if (empty($order->order_number)) {
                $year = now()->year;
                $last = self::whereYear('created_at', $year)->latest('id')->first();
                $next = $last ? (int) substr($last->order_number, -4) + 1 : 1;
                $order->order_number = sprintf('LAB-%d-%04d', $year, $next);
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(\Modules\Appointment\Models\Appointment::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'practitioner_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(LabOrderEvent::class)->orderByDesc('event_at')->orderByDesc('id');
    }
}
