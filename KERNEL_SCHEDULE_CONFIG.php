// Add to: app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Escalade automatique des patients en attente chaque 5 minutes
    $schedule->command('queue:auto-escalate-waiting', ['--threshold=20'])
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->runInBackground();

    // Vérification des tâches dues chaque matin à 8h
    $schedule->command('tasks:check-due')
        ->dailyAt('08:00')
        ->runInBackground();

    // Archivage des sessions clôturées chaque jour à 23h
    $schedule->command('cash:archive-closed-sessions')
        ->dailyAt('23:00')
        ->runInBackground();
}
