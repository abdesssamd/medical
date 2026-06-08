<?php

namespace Modules\Rehab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RehabEvaluation extends Model
{
    protected $fillable = [
        'prescription_id',
        'type',
        'evaluation_date',
        'goniometry',
        'muscle_testing',
        'functional_tests',
        'notes',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'goniometry' => 'array',
        'muscle_testing' => 'array',
        'functional_tests' => 'array',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(RehabPrescription::class, 'prescription_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'initial' => 'Bilan initial',
            'intermediate' => 'Bilan intermédiaire',
            'final' => 'Bilan final',
            default => $this->type,
        };
    }
}
