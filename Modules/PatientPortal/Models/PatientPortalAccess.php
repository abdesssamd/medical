<?php

namespace Modules\PatientPortal\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\RIS\Models\RisOrder;
use Modules\RIS\Models\RisReport;

class PatientPortalAccess extends Model
{
    use HasFactory;

    protected $table = 'patient_portal_accesses';

    protected $fillable = [
        'patient_id',
        'order_id',
        'report_id',
        'access_token',
        'access_code_hash',
        'access_code_encrypted',
        'access_code_last4',
        'delivery_channel',
        'delivery_email',
        'delivery_phone',
        'expires_at',
        'verified_at',
        'revoked_at',
        'attempt_count',
        'last_attempt_at',
        'locked_until_at',
        'last_access_at',
        'last_ip',
        'last_user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'locked_until_at' => 'datetime',
        'last_access_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(RisOrder::class, 'order_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(RisReport::class, 'report_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PatientPortalAccessLog::class, 'patient_portal_access_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isLocked(): bool
    {
        return $this->locked_until_at !== null && $this->locked_until_at->isFuture();
    }
}
