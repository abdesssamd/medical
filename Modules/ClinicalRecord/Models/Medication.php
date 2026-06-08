<?php

namespace Modules\ClinicalRecord\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'strength',
        'forms',
        'default_unit',
        'default_frequency',
        'default_duration_days',
        'allergen_keywords',
        'contraindication_tags',
        'interaction_keywords',
        'is_active',
    ];

    protected $casts = [
        'forms' => 'array',
        'allergen_keywords' => 'array',
        'contraindication_tags' => 'array',
        'interaction_keywords' => 'array',
        'default_duration_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function templateItems(): HasMany
    {
        return $this->hasMany(PrescriptionTemplateItem::class);
    }
}
