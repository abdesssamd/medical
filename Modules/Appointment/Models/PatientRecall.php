<?php

namespace Modules\Appointment\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientRecall extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'recall_rule_id',
        'reason',
        'due_date',
        'status',
        'last_notified_at',
        'meta',
    ];

    protected $casts = [
        'due_date' => 'date',
        'last_notified_at' => 'date',
        'meta' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(RecallRule::class, 'recall_rule_id');
    }
}
