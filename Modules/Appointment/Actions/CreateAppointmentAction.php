<?php

namespace Modules\Appointment\Actions;

use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Services\AvailabilityService;
use Modules\Billing\Models\Invoice;
use Modules\ClinicalRecord\Models\PatientConsultation;

class CreateAppointmentAction
{
    public function __construct(private readonly AvailabilityService $availabilityService)
    {
    }

    /**
     * Execute the appointment creation.
     * 
     * @param array $payload {
     *     @type int $professional_id
     *     @type int|null $patient_id
     *     @type string|null $patient_name
     *     @type string|null $patient_phone
     *     @type string|null $patient_email
     *     @type string $appointment_date
     *     @type string $start_time
     *     @type int|null $appointment_type_id
     *     @type int|null $parent_appointment_id
     *     @type string|null $notes
     * }
     * @param int $creatorId
     * @return Appointment
     */
    public function execute(array $payload, int $creatorId): Appointment
    {
        $date = Carbon::parse($payload['appointment_date']);
        $startTime = Carbon::parse($payload['start_time'])->format('H:i:s');

        $this->availabilityService->ensureSlotIsAvailable(
            (int) $payload['professional_id'],
            $date,
            $startTime
        );

        $planning = $this->availabilityService->resolvePlanningOrFail((int) $payload['professional_id'], $date);
        $endTime = Carbon::parse($date->toDateString().' '.$startTime)
            ->addMinutes((int) $planning->consultation_minutes)
            ->format('H:i:s');

        // Resolve or create patient if patient_id not provided but patient_name is
        $patientId = $payload['patient_id'] ?? null;
        if (! $patientId && ! empty($payload['patient_name'])) {
            $patientId = $this->resolveOrCreatePatient($payload);
        }

        return DB::transaction(function () use ($payload, $creatorId, $startTime, $endTime, $patientId, $planning): Appointment {
            $appointment = Appointment::create([
                'professional_id' => (int) $payload['professional_id'],
                'secretary_id' => $payload['secretary_id'] ?? null,
                'created_by' => $creatorId,
                'patient_id' => $patientId,
                'appointment_type_id' => $payload['appointment_type_id'] ?? null,
                'planning_id' => $planning->id,
                'parent_appointment_id' => $payload['parent_appointment_id'] ?? null,
                'patient_name' => $payload['patient_name'] ?? null,
                'patient_phone' => $payload['patient_phone'] ?? null,
                'patient_email' => $payload['patient_email'] ?? null,
                'consultation_reason' => $payload['consultation_reason'] ?? null,
                'consultation_type' => $payload['consultation_type'] ?? null,
                'appointment_date' => $payload['appointment_date'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'booked',
                'follow_up_status' => ! empty($payload['parent_appointment_id']) ? 'scheduled' : null,
                'notes' => $payload['notes'] ?? null,
            ]);

            if ($patientId) {
                $invoice = Invoice::where('patient_id', $patientId)
                    ->whereDate('invoice_date', $payload['appointment_date'])
                    ->latest('id')
                    ->first();

                PatientConsultation::create([
                    'patient_id' => $patientId,
                    'appointment_id' => $appointment->id,
                    'planning_id' => $planning->id,
                    'practitioner_id' => (int) $payload['professional_id'],
                    'consultation_date' => $payload['appointment_date'],
                    'consultation_reason' => $payload['consultation_reason'] ?? $payload['notes'] ?? null,
                    'consultation_type' => $payload['consultation_type'] ?? 'bilan',
                    'consultation_status' => 'attendu',
                    'chief_complaint' => $payload['consultation_reason'] ?? $payload['notes'] ?? null,
                    'source' => 'appointment',
                    'invoice_id' => $invoice?->id,
                    'payment_status' => $invoice?->status === Invoice::STATUS_PAID
                        ? 'paid'
                        : ($invoice ? 'billed' : 'unbilled'),
                    'paid_at' => $invoice?->paid_at,
                ]);
            }

            return $appointment;
        });
    }

    /**
     * Resolve an existing patient by phone/email or create a new one.
     */
    private function resolveOrCreatePatient(array $payload): ?int
    {
        if (empty($payload['patient_phone']) && empty($payload['patient_email'])) {
            return null;
        }

        // Try to find existing patient
        $existingPatient = Patient::query()
            ->when(! empty($payload['patient_phone']), fn ($q) => $q->where('phone', $payload['patient_phone']))
            ->when(! empty($payload['patient_email']), fn ($q) => $q->orWhere('email', $payload['patient_email']))
            ->first();

        if ($existingPatient) {
            return $existingPatient->id;
        }

        // Create new patient
        $nameParts = explode(' ', $payload['patient_name'], 2);
        
        $patient = Patient::create([
            'first_name' => $nameParts[0] ?? $payload['patient_name'],
            'last_name' => $nameParts[1] ?? '',
            'phone' => $payload['patient_phone'] ?? null,
            'email' => $payload['patient_email'] ?? null,
            'date_of_birth' => now()->subYears(30), // Default, will be updated later
        ]);

        return $patient->id;
    }
}
