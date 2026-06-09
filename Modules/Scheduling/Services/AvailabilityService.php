<?php

namespace Modules\Scheduling\Services;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Appointment\Models\Appointment;
use Modules\Scheduling\Models\AvailabilityBlock;
use Modules\Scheduling\Models\AppointmentType;

class AvailabilityService
{
    /**
     * V2: utilise FlexiblePlanningService pour résoudre les trois modes.
     */
    public function getAvailabilityV2(
        int $practitionerId,
        Carbon $date,
        ?int $appointmentTypeId = null
    ): array {
        $flexibleService = app(FlexiblePlanningService::class);
        $resolvedBlocks = $flexibleService->resolveAvailableBlocks(
            $practitionerId, $date, $appointmentTypeId
        );

        if ($resolvedBlocks->isEmpty()) {
            return [
                'available' => false,
                'reason' => 'no_availability_block',
                'slots' => [],
                'max_patients_per_day' => null,
                'booked_count' => 0,
            ];
        }

        foreach ($resolvedBlocks as $blockData) {
            AvailabilityBlock::updateOrCreate(
                [
                    'practitioner_id' => $blockData['practitioner_id'],
                    'date'            => $blockData['date'],
                    'start_time'      => $blockData['start_time'],
                    'end_time'        => $blockData['end_time'],
                    'type'            => 'available',
                ],
                [
                    'room_id'             => $blockData['room_id'] ?? null,
                    'appointment_type_id' => $blockData['appointment_type_id'] ?? $appointmentTypeId,
                    'max_patients'        => $blockData['max_patients'] ?? null,
                ]
            );
        }

        return $this->getAvailability($practitionerId, $date, $appointmentTypeId);
    }
    /**
     * Get availability for a practitioner on a specific date.
     * Returns available time slots based on availability blocks and existing appointments.
     */
    public function getAvailability(int $practitionerId, Carbon $date, ?int $appointmentTypeId = null): array
    {
        $blocks = AvailabilityBlock::forPractitioner($practitionerId)
            ->forDate($date)
            ->available()
            ->when($appointmentTypeId, fn ($q) => $q->forAppointmentType($appointmentTypeId))
            ->orderBy('start_time')
            ->get();

        if ($blocks->isEmpty()) {
            return [
                'available' => false,
                'reason' => 'no_availability_block',
                'quota_reached' => false,
                'slots' => [],
                'max_patients_per_day' => null,
                'booked_count' => 0,
            ];
        }

        // Get existing appointments for the date
        $bookedCount = Appointment::where('professional_id', $practitionerId)
            ->whereDate('appointment_date', $date->toDateString())
            ->whereIn('status', ['booked', 'consulted'])
            ->count();

        // Generate available slots from blocks
        $slots = $this->generateAvailableSlotsFromBlocks($blocks, $date, $practitionerId, $appointmentTypeId);
        $totalMaxPatients = $blocks->sum('max_patients') ?: null;
        $quotaReached = $totalMaxPatients !== null && $bookedCount >= $totalMaxPatients;

        return [
            'available' => ! $quotaReached && ! empty($slots),
            'reason' => $quotaReached ? 'quota_reached' : (empty($slots) ? 'no_slots_available' : null),
            'quota_reached' => $quotaReached,
            'slots' => $quotaReached ? [] : $slots,
            'max_patients_per_day' => $totalMaxPatients,
            'booked_count' => $bookedCount,
            'blocks' => $blocks->map(fn ($block) => [
                'id' => $block->id,
                'start_time' => $block->start_time,
                'end_time' => $block->end_time,
                'room_id' => $block->room_id,
                'appointment_type_id' => $block->appointment_type_id,
            ]),
        ];
    }

    /**
     * Generate available time slots from availability blocks.
     */
    private function generateAvailableSlotsFromBlocks(
        Collection $blocks,
        Carbon $date,
        int $practitionerId,
        ?int $appointmentTypeId = null
    ): array {
        // Get existing appointment times
        $bookedTimes = Appointment::where('professional_id', $practitionerId)
            ->whereDate('appointment_date', $date->toDateString())
            ->whereIn('status', ['booked', 'consulted'])
            ->get()
            ->map(function ($appointment) {
                return [
                    'start' => $appointment->start_time,
                    'end' => $appointment->end_time,
                ];
            })
            ->all();

        $slots = [];

        foreach ($blocks as $block) {
            // Determine slot duration
            $durationMinutes = $appointmentTypeId
                ? AppointmentType::find($appointmentTypeId)?->duration_minutes ?? 20
                : $block->appointmentType?->duration_minutes ?? 20;

            $start = Carbon::parse($date->toDateString().' '.$block->start_time);
            $end = Carbon::parse($date->toDateString().' '.$block->end_time);

            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            // Generate slots within this block
            $period = CarbonPeriod::since($start)
                ->minutes($durationMinutes)
                ->until($end->copy()->subMinutes($durationMinutes));

            foreach ($period as $time) {
                $slotStart = $time->format('H:i:s');
                $slotEnd = $time->copy()->addMinutes($durationMinutes)->format('H:i:s');

                // Check if this slot overlaps with any existing appointment
                $isOverlapping = false;
                foreach ($bookedTimes as $booked) {
                    if ($this->timeSlotsOverlap($slotStart, $slotEnd, $booked['start'], $booked['end'])) {
                        $isOverlapping = true;
                        break;
                    }
                }

                if (! $isOverlapping) {
                    $slots[] = [
                        'start_time' => $slotStart,
                        'end_time' => $slotEnd,
                        'block_id' => $block->id,
                        'room_id' => $block->room_id,
                    ];
                }
            }
        }

        return $slots;
    }

    /**
     * Check if two time slots overlap.
     */
    private function timeSlotsOverlap(
        string $start1, string $end1,
        string $start2, string $end2
    ): bool {
        return $start1 < $end2 && $start2 < $end1;
    }

    /**
     * Ensure a specific slot is available for booking.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureSlotIsAvailable(int $practitionerId, Carbon $date, string $startTime): void
    {
        $availability = $this->getAvailability($practitionerId, $date);

        if (! $availability['available']) {
            abort(422, $availability['reason'] === 'quota_reached'
                ? 'Quota journalier atteint ou jour non ouvré.'
                : 'Créneau indisponible.'
            );
        }

        $slotFound = collect($availability['slots'])
            ->contains(fn ($slot) => $slot['start_time'] === $startTime);

        if (! $slotFound) {
            abort(422, 'Créneau indisponible.');
        }
    }

    /**
     * Get the first available slot for a given date and appointment type.
     */
    public function getFirstAvailableSlot(int $practitionerId, Carbon $date, ?int $appointmentTypeId = null): ?array
    {
        $availability = $this->getAvailability($practitionerId, $date, $appointmentTypeId);

        if (! $availability['available'] || empty($availability['slots'])) {
            return null;
        }

        return $availability['slots'][0];
    }

    /**
     * Find available slots across a date range.
     */
    public function findAvailableSlotsInRange(
        int $practitionerId,
        Carbon $fromDate,
        Carbon $toDate,
        ?int $appointmentTypeId = null
    ): array {
        $availableDates = [];

        $period = CarbonPeriod::since($fromDate)->until($toDate);

        foreach ($period as $date) {
            $availability = $this->getAvailability($practitionerId, $date, $appointmentTypeId);

            if ($availability['available']) {
                $availableDates[] = [
                    'date' => $date->toDateString(),
                    'day_name' => $date->locale(app()->getLocale())->dayName,
                    'slot_count' => count($availability['slots']),
                    'first_slot' => $availability['slots'][0] ?? null,
                ];
            }
        }

        return $availableDates;
    }

    /**
     * Resolve planning or fail (backward compatibility with old Planning model).
     */
    public function resolvePlanningOrFail(int $professionalId, Carbon $date): \Modules\Appointment\Models\Planning
    {
        return \Modules\Appointment\Models\Planning::where('professional_id', $professionalId)
            ->where('day_of_week', (int) $date->dayOfWeek)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * Create availability blocks from a Planning model (migration helper).
     */
    public function migratePlanningToBlocks(int $practitionerId, Carbon $fromDate, Carbon $toDate): void
    {
        $plannings = \Modules\Appointment\Models\Planning::where('professional_id', $practitionerId)
            ->where('is_active', true)
            ->get();

        $currentDate = $fromDate->copy();

        while ($currentDate->lte($toDate)) {
            $dayOfWeek = (int) $currentDate->dayOfWeek;
            $planning = $plannings->firstWhere('day_of_week', $dayOfWeek);

            if ($planning) {
                AvailabilityBlock::create([
                    'practitioner_id' => $practitionerId,
                    'date' => $currentDate->toDateString(),
                    'start_time' => $planning->start_time,
                    'end_time' => $planning->end_time,
                    'type' => 'available',
                    'max_patients' => $planning->max_patients_per_day,
                ]);
            }

            $currentDate->addDay();
        }
    }
}
