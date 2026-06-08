<?php

namespace Modules\Appointment\Console\Commands;

use Illuminate\Console\Command;
use Modules\Appointment\Models\SecretaryTask;
use Illuminate\Support\Facades\Log;

class CheckDueTasksCommand extends Command
{
    protected $signature = 'tasks:check-due';
    protected $description = 'Notifie les secrétaires des tâches dues/retardataires';

    public function handle()
    {
        $this->info("Vérification des tâches dues...");

        // Tâches dues aujourd'hui
        $dueTodayCount = SecretaryTask::where('status', 'open')
            ->whereDate('due_at', today())
            ->count();

        // Tâches en retard
        $overdueCount = SecretaryTask::where('status', 'open')
            ->whereDate('due_at', '<', today())
            ->count();

        if ($dueTodayCount > 0) {
            $this->warn("⏰ {$dueTodayCount} tâches dues aujourd'hui");
        }

        if ($overdueCount > 0) {
            $this->error("🚨 {$overdueCount} tâches en retard!");
            Log::warning('tasks.overdue_detected', ['count' => $overdueCount]);
        }

        $this->info("✅ Vérification complète.");
    }
}
