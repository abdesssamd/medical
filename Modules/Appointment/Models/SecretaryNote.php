<?php

namespace Modules\Appointment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecretaryNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'created_by',
        'tag',
        'message',
        'priority',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public const TAG_DOCUMENT_MISSING = 'document_missing';
    public const TAG_INSURANCE_VERIFY = 'insurance_verify';
    public const TAG_CONSENT_PENDING = 'consent_pending';
    public const TAG_PAYMENT_ISSUE = 'payment_issue';
    public const TAG_URGENT = 'urgent';
    public const TAG_OTHER = 'other';

    public const PRIORITY_CRITICAL = 'critical';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_NORMAL = 'normal';

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
