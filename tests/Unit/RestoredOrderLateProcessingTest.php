<?php

namespace Tests\Unit;

use App\Http\Controllers\WarehouseDashboardController;
use App\Models\Order;
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

            $method = new ReflectionMethod(WarehouseDashboardController::class, 'canProcessOrderOnCurrentRun');

            $this->assertFalse($method->invoke(new WarehouseDashboardController(), $order));
        } finally {
            Carbon::setTestNow();
        }
    }
}
