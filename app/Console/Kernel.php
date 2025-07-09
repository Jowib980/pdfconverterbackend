<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        // Loads commands in app/Console/Commands
        $this->load(__DIR__ . '/Commands');

        // Optional: you can include route-based console commands
        require base_path('routes/console.php');
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Run your custom cleanup command every hour
        $schedule->command('downloads:clean')->everyMinute();

        // Example: schedule other built-in or custom commands
        // $schedule->command('inspire')->daily();
    }

    /**
     * Register Artisan commands.
     */
    protected $commands = [
        \App\Console\Commands\CleanExpiredDownloads::class, // ✅ Register your custom command
    ];
}
