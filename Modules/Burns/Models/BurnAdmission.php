<?php

namespace Modules\Burns\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BurnAdmission extends Model
{
    protected $fillable = [
        'patient_id',
        'practitioner_id',
        'consultation_id',
        'burn_type',
        'burn_cause',
        'accident_datetime',
        'admission_datetime',
        'admission_weight_kg',
        'admission_height_cm',
        'admission_location',
        'mechanism_description',
        'inhalation_injury_suspected',
        'inhalation_injury_confirmed',
        'inhalation_severity',
        'associated_injuries',
        'admission_status',
        'notes',
    ];

    protected $casts = [
        'accident_datetime' => 'datetime',
        'admission_datetime' => 'datetime',
        'inhalation_injury_suspected' => 'boolean',
        'inhalation_injury_confirmed' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(BurnAssessment::class)->orderByDesc('assessment_datetime');
    }

    public function latestAssessment(): HasOne
    {
        return $this->hasOne(BurnAssessment::class)->latestOfMany('assessment_datetime');
    }

    public function fluidResuscitations(): HasMany
    {
        return $this->hasMany(FluidResuscitation::class)->orderByDesc('resuscitation_start_time');
    }

    public function activeFluidResuscitation(): HasOne
    {
        return $this->hasOne(FluidResuscitation::class)->where('status', 'active')->latestOfMany('resuscitation_start_time');
    }

    public function woundEvolutions(): HasMany
    {
        return $this->hasMany(WoundEvolution::class)->orderByDesc('evolution_datetime');
    }

    public function getTimeSinceAccidentHoursAttribute(): ?float
    {
        if (! $this->accident_datetime) {
            return null;
        }

        return round(now()->diffInMinutes($this->accident_datetime) / 60, 1);
    }

    public function getBurnTypeLabelAttribute(): string
    {
        return match ($this->burn_type) {
            'thermal' => 'Thermique',
            'chemical' => 'Chimique',
            'electrical' => 'Électrique',
            'radiation' => 'Radique',
            default => $this->burn_type,
        };
    }
}
