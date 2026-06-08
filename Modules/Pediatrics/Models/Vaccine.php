<?php

namespace Modules\Pediatrics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vaccine extends Model
{
    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'disease',
        'disease_ar',
        'recommended_age_months',
        'dose_number',
        'total_doses',
        'route',
        'site',
        'is_mandatory',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function vaccinationRecords(): HasMany
    {
        return $this->hasMany(VaccinationRecord::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->total_doses > 1) {
            return $this->name . ' (D' . $this->dose_number . '/' . $this->total_doses . ')';
        }

        return $this->name;
    }
}
