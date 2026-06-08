<?php

namespace Modules\Appointment\Listeners;

use Modules\Queue\Models\QueuePriority;
use Modules\Appointment\Models\PatientJourney;

class AutoEscalateOnLongWait
{
    /**
     * Escalade automatique en cas d'attente excessive.
     * Lancé par: Scheduled Command toutes les 5 minutes.
     */
    public function handle(): void
    {
        $thresholds = [
            PatientJourney::STATUS_ARRIVED => 20, // 20 min
            PatientJourney::STATUS_IN_CARE => 10, // 10 min
        ];

        foreach ($thresholds as $status => $minutes) {
            $journeys = PatientJourney::where('current_status', $status)
                ->whereNotNull('arrived_at')
                ->where('arrived_at', '<', now()->subMinutes($minutes))
                ->get();

            foreach ($journeys as $journey) {
                $appointment = $journey->appointment;

                // Vérifier si déjà escaladée
                $priority = QueuePriority::where('appointment_id', $appointment->id)->first();

                if (!$priority || $priority->priority_level !== QueuePriority::PRIORITY_CRITICAL) {
                    // Escalader
                    QueuePriority::updateOrCreate(
                        ['appointment_id' => $appointment->id],
                        [
                            'priority_level' => QueuePriority::PRIORITY_CRITICAL,
                            'override_reason' => 'Auto-escalade: attente dépasse ' . $minutes . ' minutes',
                            'overridden_at' => now(),
                        ]
                    );

                    \Log::warning('ticket.auto_escalated', [
                        'appointment_id' => $appointment->id,
                        'wait_minutes' => now()->diffInMinutes($journey->arrived_at),
                    ]);
                }
            }
        }
    }
}
