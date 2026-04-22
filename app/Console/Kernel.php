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
        \App\Console\Commands\SyncPermissionsFromRoutes::class,
        \App\Console\Commands\ExportProductsToSeederArray::class,
        \App\Console\Commands\ExportCategoriesToSeederArray::class,
        \App\Console\Commands\AutoCancelOverdueOrders::class,
        \App\Console\Commands\ReconcileInventoryReservations::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Auto-cancel overdue orders every day at 00:05
        $schedule->command('orders:auto-cancel-overdue')->dailyAt('00:05');
        // Reconcile reserved_quantity drift every day at 00:10 (after auto-cancel)
        $schedule->command('inventory:reconcile-reservations')->dailyAt('00:10');
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
