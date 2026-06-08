<?php

namespace Modules\Appointment\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\Planning;

class AvailabilityService
{
    public function getAvailability(int $professionalId, Carbon $date): array
    {
        $planning = Planning::where('professional_id', $professionalId)
            ->where('day_of_week', (int) $date->dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (! $planning) {
            return [
                'available' => false,
                'reason' => 'not_working_day',
                'quota_reached' => false,
                'slots' => [],
                'max_patients_per_day' => null,
                'booked_count' => 0,
            ];
        }

        $bookedCount = Appointment::where('professional_id', $professionalId)
            ->whereDate('appointment_date', $date->toDateString())
            ->whereIn('status', ['booked', 'consulted'])
            ->count();

        $quotaReached = $planning->max_patients_per_day !== null
            && $bookedCount >= $planning->max_patients_per_day;

        return [
            'available' => ! $quotaReached,
            'reason' => $quotaReached ? 'quota_reached' : null,
            'quota_reached' => $quotaReached,
            'slots' => $quotaReached ? [] : $this->generateAvailableSlots($planning, $date),
            'max_patients_per_day' => $planning->max_patients_per_day,
            'booked_count' => $bookedCount,
        ];
    }

    public function ensureSlotIsAvailable(int $professionalId, Carbon $date, string $startTime): void
    {
        $availability = $this->getAvailability($professionalId, $date);

        if (! $availability['available']) {
            abort(422, 'Quota journalier atteint ou jour non ouvré.');
        }

        if (! in_array($startTime, $availability['slots'], true)) {
            abort(422, 'Créneau indisponible.');
        }
    }

    public function resolvePlanningOrFail(int $professionalId, Carbon $date): Planning
    {
        return Planning::where('professional_id', $professionalId)
            ->where('day_of_week', (int) $date->dayOfWeek)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function generateAvailableSlots(Planning $planning, Carbon $date): array
    {
        $duration = max(5, (int) $planning->consultation_minutes);
        $start = Carbon::parse($date->toDateString().' '.$planning->start_time);
        $end = Carbon::parse($date->toDateString().' '.$planning->end_time);

        if ($end->lessThanOrEqualTo($start)) {
            return [];
        }

        $period = CarbonPeriod::since($start)->minutes($duration)->until($end->copy()->subMinutes($duration));
        $booked = Appointment::where('professional_id', $planning->professional_id)
            ->whereDate('appointment_date', $date->toDateString())
            ->whereIn('status', ['booked', 'consulted'])
            ->pluck('start_time')
            ->map(fn ($time) => Carbon::parse((string) $time)->format('H:i:s'))
            ->values()
            ->all();

        $slots = [];
        foreach ($period as $time) {
            $candidate = $time->format('H:i:s');
            if (! in_array($candidate, $booked, true)) {
                $slots[] = $candidate;
            }
        }

        return $slots;
    }
}
