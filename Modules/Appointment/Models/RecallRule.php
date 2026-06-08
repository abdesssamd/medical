<?php

namespace Modules\Appointment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecallRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'trigger_type',
        'trigger_value',
        'interval_days',
        'is_active',
        'channels',
    ];

    protected $casts = [
        'interval_days' => 'integer',
        'is_active' => 'boolean',
        'channels' => 'array',
    ];

    public function recalls(): HasMany
    {
        return $this->hasMany(PatientRecall::class);
    }
}
