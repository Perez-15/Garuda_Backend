<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * Runs daily at midnight (server time).
     */
    protected function schedule(Schedule $schedule): void
    {
        // Permanently purge applicants and employees that have been
        // soft-deleted for more than 30 days. Runs every day at midnight.
        $schedule->command('garuda:purge-deleted --days=30')
                 ->dailyAt('00:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/purge-deleted.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}