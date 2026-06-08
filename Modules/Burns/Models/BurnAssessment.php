<?php

namespace Modules\Burns\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BurnAssessment extends Model
{
    protected $fillable = [
        'burn_admission_id',
        'practitioner_id',
        'assessment_datetime',
        'total_burn_surface_area',
        'partial_thickness_area',
        'full_thickness_area',
        'superficial_area',
        'body_regions',
        'head_face_percent',
        'neck_percent',
        'anterior_trunk_percent',
        'posterior_trunk_percent',
        'right_arm_percent',
        'left_arm_percent',
        'right_forearm_hand_percent',
        'left_forearm_hand_percent',
        'right_thigh_percent',
        'left_thigh_percent',
        'right_leg_foot_percent',
        'left_leg_foot_percent',
        'genitalia_percent',
        'depth_dominant',
        'circumferential_burns',
        'circumferential_locations',
        'escharotomy_needed',
        'escharotomy_locations',
        'notes',
    ];

    protected $casts = [
        'assessment_datetime' => 'datetime',
        'body_regions' => 'array',
        'circumferential_burns' => 'boolean',
        'escharotomy_needed' => 'boolean',
    ];

    public function burnAdmission(): BelongsTo
    {
        return $this->belongsTo(BurnAdmission::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function woundEvolutions(): HasMany
    {
        return $this->hasMany(WoundEvolution::class);
    }

    public function getDepthDominantLabelAttribute(): string
    {
        return match ($this->depth_dominant) {
            'superficial' => '1er degré (superficiel)',
            'partial_superficial' => '2ème degré superficiel',
            'partial_deep' => '2ème degré profond',
            'full_thickness' => '3ème degré',
            default => $this->depth_dominant ?? '-',
        };
    }

    public function getSeverityClassificationAttribute(): string
    {
        $tbsa = $this->total_burn_surface_area;

        if ($tbsa < 10) {
            return 'mineure';
        }

        if ($tbsa < 20) {
            return 'modérée';
        }

        if ($tbsa < 40) {
            return 'sévère';
        }

        return 'critique';
    }
}
