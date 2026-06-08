<?php

namespace Modules\ClinicalRecord\Services;

use App\Models\Patient;
use Modules\ClinicalRecord\Models\ClinicalProcedure;
use Modules\ClinicalRecord\Models\DentalChart;
use Modules\ClinicalRecord\Models\ImagingStudy;
use Modules\ClinicalRecord\Models\PatientQuestionnaireResponse;
use Modules\ClinicalRecord\Models\PatientConsultation;
use Modules\ClinicalRecord\Models\Questionnaire;
use Modules\ClinicalRecord\Models\Prescription;
use Modules\ClinicalRecord\Models\TreatmentPlan;

class ClinicalWorkflowService
{
    public function odontogram(int $patientId): array
    {
        Patient::findOrFail($patientId);

        $chart = DentalChart::forPatient($patientId)->latestFirst()->first();

        if (! $chart) {
            $chart = DentalChart::create([
                'patient_id' => $patientId,
                'version' => 'adult',
                'teeth_status' => [],
                'created_by' => auth()->id(),
            ]);
            $chart->initializeWithDefaults('adult');
            $chart = $chart->fresh();
        }

        return [
            'chart_id' => $chart->id,
            'version' => $chart->version,
            'teeth_status' => $chart->teeth_status,
            'summary' => $chart->getTeethSummary(),
        ];
    }

    public function consultationTypes(): array
    {
        return [
            ['value' => 'bilan', 'label' => 'Bilan'],
            ['value' => 'soins', 'label' => 'Soins'],
            ['value' => 'chirurgie', 'label' => 'Chirurgie'],
            ['value' => 'controle', 'label' => 'Controle'],
        ];
    }

    public function diagnosisCatalog(): array
    {
        return [
            ['code' => 'DX-001', 'label' => 'Douleur dentaire aigue'],
            ['code' => 'DX-002', 'label' => 'Carie dentinaire'],
            ['code' => 'DX-003', 'label' => 'Pulpite irreversibile'],
            ['code' => 'DX-004', 'label' => 'Parodontite chronique'],
            ['code' => 'DX-005', 'label' => 'Pericoronarite'],
            ['code' => 'DX-006', 'label' => 'Infection endodontique'],
            ['code' => 'DX-007', 'label' => 'Urgence traumatique'],
            ['code' => 'DX-008', 'label' => 'Contrôle post-operatoire'],
            ['code' => 'DX-009', 'label' => 'Bilan initial'],
            ['code' => 'DX-010', 'label' => 'Détartrage et prophylaxie'],
        ];
    }

    public function timeline(int $patientId, array $filters = []): array
    {
        Patient::findOrFail($patientId);

        $fromDate = $filters['from'] ?? null;
        $toDate = $filters['to'] ?? null;
        $eventType = $filters['type'] ?? 'all';

        $consultationQuery = PatientConsultation::where('patient_id', $patientId)
            ->with(['practitioner:id,name', 'invoice:id,invoice_number,status,paid_amount,remaining_amount,paid_at']);

        if ($fromDate) {
            $consultationQuery->whereDate('consultation_date', '>=', $fromDate);
        }

        if ($toDate) {
            $consultationQuery->whereDate('consultation_date', '<=', $toDate);
        }

        $consultations = $consultationQuery->orderByDesc('consultation_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (PatientConsultation $consultation): array {
                $displayDiagnosis = $consultation->diagnosis_label ?: $consultation->diagnosis;

                return [
                    'type' => 'consultation',
                    'id' => $consultation->id,
                    'date' => optional($consultation->consultation_date)->toDateString(),
                    'status' => $consultation->consultation_status,
                    'label' => $consultation->consultation_reason ?: 'Consultation',
                    'consultation_type' => $consultation->consultation_type,
                    'diagnosis' => $displayDiagnosis,
                    'practitioner' => $consultation->practitioner?->name,
                    'payment_status' => $consultation->payment_status,
                    'invoice_number' => $consultation->invoice?->invoice_number,
                    'observations' => $consultation->observations ?: $consultation->clinical_exam,
                    'source' => $consultation->source,
                    'searchable' => collect([
                        $consultation->consultation_reason,
                        $displayDiagnosis,
                        $consultation->chief_complaint,
                        $consultation->anamnesis,
                        $consultation->observations,
                        $consultation->clinical_exam,
                        $consultation->prescription,
                        $consultation->recommendations,
                        $consultation->practitioner?->name,
                    ])->filter()->implode(' | '),
                ];
            });

        $procedures = ClinicalProcedure::forPatient($patientId)
            ->with(['specialty:id,name', 'practitioner:id,name'])
            ->orderByDesc('performed_at')
            ->orderByDesc('planned_date')
            ->get()
            ->map(fn (ClinicalProcedure $p) => [
                'type' => 'procedure',
                'id' => $p->id,
                'date' => optional($p->performed_at)->toDateString() ?? optional($p->planned_date)->toDateString(),
                'status' => $p->status,
                'label' => $p->name,
                'tooth_number' => $p->tooth_number,
                'tooth_surfaces' => $p->tooth_surfaces,
                'specialty' => $p->specialty?->name,
                'practitioner' => $p->practitioner?->name,
                'price' => (float) $p->price,
                'consultation_id' => $p->consultation_id,
            ]);

        $plans = TreatmentPlan::forPatient($patientId)
            ->with(['practitioner:id,name'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (TreatmentPlan $plan) => [
                'type' => 'treatment_plan',
                'id' => $plan->id,
                'date' => $plan->created_at->toDateString(),
                'status' => $plan->status,
                'label' => $plan->name,
                'phases' => $plan->phases,
                'practitioner' => $plan->practitioner?->name,
                'estimated_cost' => (float) $plan->total_estimated_cost,
            ]);

        $images = ImagingStudy::where('patient_id', $patientId)
            ->orderByDesc('captured_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ImagingStudy $image) => [
                'type' => 'imaging',
                'id' => $image->id,
                'date' => optional($image->captured_at)->toDateString() ?? $image->created_at->toDateString(),
                'status' => 'available',
                'label' => strtoupper($image->modality),
                'modality' => $image->modality,
                'file_path' => $image->file_path,
            ]);

        $questionnaireResponses = PatientQuestionnaireResponse::where('patient_id', $patientId)
            ->with(['questionnaire:id,name', 'consultation:id,consultation_date,consultation_reason'])
            ->orderByDesc('answered_at')
            ->get()
            ->map(fn (PatientQuestionnaireResponse $response) => [
                'type' => 'questionnaire',
                'id' => $response->id,
                'date' => optional($response->answered_at)->toDateString(),
                'status' => 'answered',
                'label' => $response->questionnaire?->name ?? 'Questionnaire',
                'questionnaire' => $response->questionnaire?->name,
                'consultation' => $response->consultation?->consultation_reason,
                'answers' => $response->answers,
                'practitioner' => $response->practitioner?->name,
                'notes' => $response->notes,
                'searchable' => collect([
                    $response->questionnaire?->name,
                    $response->consultation?->consultation_reason,
                    json_encode($response->answers, JSON_UNESCAPED_UNICODE),
                    $response->notes,
                    $response->practitioner?->name,
                ])->filter()->implode(' | '),
            ]);

        $prescriptions = Prescription::where('patient_id', $patientId)
            ->with(['consultation:id,consultation_date,consultation_reason', 'practitioner:id,name', 'template:id,name'])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Prescription $prescription) => [
                'type' => 'prescription',
                'id' => $prescription->id,
                'date' => optional($prescription->issued_at)->toDateString(),
                'status' => $prescription->status,
                'label' => $prescription->prescription_number,
                'consultation' => $prescription->consultation?->consultation_reason,
                'template' => $prescription->template?->name,
                'practitioner' => $prescription->practitioner?->name,
                'searchable' => collect([
                    $prescription->prescription_number,
                    $prescription->status,
                    $prescription->template?->name,
                    $prescription->consultation?->consultation_reason,
                    $prescription->practitioner?->name,
                    json_encode($prescription->safety_alerts ?? [], JSON_UNESCAPED_UNICODE),
                ])->filter()->implode(' | '),
            ]);

        $items = $consultations->concat($procedures)->concat($plans)->concat($images)->concat($questionnaireResponses)->concat($prescriptions);

        if ($eventType !== 'all') {
            $items = $items->filter(fn (array $item): bool => $item['type'] === $eventType);
        }

        $items = $items->sortByDesc(function (array $item): string {
            return (string) ($item['date'] ?? '');
        })->values();

        return [
            'patient_id' => $patientId,
            'events' => $items,
        ];
    }

    public function storeImagingStudy(int $patientId, array $payload): ImagingStudy
    {
        Patient::findOrFail($patientId);

        return ImagingStudy::create([
            'patient_id' => $patientId,
            'medical_image_id' => $payload['medical_image_id'] ?? null,
            'uploaded_by' => auth()->id(),
            'modality' => $payload['modality'],
            'study_uid' => $payload['study_uid'] ?? null,
            'series_uid' => $payload['series_uid'] ?? null,
            'instance_uid' => $payload['instance_uid'] ?? null,
            'file_path' => $payload['file_path'],
            'mime_type' => $payload['mime_type'] ?? null,
            'file_size_bytes' => $payload['file_size_bytes'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
            'captured_at' => $payload['captured_at'] ?? null,
        ]);
    }

    public function imagingManifest(int $patientId): array
    {
        Patient::findOrFail($patientId);

        $studies = ImagingStudy::where('patient_id', $patientId)
            ->orderByDesc('captured_at')
            ->orderByDesc('created_at')
            ->get();

        return [
            'patient_id' => $patientId,
            'items' => $studies->map(fn (ImagingStudy $study) => [
                'id' => $study->id,
                'modality' => $study->modality,
                'study_uid' => $study->study_uid,
                'series_uid' => $study->series_uid,
                'instance_uid' => $study->instance_uid,
                'mime_type' => $study->mime_type,
                'file_size_bytes' => $study->file_size_bytes,
                'file_path' => $study->file_path,
                'captured_at' => optional($study->captured_at)->toDateTimeString(),
                'metadata' => $study->metadata,
            ]),
        ];
    }
}
