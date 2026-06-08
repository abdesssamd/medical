<?php

namespace Modules\Appointment\Console\Commands;

use Illuminate\Console\Command;
use Modules\Appointment\Models\PatientJourney;
use Modules\Queue\Models\QueuePriority;
use Illuminate\Support\Facades\Log;

class AutoEscalateWaitingPatientsCommand extends Command
{
    protected $signature = 'queue:auto-escalate-waiting {--threshold=20 : Seuil d\'attente en minutes}';
    protected $description = 'Escalade automatique des patients en attente excessive';

    public function handle()
    {
        $threshold = $this->option('threshold');
        $this->info("Vérification des patients en attente excessive (seuil: {$threshold}min)...");

        $thresholds = [
            PatientJourney::STATUS_ARRIVED => 20,
            PatientJourney::STATUS_IN_CARE => 10,
            PatientJourney::STATUS_AWAITING_PAYMENT => 5,
        ];

        $escalatedCount = 0;
        foreach ($thresholds as $status => $minutes) {
            $journeys = PatientJourney::where('current_status', $status)
                ->whereNotNull('arrived_at')
                ->where('arrived_at', '<', now()->subMinutes($minutes))
                ->get();

            foreach ($journeys as $journey) {
                $waitMinutes = now()->diffInMinutes($journey->arrived_at);
                
                $priority = QueuePriority::where('appointment_id', $journey->appointment_id)->first();
                
                if (!$priority || $priority->priority_level !== QueuePriority::PRIORITY_CRITICAL) {
                    QueuePriority::updateOrCreate(
                        ['appointment_id' => $journey->appointment_id],
                        [
                            'priority_level' => QueuePriority::PRIORITY_CRITICAL,
                            'override_reason' => "Auto-escalade: {$waitMinutes}min en {$status}",
                            'overridden_at' => now(),
                        ]
                    );
                    
                    $escalatedCount++;
                    $this->warn("  ⚠️  Escaladé: {$journey->appointment->patient?->full_name} ({$waitMinutes}min)");
                }
            }
        }

        Log::info('queue.auto_escalate_completed', ['count' => $escalatedCount, 'threshold' => $threshold]);
        $this->info("✅ {$escalatedCount} patients escaladés.");
    }
}
