<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specialty extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'default_color',
        'default_duration_minutes',
        'description',
        'is_active',
    ];

    protected $casts = [
        'default_duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get all practitioners with this specialty.
     */
    public function practitioners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'practitioner_specialties')
            ->withPivot('is_primary', 'priority_order')
            ->withTimestamps();
    }

    /**
     * Get all appointment types for this specialty.
     */
    public function appointmentTypes(): HasMany
    {
        return $this->hasMany(\Modules\Scheduling\Models\AppointmentType::class);
    }

    /**
     * Get primary practitioners only.
     */
    public function primaryPractitioners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'practitioner_specialties')
            ->withPivot('is_primary', 'priority_order')
            ->wherePivot('is_primary', true)
            ->withTimestamps();
    }

    /**
     * Scope: Active specialties only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
}
