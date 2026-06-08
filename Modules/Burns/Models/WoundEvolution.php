<?php

namespace Modules\Burns\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoundEvolution extends Model
{
    protected $fillable = [
        'burn_admission_id',
        'practitioner_id',
        'burn_assessment_id',
        'evolution_datetime',
        'body_region',
        'wound_status',
        'depth_current',
        'wound_description',
        'graft_planned',
        'graft_planned_date',
        'graft_type',
        'graft_donor_site',
        'graft_completed',
        'graft_completed_date',
        'graft_outcome',
        'flap_planned',
        'flap_type',
        'flap_planned_date',
        'dressing_type',
        'dressing_instructions',
        'dressing_change_frequency_hours',
        'pharmacy_order_needed',
        'pharmacy_order_sent',
        'pharmacy_order_sent_at',
        'pharmacy_order_items',
        'infection_signs',
        'infection_confirmed',
        'infection_organism',
        'photo_path',
        'notes',
    ];

    protected $casts = [
        'evolution_datetime' => 'datetime',
        'graft_planned_date' => 'datetime',
        'graft_completed_date' => 'datetime',
        'flap_planned_date' => 'datetime',
        'pharmacy_order_sent_at' => 'datetime',
        'graft_planned' => 'boolean',
        'graft_completed' => 'boolean',
        'flap_planned' => 'boolean',
        'pharmacy_order_needed' => 'boolean',
        'pharmacy_order_sent' => 'boolean',
        'infection_confirmed' => 'boolean',
    ];

    public function burnAdmission(): BelongsTo
    {
        return $this->belongsTo(BurnAdmission::class);
    }

    public function burnAssessment(): BelongsTo
    {
        return $this->belongsTo(BurnAssessment::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function getWoundStatusLabelAttribute(): string
    {
        return match ($this->wound_status) {
            'healing' => 'En cicatrisation',
            'stable' => 'Stable',
            'deteriorating' => 'En détérioration',
            'infected' => 'Infectée',
            'grafted' => 'Greffée',
            'closed' => 'Fermée',
            default => $this->wound_status,
        };
    }

    public function getGraftTypeLabelAttribute(): string
    {
        return match ($this->graft_type) {
            'split_thickness' => 'Peau mince',
            'full_thickness' => 'Peau totale',
            'cultured_epithelial' => 'Épithélium cultivé',
            'dermal_substitute' => 'Substitut dermique',
            default => $this->graft_type ?? '-',
        };
    }
}
