<?php

namespace Modules\Appointment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Planning extends Model
{
    use HasFactory;

    protected $fillable = [
        'professional_id',
        'day_of_week',
        'start_time',
        'end_time',
        'consultation_minutes',
        'planning_mode',
        'appointment_type_id',
        'max_patients_per_day',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'consultation_minutes' => 'integer',
        'max_patients_per_day' => 'integer',
        'is_active' => 'boolean',
    ];

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(\Modules\Scheduling\Models\AppointmentType::class);
    }

    public function getEffectiveDurationMinutes(): int
    {
        if ($this->planning_mode === 'by_act' && $this->appointmentType) {
            return $this->appointmentType->duration_minutes;
        }
        return $this->consultation_minutes;
    }
}
