<?php

namespace Modules\PatientPortal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientPortalAccessLog extends Model
{
    use HasFactory;

    protected $table = 'patient_portal_access_logs';

    protected $fillable = [
        'patient_portal_access_id',
        'event_type',
        'ip_address',
        'user_agent',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function access(): BelongsTo
    {
        return $this->belongsTo(PatientPortalAccess::class, 'patient_portal_access_id');
    }
}
