<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareAlert extends Model
{
    protected $fillable = [
        'type',
        'severity',
        'title',
        'message',
        'patient_id',
        'reference_type',
        'reference_id',
        'alerted_at',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'alerted_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}

