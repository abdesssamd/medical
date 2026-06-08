<?php

namespace Modules\Rehab\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RehabSession extends Model
{
    protected $fillable = [
        'prescription_id',
        'physiotherapist_id',
        'session_number',
        'session_date',
        'pain_score',
        'notes',
        'exercises_performed',
        'status',
        'duration_minutes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'pain_score' => 'integer',
        'exercises_performed' => 'array',
        'duration_minutes' => 'integer',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(RehabPrescription::class, 'prescription_id');
    }

    public function physiotherapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'physiotherapist_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'planned' => 'Planifiée',
            'completed' => 'Réalisée',
            'cancelled' => 'Annulée',
            'missed' => 'Manquée',
            default => $this->status,
        };
    }

    public function getPainLabelAttribute(): string
    {
        if ($this->pain_score === null) {
            return 'Non évaluée';
        }

        return match (true) {
            $this->pain_score === 0 => 'Aucune douleur',
            $this->pain_score <= 3 => 'Douleur légère',
            $this->pain_score <= 6 => 'Douleur modérée',
            $this->pain_score <= 9 => 'Douleur intense',
            $this->pain_score === 10 => 'Douleur maximale',
            default => 'Inconnue',
        };
    }
}
