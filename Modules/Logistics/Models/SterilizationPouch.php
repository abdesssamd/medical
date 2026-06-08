<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SterilizationPouch extends Model
{
    protected $fillable = [
        'batch_id',
        'pouch_code',
        'instrument_set_name',
        'status',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SterilizationBatch::class, 'batch_id');
    }

    public function trace(): HasOne
    {
        return $this->hasOne(PatientSterilizationTrace::class, 'sterilization_pouch_id');
    }
}

