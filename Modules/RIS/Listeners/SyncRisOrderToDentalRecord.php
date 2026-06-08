<?php

namespace Modules\RIS\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\ClinicalRecord\Models\ImagingStudy;
use Modules\RIS\Events\RisOrderCompleted;

class SyncRisOrderToDentalRecord
{
    /**
     * Point d'entree de synchronisation vers le dossier dentaire principal.
     */
    public function handle(RisOrderCompleted $event): void
    {
        $order = $event->order->loadMissing(['patient', 'modality']);
        $tags = (array) data_get($order->orthanc_payload ?? [], 'webhook_tags', []);

        $studyUid = (string) ($tags['StudyInstanceUID'] ?? $tags['0020,000D'] ?? '');
        $seriesUid = (string) ($tags['SeriesInstanceUID'] ?? $tags['0020,000E'] ?? '');
        $instanceUid = (string) ($tags['SOPInstanceUID'] ?? $tags['0008,0018'] ?? '');
        $orthancId = (string) (data_get($order->orthanc_payload ?? [], 'webhook_last_body.OrthancID')
            ?? data_get($order->orthanc_payload ?? [], 'webhook_last_body.id')
            ?? '');

        ImagingStudy::query()->updateOrCreate(
            [
                'patient_id' => $order->patient_id,
                'study_uid' => $studyUid !== '' ? $studyUid : null,
                'instance_uid' => $instanceUid !== '' ? $instanceUid : null,
            ],
            [
                'modality' => 'dicom',
                'series_uid' => $seriesUid !== '' ? $seriesUid : null,
                'file_path' => $orthancId !== '' ? 'orthanc://instances/'.$orthancId : 'orthanc://study/'.$order->id,
                'mime_type' => 'application/dicom',
                'metadata' => [
                    'source' => 'ris',
                    'order_id' => $order->id,
                    'modality_name' => $order->modality?->name,
                ],
                'captured_at' => now(),
            ]
        );

        Log::info('ris.order.completed.sync', [
            'order_id' => $order->id,
            'patient_id' => $order->patient_id,
            'status' => $order->status,
        ]);
    }
}
