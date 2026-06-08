<?php

namespace Modules\Burns\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FluidResuscitation extends Model
{
    protected $fillable = [
        'burn_admission_id',
        'practitioner_id',
        'patient_weight_kg',
        'burn_surface_area_percent',
        'formula_used',
        'total_volume_ml',
        'first_8h_volume_ml',
        'next_16h_volume_ml',
        'first_8h_rate_ml_per_hour',
        'next_16h_rate_ml_per_hour',
        'resuscitation_start_time',
        'first_8h_end_time',
        'next_16h_end_time',
        'fluid_type',
        'maintenance_fluid_ml_per_hour',
        'urine_output_target_ml_per_hour',
        'actual_urine_output_ml',
        'status',
        'adjustments_notes',
    ];

    protected $casts = [
        'resuscitation_start_time' => 'datetime',
        'first_8h_end_time' => 'datetime',
        'next_16h_end_time' => 'datetime',
    ];

    public function burnAdmission(): BelongsTo
    {
        return $this->belongsTo(BurnAdmission::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function getCurrentPhaseAttribute(): string
    {
        $now = now();

        if ($now < $this->first_8h_end_time) {
            return 'first_8h';
        }

        if ($now < $this->next_16h_end_time) {
            return 'next_16h';
        }

        return 'completed';
    }

    public function getCurrentRateAttribute(): ?float
    {
        $phase = $this->current_phase;

        return match ($phase) {
            'first_8h' => $this->first_8h_rate_ml_per_hour,
            'next_16h' => $this->next_16h_rate_ml_per_hour,
            default => null,
        };
    }

    public function getTimeRemainingInCurrentPhaseMinutesAttribute(): ?int
    {
        $now = now();
        $phase = $this->current_phase;

        if ($phase === 'first_8h') {
            return max(0, $now->diffInMinutes($this->first_8h_end_time, false));
        }

        if ($phase === 'next_16h') {
            return max(0, $now->diffInMinutes($this->next_16h_end_time, false));
        }

        return null;
    }

    public function getFluidTypeLabelAttribute(): string
    {
        return match ($this->fluid_type) {
            'ringer_lactate' => 'Ringer Lactate',
            'normal_saline' => 'Sérum Salé Isotonique',
            'hartmann' => 'Hartmann',
            default => $this->fluid_type,
        };
    }
}
