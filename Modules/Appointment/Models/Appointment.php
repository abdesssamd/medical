<?php

namespace Modules\Appointment\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Appointment\Models\SecretaryNote;
use Modules\Appointment\Models\SecretaryTask;
use Modules\Queue\Models\Ticket as QueueTicket;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'professional_id',
        'secretary_id',
        'created_by',
        'patient_id',
        'appointment_type_id',
        'parent_appointment_id',
        'patient_name',
        'patient_phone',
        'patient_email',
        'appointment_date',
        'start_time',
        'end_time',
        'status',
        'follow_up_status',
        'notes',
        'consultation_notes',
        'consulted_at',
        'queue_ticket_id',
        'room_id',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'consulted_at' => 'datetime',
        'queue_ticket_id' => 'integer',
        'consultation_notes' => 'array',
    ];

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function secretary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secretary_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(\Modules\Scheduling\Models\AppointmentType::class);
    }

    public function planning(): BelongsTo
    {
        return $this->belongsTo(Planning::class);
    }

    public function parentAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'parent_appointment_id');
    }

    public function followUpAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'parent_appointment_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Room::class);
    }

    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }

    public function queueTicket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class, 'queue_ticket_id');
    }

    public function patientJourney(): HasOne
    {
        return $this->hasOne(PatientJourney::class);
    }

    /**
     * Alias relation used by secretary services.
     */
    public function journey(): HasOne
    {
        return $this->patientJourney();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(SecretaryTask::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(SecretaryNote::class);
    }

    public function consultation(): HasOne
    {
        return $this->hasOne(\Modules\ClinicalRecord\Models\PatientConsultation::class);
    }

    /**
     * Get the clinical procedures linked to this appointment.
     */
    public function clinicalProcedures(): HasMany
    {
        return $this->hasMany(\Modules\ClinicalRecord\Models\ClinicalProcedure::class);
    }
}
