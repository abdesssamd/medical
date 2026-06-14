<?php

namespace Modules\RIS\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RisOrder extends Model
{
    use HasFactory;

    public const STATUS_ORDONNE = 'ordonne';
    public const STATUS_EN_ATTENTE = 'en_attente';
    public const STATUS_IMAGES_RECUES = 'images_recues';
    public const STATUS_TERMINE = 'termine';
    public const STATUS_ANNULE = 'annule';

    public const PRIORITY_ROUTINE = 'routine';
    public const PRIORITY_URGENT = 'urgent';
    public const PRIORITY_STAT = 'stat';

    protected $table = 'ris_orders';

    protected $fillable = [
        'patient_id',
        'procedure_id',
        'modality_id',
        'equipment_id',
        'accession_number',
        'priority',
        'clinical_indication',
        'requested_by_user_id',
        'orthanc_payload',
        'status',
        'requested_at',
        'scheduled_at',
        'started_at',
        'received_at',
        'completed_at',
        'cancelled_at',
        'cancelled_reason',
    ];

    protected $casts = [
        'orthanc_payload' => 'array',
        'requested_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'received_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(RisProcedure::class, 'procedure_id');
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(RisModality::class, 'modality_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(RisEquipment::class, 'equipment_id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(RisReport::class, 'order_id');
    }

    public function portalAccess(): HasOne
    {
        return $this->hasOne(\Modules\PatientPortal\Models\PatientPortalAccess::class, 'order_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by_user_id');
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ORDONNE => 'Ordonne',
            self::STATUS_EN_ATTENTE => 'En attente',
            self::STATUS_IMAGES_RECUES => 'Images recues',
            self::STATUS_TERMINE => 'Termine',
            self::STATUS_ANNULE => 'Annule',
        ];
    }

    public static function priorityLabels(): array
    {
        return [
            self::PRIORITY_ROUTINE => 'Routine',
            self::PRIORITY_URGENT => 'Urgent',
            self::PRIORITY_STAT => 'STAT',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::priorityLabels()[$this->priority] ?? ucfirst((string) $this->priority);
    }
}
