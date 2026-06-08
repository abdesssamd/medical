<?php

namespace Modules\Appointment\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\PatientJourney;
use Modules\Appointment\Models\Planning;
use Modules\Billing\Models\Invoice;
use Modules\Scheduling\Models\AvailabilityBlock;

class PatientFlowService
{
    public function syncJourneyFromAppointment(Appointment $appointment): PatientJourney
    {
        $journey = PatientJourney::firstOrNew(['appointment_id' => $appointment->id]);
        $journey->patient_id = $appointment->patient_id;
        $journey->queue_ticket_id = $appointment->queue_ticket_id;
        $journey->current_status = $journey->current_status ?: PatientJourney::STATUS_BOOKED;
        $journey->public_tracking_code = $journey->public_tracking_code ?: Str::upper(Str::random(10));
        $journey->save();

        return $journey;
    }

    public function transition(Appointment $appointment, string $status): PatientJourney
    {
        $journey = $this->syncJourneyFromAppointment($appointment);

        $allowedStatuses = [
            PatientJourney::STATUS_ARRIVED,
            PatientJourney::STATUS_IN_CARE,
            PatientJourney::STATUS_AWAITING_PAYMENT,
            PatientJourney::STATUS_COMPLETED,
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            abort(422, 'Statut de parcours invalide.');
        }

        return DB::transaction(function () use ($journey, $appointment, $status): PatientJourney {
            $payload = ['current_status' => $status];

            if ($status === PatientJourney::STATUS_ARRIVED) {
                $payload['arrived_at'] = $journey->arrived_at ?? now();
            }

            if ($status === PatientJourney::STATUS_IN_CARE) {
                $payload['in_care_at'] = $journey->in_care_at ?? now();
            }

            if ($status === PatientJourney::STATUS_AWAITING_PAYMENT) {
                $payload['awaiting_payment_at'] = $journey->awaiting_payment_at ?? now();
                $invoiceId = Invoice::where('patient_id', $appointment->patient_id)
                    ->whereDate('invoice_date', $appointment->appointment_date)
                    ->latest('id')
                    ->value('id');
                if ($invoiceId) {
                    $payload['invoice_id'] = $invoiceId;
                }
            }

            if ($status === PatientJourney::STATUS_COMPLETED) {
                $payload['completed_at'] = $journey->completed_at ?? now();
            }

            $journey->update($payload);

            return $journey->fresh(['patient', 'appointment', 'queueTicket', 'invoice']);
        });
    }

    public function board(string $date, ?int $professionalId = null): array
    {
        $query = Appointment::with(['patient', 'professional', 'queueTicket'])
            ->whereDate('appointment_date', $date)
            ->orderBy('start_time');

        if ($professionalId) {
            $query->where('professional_id', $professionalId);
        }

        $appointments = $query->get();

        $journeys = PatientJourney::whereIn('appointment_id', $appointments->pluck('id'))
            ->get()
            ->keyBy('appointment_id');

        $rows = $appointments->map(function (Appointment $appointment) use ($appointments, $journeys) {
            $journey = $journeys->get($appointment->id);
            $roomLabel = $appointment->room?->name ?? $journey?->assigned_room_label;
            $position = $this->estimateQueuePosition($appointments, $appointment);
            $estimatedWait = max(0, $position * 15);
            $startAt = $appointment->appointment_date?->format('Y-m-d').' '.substr((string) $appointment->start_time, 0, 5);
            $doctorDelayMinutes = 0;
            if ($journey?->current_status !== PatientJourney::STATUS_COMPLETED && $startAt) {
                $doctorDelayMinutes = max(0, now()->diffInMinutes($startAt, false) * -1);
            }

            if ($journey && (($journey->estimated_wait_minutes ?? -1) !== $estimatedWait || ($journey->assigned_room_label ?? '') !== ($roomLabel ?? ''))) {
                $journey->forceFill([
                    'estimated_wait_minutes' => $estimatedWait,
                    'assigned_room_label' => $roomLabel,
                ])->save();
            }

            return [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'patient_name' => $appointment->patient?->full_name ?? $appointment->patient_name,
                'professional_id' => $appointment->professional_id,
                'professional_name' => $appointment->professional?->display_name,
                'start_time' => $appointment->start_time,
                'appointment_status' => $appointment->status,
                'flow_status' => $journey?->current_status ?? PatientJourney::STATUS_BOOKED,
                'queue_ticket_id' => $appointment->queue_ticket_id,
                'queue_ticket_number' => $appointment->queueTicket?->ticket_number,
                'room_name' => $roomLabel,
                'queue_position' => $position,
                'estimated_wait_minutes' => $estimatedWait,
                'doctor_delay_minutes' => $doctorDelayMinutes,
                'public_tracking_code' => $journey?->public_tracking_code,
                'arrived_at' => $journey?->arrived_at?->toDateTimeString(),
                'in_care_at' => $journey?->in_care_at?->toDateTimeString(),
                'awaiting_payment_at' => $journey?->awaiting_payment_at?->toDateTimeString(),
                'completed_at' => $journey?->completed_at?->toDateTimeString(),
            ];
        });

        return [
            'date' => $date,
            'total' => $rows->count(),
            'by_status' => $rows->groupBy('flow_status')->map->count(),
            'items' => $rows->values(),
        ];
    }

    public function planningGrid(string $date, ?array $professionalIds = null): array
    {
        $day = Carbon::parse($date)->startOfDay();
        $weekday = (int) $day->dayOfWeek;

        $professionals = User::query()
            ->whereIn('role', ['professional', 'doctor', 'medecin'])
            ->when(! empty($professionalIds), fn ($query) => $query->whereIn('id', $professionalIds))
            ->with('primaryRoom')
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $planningByProfessional = Planning::query()
            ->where('day_of_week', $weekday)
            ->whereIn('professional_id', $professionals->pluck('id'))
            ->get()
            ->keyBy('professional_id');

        $appointments = Appointment::query()
            ->with(['patient:id,first_name,last_name', 'consultation:id,appointment_id,consultation_status,consultation_type'])
            ->whereDate('appointment_date', $day->toDateString())
            ->whereIn('professional_id', $professionals->pluck('id'))
            ->orderBy('start_time')
            ->get();

        $blocks = AvailabilityBlock::query()
            ->with('room:id,name,code')
            ->whereDate('date', $day->toDateString())
            ->whereIn('practitioner_id', $professionals->pluck('id'))
            ->orderBy('start_time')
            ->get();

        return [
            'date' => $day->toDateString(),
            'weekday' => $weekday,
            'rows' => $professionals->map(function (User $professional) use ($planningByProfessional, $appointments, $blocks, $day): array {
                $planning = $planningByProfessional->get($professional->id);

                if (! $planning || ! $planning->is_active) {
                    return [
                        'professional_id' => $professional->id,
                        'professional_name' => $professional->name,
                        'role' => $professional->role,
                        'planning_id' => $planning?->id,
                        'start_time' => null,
                        'end_time' => null,
                        'consultation_minutes' => null,
                        'slots' => [],
                        'summary' => ['free' => 0, 'booked' => 0, 'in_care' => 0, 'completed' => 0, 'blocked' => 0],
                        'is_active' => false,
                        'room_name' => $professional->primaryRoom?->name,
                    ];
                }

                $start = Carbon::parse($day->toDateString().' '.$planning->start_time);
                $end = Carbon::parse($day->toDateString().' '.$planning->end_time);
                $step = max(15, (int) $planning->consultation_minutes);
                $rowAppointments = $appointments->where('professional_id', $professional->id)->values();
                $rowBlocks = $blocks->where('practitioner_id', $professional->id)->values();

                $slots = [];
                $cursor = $start->copy();

                while ($cursor->lt($end)) {
                    $slotEnd = $cursor->copy()->addMinutes($step);
                    if ($slotEnd->gt($end)) {
                        $slotEnd = $end->copy();
                    }

                    $slotAppointment = $rowAppointments->first(function (Appointment $appointment) use ($cursor, $slotEnd): bool {
                        $appointmentStart = Carbon::parse((string) $appointment->start_time);
                        $appointmentEnd = Carbon::parse((string) $appointment->end_time);
                        return $appointmentStart->lt($slotEnd) && $appointmentEnd->gt($cursor);
                    });

                    $slotBlock = $rowBlocks->first(function (AvailabilityBlock $block) use ($cursor, $slotEnd): bool {
                        $blockStart = Carbon::parse($block->date->toDateString().' '.$block->start_time);
                        $blockEnd = Carbon::parse($block->date->toDateString().' '.$block->end_time);
                        return $blockStart->lt($slotEnd) && $blockEnd->gt($cursor);
                    });

                    $slotStatus = 'free';
                    $slotLabel = null;
                    $slotBadge = 'bg-light';
                    $appointmentStatus = null;
                    $consultationStatus = null;
                    $consultationType = null;
                    $appointmentId = null;

                    if ($slotBlock && $slotBlock->type !== AvailabilityBlock::TYPE_AVAILABLE) {
                        $slotStatus = 'blocked';
                        $slotLabel = $slotBlock->label ?: ucfirst($slotBlock->type);
                        $slotBadge = 'bg-dark text-white';
                    }

                    if ($slotAppointment) {
                        $appointmentId = $slotAppointment->id;
                        $appointmentStatus = $slotAppointment->status;
                        $consultationStatus = $slotAppointment->consultation?->consultation_status;
                        $consultationType = $slotAppointment->consultation?->consultation_type;
                        [$slotStatus, $slotBadge] = $this->mapSlotStatus($slotAppointment, $consultationStatus);
                        $slotLabel = $slotAppointment->patient?->full_name ?? $slotAppointment->patient_name ?? 'Patient';
                    }

                    $slots[] = [
                        'start_time' => $cursor->format('H:i:s'),
                        'end_time' => $slotEnd->format('H:i:s'),
                        'status' => $slotStatus,
                        'badge' => $slotBadge,
                        'label' => $slotLabel,
                        'appointment_id' => $appointmentId,
                        'patient_id' => $slotAppointment?->patient_id,
                        'patient_name' => $slotAppointment?->patient?->full_name ?? $slotAppointment?->patient_name,
                        'appointment_status' => $appointmentStatus,
                        'consultation_status' => $consultationStatus,
                        'consultation_type' => $consultationType,
                        'block_id' => $slotBlock?->id,
                        'block_type' => $slotBlock?->type,
                        'block_label' => $slotBlock?->label,
                        'room_name' => $slotBlock?->room?->name,
                    ];

                    $cursor = $slotEnd->copy();
                }

                $summary = collect($slots)->groupBy('status')->map->count()->all();

                return [
                    'professional_id' => $professional->id,
                    'professional_name' => $professional->name,
                    'role' => $professional->role,
                    'planning_id' => $planning->id,
                    'start_time' => substr((string) $planning->start_time, 0, 5),
                    'end_time' => substr((string) $planning->end_time, 0, 5),
                    'consultation_minutes' => (int) $planning->consultation_minutes,
                    'room_name' => $professional->primaryRoom?->name,
                    'slots' => $slots,
                    'summary' => array_merge(['free' => 0, 'booked' => 0, 'in_care' => 0, 'completed' => 0, 'blocked' => 0], $summary),
                    'is_active' => true,
                ];
            })->values(),
        ];
    }

    private function estimateQueuePosition($appointments, Appointment $current): int
    {
        $samePractitioner = $appointments
            ->where('professional_id', $current->professional_id)
            ->sortBy('start_time')
            ->values();

        $index = $samePractitioner->search(fn (Appointment $a) => $a->id === $current->id);
        if ($index === false) {
            return 0;
        }

        return (int) $index;
    }

    private function mapSlotStatus(Appointment $appointment, ?string $consultationStatus = null): array
    {
        $status = $appointment->status;
        $consultationStatus = $consultationStatus ?: $appointment->consultation?->consultation_status;

        if (in_array($consultationStatus, ['en_soin', 'in_care'], true) || in_array($status, ['arrived', 'in_care'], true)) {
            return ['in_care', 'bg-orange text-white'];
        }

        if (in_array($consultationStatus, ['termine', 'completed'], true) || in_array($status, ['completed', 'consulted'], true)) {
            return ['completed', 'bg-green text-white'];
        }

        return ['booked', 'bg-blue text-white'];
    }
}
