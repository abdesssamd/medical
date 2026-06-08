<?php

namespace Modules\RIS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RIS\Models\RisOrder;
use Modules\RIS\Services\OrthancService;

class RisWebhookController extends Controller
{
    /**
     * Recoit les notifications Orthanc OnStoredInstance et met a jour le statut RIS.
     */
    public function orthancStoredInstance(Request $request, OrthancService $orthancService): JsonResponse
    {
        $expectedToken = (string) config('ris.orthanc.webhook_token', '');
        if ($expectedToken !== '') {
            $providedToken = (string) ($request->header('X-Orthanc-Token') ?: $request->bearerToken() ?: $request->query('token', ''));
            if (! hash_equals($expectedToken, $providedToken)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        $directAccessionNumber = $this->extractAccessionNumber($request->all());
        $directPatientIdentifier = $this->extractPatientIdentifier($request->all());

        if ($directAccessionNumber !== '') {
            $order = RisOrder::query()->where('accession_number', $directAccessionNumber)->latest('id')->first();

            if (! $order) {
                return response()->json([
                    'message' => 'RIS order not found from accession number',
                    'accession_number' => $directAccessionNumber,
                ], 404);
            }

            return $this->markOrderImagesReceived($order, $request, (array) $request->input('tags', []));
        }

        if ($directPatientIdentifier !== '') {
            $patient = Patient::query()
                ->where('medical_record_number', $directPatientIdentifier)
                ->first();

            if ($patient) {
                $order = RisOrder::query()
                    ->where('patient_id', $patient->id)
                    ->whereIn('status', [
                        RisOrder::STATUS_ORDONNE,
                        RisOrder::STATUS_EN_ATTENTE,
                    ])
                    ->latest('requested_at')
                    ->latest('id')
                    ->first();

                if ($order) {
                    return $this->markOrderImagesReceived($order, $request, (array) $request->input('tags', []), 'RIS order updated from PatientID');
                }
            }
        }

        $orthancInstanceId = (string) ($request->input('OrthancID')
            ?? $request->input('id')
            ?? $request->input('resource_id')
            ?? '');

        if ($orthancInstanceId === '') {
            return response()->json(['message' => 'Missing Orthanc instance id'], 422);
        }

        $tagsResult = $orthancService->getInstanceSimplifiedTags($orthancInstanceId);
        if (! ($tagsResult['ok'] ?? false)) {
            return response()->json([
                'message' => 'Unable to read instance tags from Orthanc',
                'orthanc_id' => $orthancInstanceId,
            ], 502);
        }

        $tags = (array) ($tagsResult['data'] ?? []);
        $patientDicomId = (string) ($tags['PatientID'] ?? $tags['0010,0020'] ?? '');
        $accessionNumber = (string) ($tags['AccessionNumber'] ?? $tags['0008,0050'] ?? '');

        if ($patientDicomId === '' && $accessionNumber === '') {
            return response()->json(['message' => 'Missing PatientID and AccessionNumber in DICOM tags'], 422);
        }

        if ($accessionNumber !== '') {
            $order = RisOrder::query()->where('accession_number', $accessionNumber)->latest('id')->first();

            if ($order) {
                return $this->markOrderImagesReceived($order, $request, $tags, 'RIS order updated from accession number');
            }
        }

        $patient = Patient::query()
            ->where('medical_record_number', $patientDicomId)
            ->first();

        if (! $patient) {
            return response()->json([
                'message' => 'Patient not found from PatientID',
                'patient_id' => $patientDicomId,
            ], 404);
        }

        $order = RisOrder::query()
            ->where('patient_id', $patient->id)
            ->whereIn('status', [
                RisOrder::STATUS_ORDONNE,
                RisOrder::STATUS_EN_ATTENTE,
            ])
            ->latest('id')
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'No active RIS order found for patient',
                'patient_id' => $patient->id,
            ], 404);
        }

        return $this->markOrderImagesReceived($order, $request, $tags);
    }

    private function extractAccessionNumber(array $payload): string
    {
        foreach (['accession_number', 'AccessionNumber', 'accession', '0008,0050'] as $key) {
            $value = (string) data_get($payload, $key, '');
            if ($value !== '') {
                return $value;
            }
        }

        foreach (['tags.AccessionNumber', 'tags.0008,0050', 'MainDicomTags.AccessionNumber'] as $key) {
            $value = (string) data_get($payload, $key, '');
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function extractPatientIdentifier(array $payload): string
    {
        foreach (['PatientID', 'patient_id', '0010,0020'] as $key) {
            $value = (string) data_get($payload, $key, '');
            if ($value !== '') {
                return $value;
            }
        }

        foreach (['tags.PatientID', 'tags.0010,0020', 'MainDicomTags.PatientID'] as $key) {
            $value = (string) data_get($payload, $key, '');
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function extractStudyUid(array $payload, array $tags): ?string
    {
        foreach ([
            'study_uid',
            'StudyInstanceUID',
            '0020,000D',
            'ParentStudy',
            'StudyID',
            'tags.StudyInstanceUID',
            'tags.0020,000D',
            'MainDicomTags.StudyInstanceUID',
        ] as $key) {
            $value = (string) data_get($payload, $key, data_get($tags, $key, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function markOrderImagesReceived(
        RisOrder $order,
        Request $request,
        array $tags = [],
        string $message = 'RIS order updated to images_recues'
    ): JsonResponse {
        $payload = (array) ($order->orthanc_payload ?? []);
        $payload['webhook_last_event'] = 'on_stored_instance';
        $payload['webhook_last_body'] = $request->all();
        $payload['webhook_tags'] = $tags;
        $payload['webhook_last_at'] = now()->toIso8601String();
        $payload['study_uid'] = $this->extractStudyUid($request->all(), $tags) ?? ($payload['study_uid'] ?? null);

        $order->forceFill([
            'status' => RisOrder::STATUS_IMAGES_RECUES,
            'received_at' => $order->received_at ?? now(),
            'orthanc_payload' => $payload,
        ])->save();

        return response()->json([
            'message' => $message,
            'order_id' => $order->id,
            'patient_id' => $order->patient_id,
            'status' => $order->status,
            'study_uid' => $payload['study_uid'] ?? null,
        ]);
    }
}
