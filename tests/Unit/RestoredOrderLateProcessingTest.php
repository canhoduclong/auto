<?php

namespace Tests\Unit;

use App\Http\Controllers\WarehouseDashboardController;
use App\Models\Order;
use App\Models\OrderAdjustment;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RestoredOrderLateProcessingTest extends TestCase
{
    public function test_restored_old_order_can_continue_warehouse_processing(): void
    {
        Carbon::setTestNow('2026-08-22 10:00:00');

        try {
            $order = new Order();
            $order->setRawAttributes([
                'created_at' => Carbon::parse('2026-08-21 08:00:00'),
                'skip_auto_cancel' => 1,
                'accounting_sales_import_batch_id' => null,
            ], true);
            $order->setRelation('adjustments', collect());

            $method = new ReflectionMethod(WarehouseDashboardController::class, 'canProcessOrderOnCurrentRun');

            $this->assertTrue($method->invoke(new WarehouseDashboardController(), $order));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_normal_old_order_remains_blocked_from_current_run(): void
    {
        Carbon::setTestNow('2026-08-22 10:00:00');

        try {
            $order = new Order();
            $order->setRawAttributes([
                'created_at' => Carbon::parse('2026-08-21 08:00:00'),
                'skip_auto_cancel' => 0,
                'accounting_sales_import_batch_id' => null,
            ], true);
            $order->setRelation('adjustments', collect());

            $method = new ReflectionMethod(WarehouseDashboardController::class, 'canProcessOrderOnCurrentRun');

            $this->assertFalse($method->invoke(new WarehouseDashboardController(), $order));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_old_order_with_completed_adjustment_can_continue_warehouse_processing(): void
    {
        Carbon::setTestNow('2026-08-25 11:00:00');

        try {
            $order = new Order();
            $order->setRawAttributes([
                'created_at' => Carbon::parse('2026-08-23 12:00:00'),
                'skip_auto_cancel' => 0,
                'accounting_sales_import_batch_id' => null,
            ], true);
            $order->setRelation('adjustments', collect([
                new OrderAdjustment(['status' => OrderAdjustment::STATUS_COMPLETED]),
            ]));

            $method = new ReflectionMethod(WarehouseDashboardController::class, 'canProcessOrderOnCurrentRun');

            $this->assertTrue($order->hasCompletedAdjustment());
            $this->assertTrue($method->invoke(new WarehouseDashboardController(), $order));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_auto_cancel_command_excludes_orders_with_completed_adjustments(): void
    {
        $command = file_get_contents(__DIR__.'/../../app/Console/Commands/AutoCancelOverdueOrders.php');

        $this->assertStringContainsString("->whereDoesntHave('adjustments'", $command);
        $this->assertStringContainsString('OrderAdjustment::STATUS_COMPLETED', $command);
    }
}
