<?php

namespace Modules\Pediatrics\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaccinationRecord extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ADMINISTERED = 'administered';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_REFUSED = 'refused';
    public const STATUS_CONTRAINDICATED = 'contraindicated';

    protected $fillable = [
        'patient_id',
        'vaccine_id',
        'practitioner_id',
        'consultation_id',
        'scheduled_date',
        'administered_date',
        'batch_number',
        'manufacturer',
        'expiry_date',
        'injection_site',
        'status',
        'adverse_reaction',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'administered_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->scheduled_date
            && $this->scheduled_date->isPast();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_ADMINISTERED => 'Administré',
            self::STATUS_OVERDUE => 'En retard',
            self::STATUS_REFUSED => 'Refusé',
            self::STATUS_CONTRAINDICATED => 'Contre-indiqué',
            default => $this->status,
        };
    }
}
