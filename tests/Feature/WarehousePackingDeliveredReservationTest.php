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
            now()->toDateString()
        );

        $this->assertFalse($result['guards'][$waitingOrder->id]['has_shortage']);
        $this->assertTrue($result['guards'][$waitingOrder->id]['can_start_packing']);
        $this->assertSame([], $result['guards'][$waitingOrder->id]['shortages']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
