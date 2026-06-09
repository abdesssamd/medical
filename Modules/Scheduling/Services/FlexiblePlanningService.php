<?php

namespace Modules\Scheduling\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Appointment\Models\Planning;
use Modules\Scheduling\Models\AppointmentType;
use Modules\Scheduling\Models\AvailabilityBlock;

class FlexiblePlanningService
{
    public function resolveAvailableBlocks(
        int $practitionerId,
        Carbon $date,
        ?int $appointmentTypeId = null
    ): Collection {
        $dayOfWeek = (int) $date->dayOfWeek;

        $plannings = Planning::where('professional_id', $practitionerId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        if ($plannings->isEmpty()) {
            return collect();
        }

        $blocks = collect();
        foreach ($plannings as $planning) {
            $modeBlocks = match ($planning->planning_mode) {
                'by_act'  => $this->resolveByActMode($planning, $date, $appointmentTypeId),
                'mixed'   => $this->resolveMixedMode($planning, $date, $appointmentTypeId),
                default   => $this->resolveBySpecialistMode($planning, $date),
            };
            $blocks = $blocks->merge($modeBlocks);
        }

        return $blocks;
    }

    private function resolveBySpecialistMode(Planning $planning, Carbon $date): Collection
    {
        $block = $this->buildBlockFromPlanning($planning, $date);
        return $block ? collect([$block]) : collect();
    }

    private function resolveByActMode(Planning $planning, Carbon $date, ?int $appointmentTypeId): Collection
    {
        if ($appointmentTypeId && $planning->appointment_type_id !== $appointmentTypeId) {
            return collect();
        }

        $block = $this->buildBlockFromPlanning($planning, $date);
        if (!$block) {
            return collect();
        }
        $block['appointment_type_id'] = $planning->appointment_type_id;
        return collect([$block]);
    }

    private function resolveMixedMode(Planning $planning, Carbon $date, ?int $appointmentTypeId): Collection
    {
        $block = $this->buildBlockFromPlanning($planning, $date);
        if (!$block) {
            return collect();
        }

        if ($appointmentTypeId) {
            $acte = AppointmentType::find($appointmentTypeId);
            if ($acte) {
                $blockDuration = $this->getBlockDuration($block);
                if ($acte->duration_minutes > $blockDuration) {
                    return collect();
                }

                $room = $planning->professional?->primaryRoom;
                if ($acte->required_equipment && $room) {
                    $roomEquipment = $room->equipment ?? [];
                    foreach ((array) $acte->required_equipment as $equip) {
                        if (!in_array($equip, $roomEquipment, true)) {
                            return collect();
                        }
                    }
                }
                $block['appointment_type_id'] = $appointmentTypeId;
            }
        }

        return collect([$block]);
    }

    private function buildBlockFromPlanning(Planning $planning, Carbon $date): ?array
    {
        $existingBlock = AvailabilityBlock::where('practitioner_id', $planning->professional_id)
            ->whereDate('date', $date->toDateString())
            ->where('start_time', $planning->start_time)
            ->where('end_time', $planning->end_time)
            ->where('type', 'available')
            ->where('is_booked', false)
            ->first();

        if ($existingBlock) {
            return $existingBlock->toArray();
        }

        return [
            'practitioner_id'    => $planning->professional_id,
            'date'               => $date->toDateString(),
            'start_time'         => $planning->start_time,
            'end_time'           => $planning->end_time,
            'type'               => 'available',
            'max_patients'       => $planning->max_patients_per_day,
            'is_booked'          => false,
            'room_id'            => $planning->professional?->primaryRoom?->id,
            'appointment_type_id' => null,
        ];
    }

    private function getBlockDuration(array $block): int
    {
        return Carbon::parse($block['start_time'])->diffInMinutes(Carbon::parse($block['end_time']));
    }

    public function practitionerCanPerformAct(int $practitionerId, int $appointmentTypeId): bool
    {
        $acte = AppointmentType::with('specialty')->find($appointmentTypeId);
        if (!$acte) return false;

        $practitioner = User::with('specialties')->find($practitionerId);
        if (!$practitioner) return false;

        return $practitioner->canAccessSpecialty($acte->specialty_id);
    }

    public function generateWeeklyGrid(int $practitionerId): array
    {
        $plannings = Planning::where('professional_id', $practitionerId)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $grid = [];

        foreach ($days as $dayIndex => $dayName) {
            $dayPlannings = $plannings->get($dayIndex, collect());
            $grid[] = [
                'day_index' => $dayIndex,
                'day_name'  => $dayName,
                'plannings' => $dayPlannings->map(fn (Planning $p) => [
                    'id'                   => $p->id,
                    'start_time'           => $p->start_time,
                    'end_time'             => $p->end_time,
                    'planning_mode'        => $p->planning_mode,
                    'appointment_type'     => $p->appointmentType?->name,
                    'appointment_type_id'  => $p->appointment_type_id,
                    'consultation_minutes' => $p->getEffectiveDurationMinutes(),
                    'max_patients'         => $p->max_patients_per_day,
                    'is_active'            => $p->is_active,
                ]),
            ];
        }

        return $grid;
    }
}
