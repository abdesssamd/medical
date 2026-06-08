<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PractitionerAccountingProfile extends Model
{
    protected $fillable = [
        'practitioner_id',
        'organization_id',
        'entity_code',
        'invoice_prefix',
        'currency',
        'default_tax_rate',
        'is_active',
    ];

    protected $casts = [
        'default_tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }
}

