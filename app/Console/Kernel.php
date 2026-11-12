<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('prodigi:sync-catalog --seed-user-preferences')
            ->daily()
            ->withoutOverlapping();

        $schedule->command('prodigi:sync-orders')
            ->everyThirtyMinutes()
            ->withoutOverlapping();

        // Custom domains: pending ones often, live ones daily, plus hourly for
        // live domains already failing so they recover (or go offline) promptly.
        $schedule->command('videos:clean-uploads')->daily()->withoutOverlapping();
        $schedule->command('custom-domains:verify --pending')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('custom-domains:verify --verified')->daily()->withoutOverlapping();
        $schedule->command('custom-domains:verify --failing')->hourly()->withoutOverlapping();
        $schedule->command('custom-domains:verify --securing')->everyTwoMinutes()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
