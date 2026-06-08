<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthQuestionnaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'validated_by',
        'filled_on',
        'answers',
        'risk_tags',
        'has_critical_risk',
        'critical_notes',
    ];

    protected $casts = [
        'filled_on' => 'date',
        'answers' => 'array',
        'risk_tags' => 'array',
        'has_critical_risk' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
