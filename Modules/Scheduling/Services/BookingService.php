<?php

namespace Modules\Scheduling\Services;

use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Appointment\Models\Appointment;
use Modules\Scheduling\Models\AppointmentType;
use Modules\Scheduling\Models\AvailabilityBlock;

class BookingService
{
    public function __construct(
        private readonly AvailabilityService $availabilityService
    ) {
    }

    /**
     * Book an appointment with full validation and resource allocation.
     */
    public function bookAppointment(array $data, int $creatorId): Appointment
    {
        $practitionerId = (int) $data['professional_id'];
        $date = Carbon::parse($data['appointment_date']);
        $startTime = Carbon::parse($data['start_time'])->format('H:i:s');
        $appointmentTypeId = $data['appointment_type_id'] ?? null;

        // Validate availability
        $this->availabilityService->ensureSlotIsAvailable($practitionerId, $date, $startTime);

        // Resolve or create patient
        $patientId = $data['patient_id'] ?? $this->resolveOrCreatePatient($data);

        // Determine appointment duration and end time
        $durationMinutes = $this->determineDuration($practitionerId, $date, $appointmentTypeId);
        $endTime = Carbon::parse($date->toDateString().' '.$startTime)
            ->addMinutes($durationMinutes)
            ->format('H:i:s');

        // Allocate room if needed
        $roomId = $data['room_id'] ?? $this->allocateRoom($practitionerId, $date, $startTime, $endTime);

        return DB::transaction(function () use (
            $data, $creatorId, $patientId, $startTime, $endTime, $durationMinutes, $roomId, $appointmentTypeId
        ): Appointment {
            $appointment = Appointment::create([
                'professional_id' => (int) $data['professional_id'],
                'secretary_id' => $data['secretary_id'] ?? null,
                'created_by' => $creatorId,
                'patient_id' => $patientId,
                'appointment_type_id' => $appointmentTypeId,
                'parent_appointment_id' => $data['parent_appointment_id'] ?? null,
                'patient_name' => $data['patient_name'] ?? null,
                'patient_phone' => $data['patient_phone'] ?? null,
                'patient_email' => $data['patient_email'] ?? null,
                'appointment_date' => $data['appointment_date'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'booked',
                'follow_up_status' => ! empty($data['parent_appointment_id']) ? 'scheduled' : null,
                'notes' => $data['notes'] ?? null,
                'room_id' => $roomId,
            ]);

            // Mark availability block as booked
            $this->markBlockAsBooked($appointment);

            // Schedule follow-up if needed
            if ($appointmentTypeId) {
                $this->scheduleFollowUpIfNeeded($appointment);
            }

            Log::info('appointment.booked', [
                'appointment_id' => $appointment->id,
                'patient_id' => $patientId,
                'practitioner_id' => $appointment->professional_id,
                'date' => $appointment->appointment_date->toDateString(),
            ]);

            return $appointment;
        });
    }

    /**
     * Cancel an appointment and free resources.
     */
    public function cancelAppointment(Appointment $appointment, int $actorId, ?string $reason = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $actorId, $reason): Appointment {
            $appointment->update([
                'status' => 'cancelled',
                'notes' => $appointment->notes
                    ? $appointment->notes."\n[Annulé par #{$actorId}: {$reason}]"
                    : "[Annulé par #{$actorId}: {$reason}]",
            ]);

            // Free the availability block
            $this->markBlockAsAvailable($appointment);

            // Cancel follow-ups
            $appointment->followUpAppointments()
                ->where('status', 'booked')
                ->update(['status' => 'cancelled']);

            Log::info('appointment.cancelled', [
                'appointment_id' => $appointment->id,
                'actor_id' => $actorId,
                'reason' => $reason,
            ]);

            return $appointment->fresh();
        });
    }

    /**
     * Mark an appointment as no-show.
     */
    public function markNoShow(Appointment $appointment, int $actorId): Appointment
    {
        return DB::transaction(function () use ($appointment, $actorId): Appointment {
            $appointment->update([
                'status' => 'no_show',
            ]);

            // Free the block for future bookings
            $this->markBlockAsAvailable($appointment);

            Log::info('appointment.no_show', [
                'appointment_id' => $appointment->id,
                'actor_id' => $actorId,
            ]);

            return $appointment->fresh();
        });
    }

    /**
     * Determine the duration of an appointment.
     */
    private function determineDuration(int $practitionerId, Carbon $date, ?int $appointmentTypeId): int
    {
        if ($appointmentTypeId) {
            $appointmentType = AppointmentType::find($appointmentTypeId);
            if ($appointmentType) {
                return $appointmentType->duration_minutes;
            }
        }

        // Fallback to planning or default
        $planning = \Modules\Appointment\Models\Planning::where('professional_id', $practitionerId)
            ->where('day_of_week', (int) $date->dayOfWeek)
            ->where('is_active', true)
            ->first();

        return $planning?->consultation_minutes ?? config('appointment.default_consultation_minutes', 20);
    }

    /**
     * Allocate a room for the appointment.
     */
    private function allocateRoom(int $practitionerId, Carbon $date, string $startTime, string $endTime): ?int
    {
        // Get practitioner's primary room
        $user = User::find($practitionerId);
        $primaryRoom = $user->rooms()->wherePivot('is_primary', true)->first();

        if ($primaryRoom && $primaryRoom->isAvailableAt($date->toDateString(), $startTime, $endTime)) {
            return $primaryRoom->id;
        }

        // Find any available room
        $availableRoom = Room::whereHas('practitioners', function ($q) use ($practitionerId) {
            $q->where('user_id', $practitionerId);
        })->get()->first(function ($room) use ($date, $startTime, $endTime) {
            return $room->isAvailableAt($date->toDateString(), $startTime, $endTime);
        });

        return $availableRoom?->id;
    }

    /**
     * Mark the corresponding availability block as booked.
     */
    private function markBlockAsBooked(Appointment $appointment): void
    {
        $block = AvailabilityBlock::where('practitioner_id', $appointment->professional_id)
            ->where('date', $appointment->appointment_date->toDateString())
            ->available()
            ->where('start_time', '<=', $appointment->start_time)
            ->where('end_time', '>=', $appointment->end_time)
            ->orderBy('start_time')
            ->first();

        if ($block) {
            $block->markAsBooked();
        }
    }

    /**
     * Mark the corresponding availability block as available.
     */
    private function markBlockAsAvailable(Appointment $appointment): void
    {
        // We don't un-book blocks on cancellation to preserve history
        // But you could implement this if needed
    }

    /**
     * Schedule a follow-up appointment if the appointment type requires it.
     */
    private function scheduleFollowUpIfNeeded(Appointment $appointment): void
    {
        if (! $appointment->appointment_type_id) {
            return;
        }

        $appointmentType = AppointmentType::find($appointment->appointment_type_id);

        if (! $appointmentType || ! $appointmentType->needsFollowUp()) {
            return;
        }

        $followUpDate = $appointment->appointment_date->copy()->addDays($appointmentType->getFollowUpDays());

        // Create a planned follow-up appointment
        Appointment::create([
            'professional_id' => $appointment->professional_id,
            'patient_id' => $appointment->patient_id,
            'appointment_type_id' => $appointment->appointment_type_id,
            'parent_appointment_id' => $appointment->id,
            'appointment_date' => $followUpDate,
            'start_time' => $appointment->start_time,
            'end_time' => $appointment->end_time,
            'status' => 'planned_follow_up',
            'follow_up_status' => 'planned',
            'notes' => "Suivi automatique - {$appointmentType->name}",
            'room_id' => $appointment->room_id,
        ]);
    }

    /**
     * Resolve an existing patient or create a new one.
     */
    private function resolveOrCreatePatient(array $data): ?int
    {
        if (! empty($data['patient_id'])) {
            return (int) $data['patient_id'];
        }

        if (empty($data['patient_phone']) && empty($data['patient_email'])) {
            return null;
        }

        // Try to find existing patient
        $existingPatient = Patient::query()
            ->when(! empty($data['patient_phone']), fn ($q) => $q->where('phone', $data['patient_phone']))
            ->when(! empty($data['patient_email']), fn ($q) => $q->orWhere('email', $data['patient_email']))
            ->first();

        if ($existingPatient) {
            return $existingPatient->id;
        }

        // Create new patient
        $nameParts = ! empty($data['patient_name']) ? explode(' ', $data['patient_name'], 2) : ['', ''];

        $patient = Patient::create([
            'first_name' => $nameParts[0] ?: 'Inconnu',
            'last_name' => $nameParts[1] ?? '',
            'phone' => $data['patient_phone'] ?? null,
            'email' => $data['patient_email'] ?? null,
            'date_of_birth' => now()->subYears(30), // Default placeholder
        ]);

        return $patient->id;
    }
}
