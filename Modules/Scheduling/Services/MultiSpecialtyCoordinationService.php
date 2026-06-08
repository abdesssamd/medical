<?php

namespace Modules\Scheduling\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\ClinicalRecord\Models\ClinicalProcedure;
use Modules\Scheduling\Services\BookingService;
use Modules\Scheduling\Models\AvailabilityBlock;

class MultiSpecialtyCoordinationService
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly BookingService $bookingService
    ) {
    }

    /**
     * Find the optimal day for a patient to see multiple specialists.
     * 
     * @param int $patientId Patient ID
     * @param array $requiredSpecialties Array of ['specialty_id' => X, 'appointment_type_id' => Y, 'priority' => Z]
     * @param Carbon $fromDate Search start date
     * @param Carbon $toDate Search end date
     * @return array|null Optimal day info or null
     */
    public function findOptimalDayForMultipleSpecialties(
        int $patientId,
        array $requiredSpecialties,
        Carbon $fromDate,
        Carbon $toDate
    ): ?array {
        $availabilityByDay = [];

        // Collect availability for each required specialty
        foreach ($requiredSpecialties as $index => $spec) {
            $practitioners = User::whereHas('specialties', function ($q) use ($spec) {
                $q->where('specialty_id', $spec['specialty_id']);
            })->get();

            foreach ($practitioners as $practitioner) {
                $blocks = AvailabilityBlock::where('practitioner_id', $practitioner->id)
                    ->betweenDates($fromDate, $toDate)
                    ->available()
                    ->orderBy('date')
                    ->orderBy('start_time')
                    ->get();

                foreach ($blocks as $block) {
                    $day = $block->date->format('Y-m-d');
                    $availabilityByDay[$day][] = [
                        'practitioner_id' => $practitioner->id,
                        'practitioner_name' => $practitioner->display_name,
                        'specialty_id' => $spec['specialty_id'],
                        'appointment_type_id' => $spec['appointment_type_id'] ?? null,
                        'start_time' => $block->start_time,
                        'end_time' => $block->end_time,
                        'room_id' => $block->room_id,
                        'priority' => $spec['priority'] ?? ($index + 1),
                    ];
                }
            }
        }

        // Find the optimal day (all specialties covered)
        $candidateDays = [];

        foreach ($availabilityByDay as $day => $slots) {
            $coveredSpecialties = collect($slots)->pluck('specialty_id')->unique();

            if ($coveredSpecialties->count() === count($requiredSpecialties)) {
                // This day has all required specialties
                $candidateDays[$day] = [
                    'date' => $day,
                    'day_name' => Carbon::parse($day)->locale(app()->getLocale())->dayName,
                    'slots' => $this->optimizeSlotOrder($slots, $requiredSpecialties),
                    'total_wait_minutes' => $this->calculateTotalWaitTime($slots),
                    'practitioner_count' => collect($slots)->pluck('practitioner_id')->unique()->count(),
                ];
            }
        }

        if (empty($candidateDays)) {
            return null;
        }

        // Return the earliest day with minimal wait time
        return collect($candidateDays)
            ->sortBy(['total_wait_minutes', 'date'])
            ->first();
    }

    /**
     * Find available practitioners for a specific specialty and date.
     */
    public function findAvailablePractitionersForSpecialty(
        int $specialtyId,
        Carbon $date,
        ?int $appointmentTypeId = null
    ): Collection {
        $practitioners = User::whereHas('specialties', function ($q) use ($specialtyId) {
            $q->where('specialty_id', $specialtyId);
        })->get();

        $availablePractitioners = [];

        foreach ($practitioners as $practitioner) {
            $blocks = AvailabilityBlock::forPractitioner($practitioner->id)
                ->forDate($date)
                ->available()
                ->when($appointmentTypeId, fn ($q) => $q->forAppointmentType($appointmentTypeId))
                ->get();

            if ($blocks->isNotEmpty()) {
                $availablePractitioners[] = [
                    'practitioner_id' => $practitioner->id,
                    'practitioner_name' => $practitioner->display_name,
                    'specialty_id' => $specialtyId,
                    'available_blocks' => $blocks->map(fn ($block) => [
                        'start_time' => $block->start_time,
                        'end_time' => $block->end_time,
                        'room_id' => $block->room_id,
                    ])->values(),
                ];
            }
        }

        return collect($availablePractitioners);
    }

    /**
     * Optimize the order of slots to minimize patient wait time.
     * Priority: surgery first, then consultations, then follow-ups.
     */
    private function optimizeSlotOrder(array $slots, array $requiredSpecialties): array
    {
        return collect($slots)
            ->sortBy(function ($slot) use ($requiredSpecialties) {
                // Sort by priority defined in requiredSpecialties
                foreach ($requiredSpecialties as $spec) {
                    if ($spec['specialty_id'] === $slot['specialty_id']) {
                        return $spec['priority'] ?? 99;
                    }
                }
                return 99;
            })
            ->values()
            ->all();
    }

    /**
     * Calculate total wait time between consecutive appointments.
     */
    private function calculateTotalWaitTime(array $slots): int
    {
        if (count($slots) < 2) {
            return 0;
        }

        $sorted = collect($slots)->sortBy('start_time')->values();
        $totalWait = 0;

        for ($i = 0; $i < $sorted->count() - 1; $i++) {
            $endCurrent = Carbon::parse($sorted[$i]['end_time']);
            $startNext = Carbon::parse($sorted[$i + 1]['start_time']);

            if ($startNext->gt($endCurrent)) {
                $totalWait += $endCurrent->diffInMinutes($startNext);
            }
        }

        return $totalWait;
    }

    /**
     * Suggest follow-up appointment dates based on previous appointment.
     */
    public function suggestFollowUpDates(
        int $practitionerId,
        int $appointmentTypeId,
        Carbon $fromDate,
        int $limit = 5
    ): array {
        $appointmentType = \Modules\Scheduling\Models\AppointmentType::find($appointmentTypeId);

        if (! $appointmentType || ! $appointmentType->needsFollowUp()) {
            return [];
        }

        $recommendedFromDate = $fromDate->copy()->addDays($appointmentType->getFollowUpDays());
        $toDate = $recommendedFromDate->copy()->addDays(7); // Search within a week

        return $this->findAvailableSlotsInRange($practitionerId, $recommendedFromDate, $toDate, $appointmentTypeId)
            ->take($limit)
            ->all();
    }

    /**
     * Find available slots in a date range (wrapper around AvailabilityService).
     */
    private function findAvailableSlotsInRange(
        int $practitionerId,
        Carbon $fromDate,
        Carbon $toDate,
        int $appointmentTypeId
    ): Collection {
        return collect(
            $this->availabilityService->findAvailableSlotsInRange($practitionerId, $fromDate, $toDate, $appointmentTypeId)
        );
    }

    /**
     * Book coordinated appointments on the same day.
     */
    public function bookGroupedAppointments(int $patientId, array $specialties, Carbon $date, int $creatorId): array
    {
        $booked = [];

        DB::transaction(function () use (&$booked, $patientId, $specialties, $date, $creatorId): void {
            foreach ($specialties as $specialty) {
                $specialtyId = (int) $specialty['specialty_id'];
                $appointmentTypeId = ! empty($specialty['appointment_type_id']) ? (int) $specialty['appointment_type_id'] : null;

                $practitioner = User::whereHas('specialties', function ($q) use ($specialtyId) {
                    $q->where('specialty_id', $specialtyId);
                })->first();

                if (! $practitioner) {
                    throw new \RuntimeException("Aucun praticien disponible pour la specialite {$specialtyId}.");
                }

                $availability = $this->availabilityService->getAvailability(
                    $practitioner->id,
                    $date,
                    $appointmentTypeId
                );

                if (empty($availability['slots'])) {
                    throw new \RuntimeException("Aucun creneau disponible pour la specialite {$specialtyId}.");
                }

                $slot = $availability['slots'][0];

                $booked[] = $this->bookingService->bookAppointment([
                    'professional_id' => $practitioner->id,
                    'patient_id' => $patientId,
                    'appointment_date' => $date->toDateString(),
                    'start_time' => substr($slot['start_time'], 0, 5),
                    'appointment_type_id' => $appointmentTypeId,
                    'room_id' => $slot['room_id'] ?? null,
                    'notes' => 'Coordination inter-specialites',
                ], $creatorId);
            }
        });

        return $booked;
    }

    /**
     * Suggest coordinated slots by automatically inferring specialties from the patient's active dossier.
     */
    public function suggestAutomaticallyFromPatientDossier(
        int $patientId,
        Carbon $fromDate,
        Carbon $toDate
    ): ?array {
        $specialties = ClinicalProcedure::query()
            ->where('patient_id', $patientId)
            ->whereIn('status', ['planned', 'in_progress'])
            ->whereNotNull('specialty_id')
            ->select('specialty_id')
            ->distinct()
            ->limit(4)
            ->get()
            ->values()
            ->map(fn ($row, $index) => [
                'specialty_id' => (int) $row->specialty_id,
                'priority' => $index + 1,
            ])
            ->all();

        if (count($specialties) < 2) {
            return null;
        }

        return $this->findOptimalDayForMultipleSpecialties($patientId, $specialties, $fromDate, $toDate);
    }
}
