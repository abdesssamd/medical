<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RIS\Models\RisOrder;
use Modules\RIS\Services\OrthancService;

class OrthancWebhookController extends Controller
{
    public function handleStoredInstance(Request $request, OrthancService $orthancService): JsonResponse
    {
        $expectedToken = (string) config('ris.orthanc.webhook_token', '');
        if ($expectedToken !== '') {
            $providedToken = (string) ($request->header('X-Orthanc-Token') ?: $request->bearerToken() ?: $request->query('token', ''));
            if (! hash_equals($expectedToken, $providedToken)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        $payload = $request->all();
        $patientIdentifier = $this->extractPatientIdentifier($payload);
        $accessionNumber = $this->extractAccessionNumber($payload);

        if ($patientIdentifier === '') {
            $orthancInstanceId = (string) ($request->input('OrthancID')
                ?? $request->input('id')
                ?? $request->input('resource_id')
                ?? '');

            if ($orthancInstanceId !== '') {
                $tagsResult = $orthancService->getInstanceSimplifiedTags($orthancInstanceId);
                if (! ($tagsResult['ok'] ?? false)) {
                    return response()->json([
                        'message' => 'Unable to read instance tags from Orthanc',
                        'orthanc_id' => $orthancInstanceId,
                    ], 502);
                }

                $tags = (array) ($tagsResult['data'] ?? []);
                $patientIdentifier = $this->extractPatientIdentifier($tags);
                $accessionNumber = $accessionNumber !== '' ? $accessionNumber : $this->extractAccessionNumber($tags);
            }
        }

        if ($patientIdentifier === '') {
            return response()->json(['message' => 'Missing PatientID in DICOM payload'], 422);
        }

        $patient = Patient::query()
            ->where('medical_record_number', $patientIdentifier)
            ->first();

        if (! $patient) {
            return response()->json([
                'message' => 'Patient not found from PatientID',
                'patient_id' => $patientIdentifier,
            ], 404);
        }

        $orderQuery = RisOrder::query()
            ->where('patient_id', $patient->id)
            ->whereIn('status', [
                RisOrder::STATUS_ORDONNE,
                RisOrder::STATUS_EN_ATTENTE,
            ]);

        $order = null;
        if ($accessionNumber !== '') {
            $order = (clone $orderQuery)
                ->where('accession_number', $accessionNumber)
                ->latest('id')
                ->first();
        }

        if (! $order) {
            $order = $orderQuery
                ->latest('requested_at')
                ->latest('id')
                ->first();
        }

        if (! $order) {
            return response()->json([
                'message' => 'No active RIS order found for patient',
                'patient_id' => $patient->id,
            ], 404);
        }

        $result = $orthancService->reconcileOrderFromOrthanc($order);

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'message' => $result['message'] ?? 'Unable to reconcile RIS order from Orthanc',
                'order_id' => $order->id,
                'patient_id' => $patient->id,
            ], 502);
        }

        if (! ($result['matched'] ?? false)) {
            return response()->json([
                'message' => 'No matching Orthanc study found for today',
                'order_id' => $order->id,
                'patient_id' => $patient->id,
                'status' => $order->status,
            ], 202);
        }

        return response()->json([
            'message' => 'RIS order updated to images_recues',
            'order_id' => $order->id,
            'patient_id' => $patient->id,
            'status' => $order->status,
            'study' => $result['study'] ?? null,
        ]);
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
}
