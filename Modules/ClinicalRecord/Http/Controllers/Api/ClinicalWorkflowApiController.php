<?php

namespace Modules\ClinicalRecord\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ClinicalRecord\Services\ClinicalWorkflowService;

class ClinicalWorkflowApiController extends Controller
{
    public function __construct(private readonly ClinicalWorkflowService $clinicalWorkflowService)
    {
    }

    public function odontogram(int $patientId): JsonResponse
    {
        return response()->json(
            $this->clinicalWorkflowService->odontogram($patientId)
        );
    }

    public function timeline(int $patientId): JsonResponse
    {
        return response()->json(
            $this->clinicalWorkflowService->timeline($patientId)
        );
    }

    public function storeImagingStudy(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'medical_image_id' => ['nullable', 'integer', 'exists:medical_images,id'],
            'modality' => ['required', 'string', 'in:xray,cbct,stl,dicom'],
            'study_uid' => ['nullable', 'string', 'max:191'],
            'series_uid' => ['nullable', 'string', 'max:191'],
            'instance_uid' => ['nullable', 'string', 'max:191'],
            'file_path' => ['required', 'string', 'max:500'],
            'mime_type' => ['nullable', 'string', 'max:191'],
            'file_size_bytes' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'captured_at' => ['nullable', 'date'],
        ]);

        $study = $this->clinicalWorkflowService->storeImagingStudy($patientId, $validated);

        return response()->json([
            'message' => 'Etude imagerie enregistree.',
            'study' => $study,
        ], 201);
    }

    public function imagingManifest(int $patientId): JsonResponse
    {
        return response()->json(
            $this->clinicalWorkflowService->imagingManifest($patientId)
        );
    }
}

