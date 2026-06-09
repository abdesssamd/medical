<?php

namespace Modules\Appointment\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Appointment\Models\PatientDocument;
use Modules\Queue\Models\Service;
use Modules\Scheduling\Models\AppointmentType;

class AppointmentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'nin',
        'first_name',
        'last_name',
        'date_of_birth',
        'phone',
        'email',
        'service_id',
        'appointment_type_id',
        'professional_id',
        'status',
        'notes',
        'preferred_date_from',
        'preferred_date_to',
        'time_preference',
        'validated_at',
        'validated_by',
        'booked_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'preferred_date_from' => 'date',
        'preferred_date_to' => 'date',
        'validated_at' => 'datetime',
        'booked_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientDocument::class, 'appointment_request_id');
    }

    public function appointment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Appointment::class, 'appointment_request_id');
    }
}
