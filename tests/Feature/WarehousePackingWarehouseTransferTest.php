<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehousePackingWarehouseTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_warehouse_can_move_a_short_order_to_another_packing_warehouse(): void
    {
        [$sourceUser, $targetUser, $order, $sourceInventory, $targetInventory] = $this->createShortOrder();

        $this->actingAs($sourceUser)
            ->withSession(['active_role' => 'warehouse'])
            ->post(route('warehouse.orders.transfer-packing-warehouse', $order), [
                'warehouse_id' => $targetInventory->warehouse_id,
                'packing_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame((int) $targetInventory->warehouse_id, (int) $order->fresh()->warehouse_id);
        $this->assertSame(0, (int) $sourceInventory->fresh()->reserved_quantity);
        $this->assertSame(5, (int) $targetInventory->fresh()->reserved_quantity);
        $this->assertDatabaseMissing('inventory_reservations', [
            'order_item_id' => $order->items()->value('id'),
            'inventory_id' => $sourceInventory->id,
        ]);
        $this->assertDatabaseHas('inventory_reservations', [
            'order_item_id' => $order->items()->value('id'),
            'inventory_id' => $targetInventory->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'warehouse_transfer_packing_warehouse',
            'user_id' => $sourceUser->id,
        ]);

        $this->assertFalse(Order::query()->whereKey($order->id)->where('warehouse_id', $sourceInventory->warehouse_id)->exists());
        $this->assertTrue(Order::query()->whereKey($order->id)->where('warehouse_id', $targetUser->warehouse_id)->exists());
    }

    public function test_warehouse_cannot_move_order_to_itself_or_move_another_warehouses_order(): void
    {
        [$sourceUser, $targetUser, $order, $sourceInventory, $targetInventory] = $this->createShortOrder();

        $this->actingAs($sourceUser)
            ->post(route('warehouse.orders.transfer-packing-warehouse', $order), [
                'warehouse_id' => $sourceInventory->warehouse_id,
                'packing_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('warehouse_id');

        $this->actingAs($targetUser)
            ->post(route('warehouse.orders.transfer-packing-warehouse', $order), [
                'warehouse_id' => $sourceInventory->warehouse_id,
                'packing_date' => now()->toDateString(),
            ])
            ->assertSessionHas('error');

        $this->assertSame((int) $sourceInventory->warehouse_id, (int) $order->fresh()->warehouse_id);
        $this->assertSame(2, (int) $sourceInventory->fresh()->reserved_quantity);
        $this->assertSame(0, (int) $targetInventory->fresh()->reserved_quantity);
    }

    public function test_shortage_card_only_offers_other_warehouses(): void
    {
        $card = file_get_contents(resource_path('views/warehouse/orders/_order_card.blade.php'));

        $this->assertStringContainsString('Điều chuyển hàng', $card);
        $this->assertStringContainsString("route('warehouse.orders.transfer-packing-warehouse', \$order)", $card);
        $this->assertStringContainsString('(int) $warehouse->id !== $currentWorkingWarehouseId', $card);
        $this->assertStringContainsString('$stockShortages->isNotEmpty()', $card);
        $this->assertStringContainsString('(int) ($order->warehouse_id ?? 0) === $currentWorkingWarehouseId', $card);
    }

    /** @return array{User, User, Order, Inventory, Inventory} */
    private function createShortOrder(): array
    {
        $warehouseRole = Role::query()->create(['name' => 'warehouse']);
        $sourceWarehouse = Warehouse::query()->create(['name' => 'Kho Long An', 'status' => true]);
        $targetWarehouse = Warehouse::query()->create(['name' => 'Kho đích', 'status' => true]);
        $sourceUser = User::factory()->create(['warehouse_id' => $sourceWarehouse->id]);
        $sourceUser->roles()->attach($warehouseRole);
        $targetUser = User::factory()->create(['warehouse_id' => $targetWarehouse->id]);
        $targetUser->roles()->attach($warehouseRole);
        $sale = User::factory()->create();
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'name' => 'Khách điều chuyển đóng hàng',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'user_id' => $sale->id,
            'name' => 'Sản phẩm thiếu tại Long An',
            'unit' => 'con',
            'status' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => '2.5 kg',
            'sku' => 'WH-PACKING-TRANSFER-001',
            'kg' => 2.5,
        ]);
        $sourceInventory = Inventory::query()->create([
            'warehouse_id' => $sourceWarehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'reserved_quantity' => 2,
        ]);
        $targetInventory = Inventory::query()->create([
            'warehouse_id' => $targetWarehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'warehouse_id' => $sourceWarehouse->id,
            'code' => 'WH-PACKING-TRANSFER-ORDER',
            'status' => Order::STATUS_READY_TO_PACK,
            'delivery_date' => now()->toDateString(),
            'skip_auto_cancel' => true,
            'total' => 500000,
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'unit_weight' => 2.5,
            'total_weight' => 12.5,
            'price' => 100000,
            'total' => 500000,
        ]);
        InventoryReservation::query()->create([
            'order_item_id' => $item->id,
            'inventory_id' => $sourceInventory->id,
            'quantity' => 2,
            'reserved_at' => now(),
        ]);

        return [$sourceUser, $targetUser, $order, $sourceInventory, $targetInventory];
    }
}
