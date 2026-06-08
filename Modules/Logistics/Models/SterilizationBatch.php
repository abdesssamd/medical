<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SterilizationBatch extends Model
{
    protected $fillable = [
        'organization_id',
        'batch_code',
        'sterilized_at',
        'expires_at',
        'sterilizer_cycle',
        'operator_user_id',
        'status',
        'sterility_validity_days',
        'bowie_dick_passed',
        'helix_passed',
        'validated_at',
        'notes',
    ];

    protected $casts = [
        'sterilized_at' => 'datetime',
        'expires_at' => 'datetime',
        'validated_at' => 'datetime',
        'sterility_validity_days' => 'integer',
        'bowie_dick_passed' => 'boolean',
        'helix_passed' => 'boolean',
    ];

    public function pouches(): HasMany
    {
        return $this->hasMany(SterilizationPouch::class, 'batch_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'operator_user_id');
    }
}

