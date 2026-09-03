<?php

namespace Tests\Feature;

use App\Http\Controllers\WarehouseDashboardController;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class WarehousePackingDeliveredReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivered_order_reservation_does_not_block_current_packing_order(): void
    {
        Carbon::setTestNow('2026-08-25 10:00:00');

        $user = User::factory()->create();
        $customer = Customer::query()->create(['name' => 'Khách kiểm tra FIFO', 'status' => 'active']);
        $warehouse = Warehouse::query()->create(['name' => 'Kho FIFO', 'status' => true]);
        $product = Product::query()->create([
            'user_id' => $user->id,
            'name' => 'Sản phẩm FIFO',
            'unit' => 'cái',
            'status' => true,
        ]);
        $variant = $product->variants()->create([
            'name' => '2.5 kg',
            'sku' => 'FIFO-DELIVERED-2-5',
            'kg' => 2.5,
        ]);
        $inventory = Inventory::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 4,
            'reserved_quantity' => 4,
        ]);

        $deliveredOrder = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'FIFO-DELIVERED',
            'status' => Order::STATUS_DELIVERED,
            'skip_auto_cancel' => true,
        ]);
        $deliveredOrder->forceFill(['created_at' => '2026-08-23 08:00:00'])->saveQuietly();
        $deliveredItem = $deliveredOrder->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 4,
            'price' => 10000,
            'total' => 40000,
            'is_priced_by_kg' => false,
        ]);
        InventoryReservation::query()->create([
            'order_item_id' => $deliveredItem->id,
            'inventory_id' => $inventory->id,
            'quantity' => 4,
        ]);

        $waitingOrder = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'FIFO-WAITING',
            'status' => Order::STATUS_READY_TO_PACK,
            'skip_auto_cancel' => true,
        ]);
        $waitingOrder->forceFill(['created_at' => '2026-08-23 12:00:00'])->saveQuietly();
        $waitingOrder->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 4,
            'price' => 10000,
            'total' => 40000,
            'is_priced_by_kg' => false,
        ]);

        $method = new ReflectionMethod(WarehouseDashboardController::class, 'buildPackingQueueStockGuards');
        $result = $method->invoke(
            app(WarehouseDashboardController::class),
            collect([$waitingOrder]),
            $warehouse->id,
            '2026-08-23'
        );

        $this->assertFalse($result['guards'][$waitingOrder->id]['has_shortage']);
        $this->assertTrue($result['guards'][$waitingOrder->id]['can_start_packing']);
        $this->assertSame([], $result['guards'][$waitingOrder->id]['shortages']);
    }

    public function test_reservation_from_another_day_does_not_reduce_selected_days_packing_stock(): void
    {
        Carbon::setTestNow('2026-08-25 10:00:00');

        $user = User::factory()->create();
        $customer = Customer::query()->create(['name' => 'Khách tách tồn theo ngày', 'status' => 'active']);
        $warehouse = Warehouse::query()->create(['name' => 'Kho tách ngày', 'status' => true]);
        $product = Product::query()->create([
            'user_id' => $user->id,
            'name' => 'Sản phẩm tách ngày',
            'unit' => 'cái',
            'status' => true,
        ]);
        $variant = $product->variants()->create([
            'name' => 'Loại ngày',
            'sku' => 'FIFO-SEPARATE-DATE',
        ]);
        $inventory = Inventory::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'reserved_quantity' => 5,
        ]);

        $previousOrder = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'PREVIOUS-DAY',
            'status' => Order::STATUS_READY_TO_PACK,
        ]);
        $previousOrder->forceFill(['created_at' => '2026-08-24 08:00:00'])->saveQuietly();
        $previousItem = $previousOrder->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'price' => 10000,
            'total' => 50000,
            'is_priced_by_kg' => false,
        ]);
        InventoryReservation::query()->create([
            'order_item_id' => $previousItem->id,
            'inventory_id' => $inventory->id,
            'quantity' => 5,
        ]);

        $todayOrder = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'CURRENT-DAY',
            'status' => Order::STATUS_READY_TO_PACK,
        ]);
        $todayOrder->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'price' => 10000,
            'total' => 50000,
            'is_priced_by_kg' => false,
        ]);

        $method = new ReflectionMethod(WarehouseDashboardController::class, 'buildPackingQueueStockGuards');
        $result = $method->invoke(
            app(WarehouseDashboardController::class),
            collect([$todayOrder]),
            $warehouse->id,
            '2026-08-25'
        );

        $this->assertTrue($result['guards'][$todayOrder->id]['can_start_packing']);
        $this->assertSame(0.0, $result['remaining_by_variant'][$variant->id]);

        $blockerMethod = new ReflectionMethod(WarehouseDashboardController::class, 'reservationBlockingOrders');
        $blockers = $blockerMethod->invoke(
            app(WarehouseDashboardController::class),
            $todayOrder,
            $warehouse->id,
            '2026-08-25'
        );

        $this->assertCount(1, $blockers);
        $this->assertSame('PREVIOUS-DAY', $blockers[0]['order_code']);
        $this->assertFalse($blockers[0]['same_packing_date']);
        $this->assertSame(5.0, $blockers[0]['reserved_qty']);
    }

    public function test_orders_on_the_same_day_still_share_one_fifo_stock_pool(): void
    {
        Carbon::setTestNow('2026-08-25 10:00:00');

        $user = User::factory()->create();
        $customer = Customer::query()->create(['name' => 'Khách FIFO cùng ngày', 'status' => 'active']);
        $warehouse = Warehouse::query()->create(['name' => 'Kho FIFO cùng ngày', 'status' => true]);
        $product = Product::query()->create([
            'user_id' => $user->id,
            'name' => 'Sản phẩm FIFO cùng ngày',
            'unit' => 'cái',
            'status' => true,
        ]);
        $variant = $product->variants()->create([
            'name' => 'Loại FIFO',
            'sku' => 'FIFO-SAME-DATE',
        ]);
        Inventory::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'reserved_quantity' => 0,
        ]);

        $orders = collect();
        foreach (['08:00:00', '09:00:00'] as $index => $time) {
            $order = Order::query()->create([
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'warehouse_id' => $warehouse->id,
                'code' => 'SAME-DAY-'.($index + 1),
                'status' => Order::STATUS_READY_TO_PACK,
            ]);
            $order->forceFill(['created_at' => '2026-08-25 '.$time])->saveQuietly();
            $order->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 3,
                'price' => 10000,
                'total' => 30000,
                'is_priced_by_kg' => false,
            ]);
            $orders->push($order);
        }

        $method = new ReflectionMethod(WarehouseDashboardController::class, 'buildPackingQueueStockGuards');
        $result = $method->invoke(
            app(WarehouseDashboardController::class),
            $orders,
            $warehouse->id,
            '2026-08-25'
        );

        $this->assertTrue($result['guards'][$orders[0]->id]['can_start_packing']);
        $this->assertFalse($result['guards'][$orders[1]->id]['can_start_packing']);
        $this->assertSame(2.0, $result['guards'][$orders[1]->id]['shortages'][0]['available_qty']);
        $this->assertSame('blocked_by_prior_order', $result['guards'][$orders[1]->id]['shortages'][0]['reason']);
        $this->assertSame(
            [[
                'order_id' => $orders[0]->id,
                'order_code' => 'SAME-DAY-1',
                'daily_sequence' => null,
                'customer_name' => 'Khách FIFO cùng ngày',
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => 'Kho FIFO cùng ngày',
                'packing_date' => '2026-08-25',
                'consumed_qty' => 3.0,
            ]],
            $result['guards'][$orders[1]->id]['shortages'][0]['blocking_orders']
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
