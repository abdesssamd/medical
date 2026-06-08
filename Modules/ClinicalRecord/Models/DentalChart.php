<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DentalChart extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'version',
        'teeth_status',
        'created_by',
    ];

    protected $casts = [
        'teeth_status' => 'array',
    ];

    /**
     * All adult teeth (FDI notation).
     */
    public const ADULT_TEETH = [
        18, 17, 16, 15, 14, 13, 12, 11, // Upper right quadrant
        21, 22, 23, 24, 25, 26, 27, 28, // Upper left quadrant
        48, 47, 46, 45, 44, 43, 42, 41, // Lower right quadrant
        31, 32, 33, 34, 35, 36, 37, 38, // Lower left quadrant
    ];

    /**
     * All child teeth (FDI notation).
     */
    public const CHILD_TEETH = [
        55, 54, 53, 52, 51, // Upper right quadrant
        61, 62, 63, 64, 65, // Upper left quadrant
        85, 84, 83, 82, 81, // Lower right quadrant
        71, 72, 73, 74, 75, // Lower left quadrant
    ];

    /**
     * Possible tooth statuses.
     */
    public const STATUSES = [
        'present' => 'Present',
        'absent' => 'Absent/Missing',
        'extracted' => 'Extracted',
        'crown' => 'Crown',
        'filling' => 'Filling/Composite',
        'root_canal' => 'Root Canal Treated',
        'implant' => 'Implant',
        'bridge_abutment' => 'Bridge Abutment',
        'decay' => 'Active Decay',
        'fractured' => 'Fractured',
    ];

    /**
     * Get the patient this chart belongs to.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the creator of this chart.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all clinical procedures linked to teeth in this chart.
     */
    public function procedures(): HasMany
    {
        return $this->hasMany(ClinicalProcedure::class, 'patient_id', 'patient_id');
    }

    /**
     * Get the latest status of a specific tooth.
     */
    public function getLatestToothStatus(int $toothNumber): ?array
    {
        return $this->teeth_status[$toothNumber] ?? null;
    }

    /**
     * Update the status of a specific tooth.
     */
    public function updateToothStatus(
        int $toothNumber,
        string $status,
        int $userId,
        ?array $procedureDetails = null
    ): void {
        $teethStatus = $this->teeth_status ?? [];

        $update = [
            'status' => $status,
            'updated_at' => now()->toDateTimeString(),
            'updated_by' => $userId,
        ];

        if ($procedureDetails) {
            $update['procedures'] = $teethStatus[$toothNumber]['procedures'] ?? [];
            $update['procedures'][] = array_merge([
                'type' => $procedureDetails['type'] ?? $status,
                'date' => now()->toDateString(),
                'practitioner_id' => $userId,
            ], $procedureDetails['details'] ?? []);
        }

        $teethStatus[$toothNumber] = $update;

        $this->update(['teeth_status' => $teethStatus]);
    }

    /**
     * Get all procedures recorded for a specific tooth.
     */
    public function getProceduresForTooth(int $toothNumber): array
    {
        return $this->teeth_status[$toothNumber]['procedures'] ?? [];
    }

    /**
     * Initialize chart with default empty status.
     */
    public function initializeWithDefaults(string $version = 'adult'): void
    {
        $teeth = $version === 'child' ? self::CHILD_TEETH : self::ADULT_TEETH;
        $teethStatus = [];

        foreach ($teeth as $toothNumber) {
            $teethStatus[$toothNumber] = [
                'status' => 'present',
                'updated_at' => now()->toDateTimeString(),
                'procedures' => [],
            ];
        }

        $this->update([
            'version' => $version,
            'teeth_status' => $teethStatus,
        ]);
    }

    /**
     * Get a summary of teeth by status.
     */
    public function getTeethSummary(): array
    {
        $summary = [];

        foreach (self::STATUSES as $statusKey => $statusLabel) {
            $teeth = collect($this->teeth_status ?? [])
                ->filter(fn ($data) => $data['status'] === $statusKey)
                ->keys()
                ->values()
                ->all();

            $summary[$statusKey] = [
                'label' => $statusLabel,
                'count' => count($teeth),
                'teeth' => $teeth,
            ];
        }

        return $summary;
    }

    /**
     * Scope: Charts for a specific patient.
     */
    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope: Latest chart first.
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('created_at');
    }
}
