<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyRequest extends Model
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'patient_id',
        'requested_by',
        'prescribing_physician_id',
        'exam_type',
        'anatomical_region',
        'priority',
        'clinical_reason',
        'scheduled_station_ae_title',
        'target_modality',
        'requested_procedure_description',
        'accession_number',
        'study_instance_uid',
        'workflow_status',
        'orthanc_worklist_id',
        'worklist_file_path',
        'orthanc_payload',
        'requested_at',
        'started_at',
        'received_at',
        'completed_at',
    ];

    protected $casts = [
        'orthanc_payload' => 'array',
        'requested_at' => 'datetime',
        'started_at' => 'datetime',
        'received_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (RadiologyRequest $request): void {
            if (empty($request->accession_number)) {
                $request->accession_number = self::generateAccessionNumber();
            }

            if (empty($request->study_instance_uid)) {
                $request->study_instance_uid = self::generateStudyInstanceUid();
            }

            if (empty($request->workflow_status)) {
                $request->workflow_status = self::STATUS_REQUESTED;
            }

            if (empty($request->requested_at)) {
                $request->requested_at = now();
            }
        });
    }

    public static function generateAccessionNumber(): string
    {
        $prefix = 'ACC'.now()->format('Ymd');

        do {
            $candidate = $prefix.sprintf('%06d', random_int(1, 999999));
        } while (self::query()->where('accession_number', $candidate)->exists());

        return $candidate;
    }

    public static function generateStudyInstanceUid(): string
    {
        $root = (string) config('services.orthanc.dicom_uid_root', '1.2.826.0.1.3680043.10.5432');
        $ts = now()->format('YmdHis');

        return trim($root, '.').'.'.$ts.'.'.random_int(1000, 9999).'.'.random_int(1000, 9999);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function prescribingPhysician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prescribing_physician_id');
    }
}
