<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'type',
        'equipment',
        'is_active',
    ];

    protected $casts = [
        'equipment' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the organization this room belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all practitioners assigned to this room.
     */
    public function practitioners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'practitioner_rooms')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get all availability blocks for this room.
     */
    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(\Modules\Scheduling\Models\AvailabilityBlock::class);
    }

    /**
     * Scope: Active rooms only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Rooms with specific equipment.
     */
    public function scopeHasEquipment($query, string $equipment)
    {
        return $query->whereJsonContains('equipment', $equipment);
    }

    /**
     * Check if room has specific equipment.
     */
    public function hasEquipment(string $equipment): bool
    {
        return in_array($equipment, $this->equipment ?? []);
    }

    /**
     * Check if room is available at a given time.
     */
    public function isAvailableAt(string $date, string $startTime, string $endTime): bool
    {
        $conflictingBlock = $this->availabilityBlocks()
            ->where('date', $date)
            ->where('is_booked', true)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function ($q2) use ($startTime, $endTime) {
                      $q2->where('start_time', '<=', $startTime)
                         ->where('end_time', '>=', $endTime);
                  });
            })
            ->exists();

        return ! $conflictingBlock;
    }
}
