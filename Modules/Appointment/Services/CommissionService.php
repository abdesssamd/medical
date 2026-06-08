<?php

namespace Modules\Appointment\Services;

use Carbon\Carbon;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\Commission;
use Modules\Appointment\Models\Setting;

class CommissionService
{
    public function registerFromConsultedAppointment(Appointment $appointment): Commission
    {
        $setting = Setting::firstOrCreate(
            ['professional_id' => $appointment->professional_id],
            [
                'default_commission_amount' => (float) config('appointment.default_commission_amount', 20),
                'currency' => (string) config('appointment.currency', 'MAD'),
                'allow_secretary_edit' => true,
                'allow_secretary_cancel' => true,
                'timezone' => 'Europe/Paris',
                'queue_service_id' => null,
            ]
        );

        return Commission::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'professional_id' => $appointment->professional_id,
                'secretary_id' => $appointment->secretary_id,
                'amount' => $setting->default_commission_amount,
                'currency' => $setting->currency,
                'status' => 'pending',
                'earned_on' => Carbon::parse($appointment->appointment_date)->toDateString(),
            ]
        );
    }

    public function totalsByPeriod(int $professionalId, Carbon $from, Carbon $to): array
    {
        $base = Commission::where('professional_id', $professionalId)
            ->whereBetween('earned_on', [$from->toDateString(), $to->toDateString()]);

        return [
            'pending' => (float) (clone $base)->where('status', 'pending')->sum('amount'),
            'approved' => (float) (clone $base)->where('status', 'approved')->sum('amount'),
            'paid' => (float) (clone $base)->where('status', 'paid')->sum('amount'),
            'total' => (float) (clone $base)->sum('amount'),
        ];
    }
}
