<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Quiet chat e-mails: bundle unread order messages into one digest (needs the Laravel scheduler cron on the server)
        $schedule->command('portal:chat-digest')->everyFiveMinutes()->withoutOverlapping();
        // Webhook-free payment safety net: records payments whose customer closed the tab before returning from Stripe
        $schedule->command('portal:reconcile-payments')->everyFiveMinutes()->withoutOverlapping();
        // Google Drive storage: copy new files to Drive every minute, and tidy up old local copies once a night (does nothing until an admin connects Drive)
        $schedule->command('drive:sync')->everyMinute()->withoutOverlapping(10);
        $schedule->command('drive:sync --prune')->dailyAt('03:30')->withoutOverlapping(30);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
