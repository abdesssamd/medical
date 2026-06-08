<?php

namespace Modules\Rehab\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RehabPrescription extends Model
{
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'diagnosis',
        'prescribed_sessions_count',
        'objectives',
        'status',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'prescribed_sessions_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(RehabEvaluation::class, 'prescription_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(RehabSession::class, 'prescription_id');
    }

    public function completedSessions(): HasMany
    {
        return $this->sessions()->where('status', 'completed');
    }

    public function getCompletedSessionsCountAttribute(): int
    {
        return $this->completedSessions()->count();
    }

    public function getRemainingSessionsAttribute(): int
    {
        return max(0, $this->prescribed_sessions_count - $this->completed_sessions_count);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->prescribed_sessions_count === 0) {
            return 0;
        }

        return round(($this->completed_sessions_count / $this->prescribed_sessions_count) * 100, 1);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'in_progress' => 'En cours',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
            default => $this->status,
        };
    }

    public function canAddSession(): bool
    {
        return $this->status === 'in_progress' && $this->remaining_sessions > 0;
    }
}
