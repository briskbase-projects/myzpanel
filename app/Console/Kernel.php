<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        Commands\ImportOrders::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('import:orders')
            ->everyFiveMinutes()->appendOutputTo(storage_path('logs/order-worker.log'));

        $schedule->command('queue:work --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/queue-worker.log'));

        // Google Sheets sync - runs every 15 minutes
        $schedule->job(new \App\Jobs\SyncOrdersToGoogleSheet)
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/google-sheets-sync.log'));

        // Restart the queue worker to prevent memory leaks
        // $schedule->command('queue:restart')
        //     ->everyFiveMinutes();

        // $schedule->command('check:live')
        //     ->everyThirtyMinutes();
        // $schedule->command('inspire')->hourly();
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
