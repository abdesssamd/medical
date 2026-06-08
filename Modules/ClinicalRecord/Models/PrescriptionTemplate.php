<?php

namespace Modules\ClinicalRecord\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrescriptionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'context',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionTemplateItem::class);
    }
}
