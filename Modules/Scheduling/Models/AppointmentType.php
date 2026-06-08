<?php

namespace Modules\Scheduling\Models;

use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'specialty_id',
        'code',
        'name',
        'name_ar',
        'duration_minutes',
        'base_price',
        'requires_follow_up',
        'follow_up_days',
        'required_equipment',
        'required_material',
        'description',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'base_price' => 'decimal:2',
        'requires_follow_up' => 'boolean',
        'follow_up_days' => 'integer',
        'required_equipment' => 'array',
        'required_material' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the specialty this appointment type belongs to.
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /**
     * Get all appointments using this type.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(\Modules\Appointment\Models\Appointment::class);
    }

    /**
     * Get all availability blocks for this type.
     */
    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(AvailabilityBlock::class);
    }

    /**
     * Scope: Active appointment types only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by specialty.
     */
    public function scopeForSpecialty($query, int $specialtyId)
    {
        return $query->where('specialty_id', $specialtyId);
    }

    /**
     * Scope: Search by name or code.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }

    /**
     * Check if this type requires specific equipment.
     */
    public function requiresEquipment(string $equipment): bool
    {
        return in_array($equipment, $this->required_equipment ?? []);
    }

    /**
     * Check if this type requires specific material.
     */
    public function requiresMaterial(string $material): bool
    {
        return in_array($material, $this->required_material ?? []);
    }

    /**
     * Get the follow-up appointment type if applicable.
     */
    public function needsFollowUp(): bool
    {
        return $this->requires_follow_up;
    }

    /**
     * Get the number of days until follow-up.
     */
    public function getFollowUpDays(): int
    {
        return $this->follow_up_days ?? 0;
    }
}
