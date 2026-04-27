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
        \App\Console\Commands\ProcessDailyOrderSchedulesCommand::class,
        \App\Console\Commands\EvaluateOrderSchedulesCommand::class,
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
        // Materialize daily auto-order rules and create/review orders for today
        $schedule->command('order-schedules:process-daily-rules')->dailyAt('00:14');
        // Evaluate scheduled orders (price/stock) and auto-generate valid ones
        $schedule->command('order-schedules:evaluate-today')->dailyAt('00:15');
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
