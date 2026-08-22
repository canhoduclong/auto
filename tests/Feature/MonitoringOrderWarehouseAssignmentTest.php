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

class MonitoringOrderWarehouseAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_select_packing_warehouse_and_move_inventory_booking(): void
    {
        [$manager, $order, $oldInventory, $newInventory] = $this->createOrderForAssignment();

        $this->actingAs($manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('pages.my_orders.monitoring', [
                'tab' => 'today',
                'view' => 'list',
                'date_field' => 'business_date',
                'date' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Kho đóng hàng')
            ->assertSee('Kho mới')
            ->assertSee(route('pages.my_orders.monitoring.warehouse', $order), false);

        $this->actingAs($manager)
            ->withSession(['active_role' => 'manager'])
            ->put(route('pages.my_orders.monitoring.warehouse', $order), [
                'warehouse_id' => $newInventory->warehouse_id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame((int) $newInventory->warehouse_id, (int) $order->fresh()->warehouse_id);
        $this->assertSame(0, (int) $oldInventory->fresh()->reserved_quantity);
        $this->assertSame(5, (int) $newInventory->fresh()->reserved_quantity);
        $this->assertDatabaseMissing('inventory_reservations', [
            'order_item_id' => $order->items()->value('id'),
            'inventory_id' => $oldInventory->id,
        ]);
        $this->assertDatabaseHas('inventory_reservations', [
            'order_item_id' => $order->items()->value('id'),
            'inventory_id' => $newInventory->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'manager_assign_packing_warehouse',
            'user_id' => $manager->id,
        ]);
    }

    public function test_sale_cannot_select_packing_warehouse(): void
    {
        [, $order, , $newInventory] = $this->createOrderForAssignment();
        $sale = User::factory()->create();
        $sale->roles()->attach(Role::query()->firstOrCreate(['name' => 'sale']));

        $this->actingAs($sale)
            ->put(route('pages.my_orders.monitoring.warehouse', $order), [
                'warehouse_id' => $newInventory->warehouse_id,
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');

        $this->assertNotSame((int) $newInventory->warehouse_id, (int) $order->fresh()->warehouse_id);
    }

    public function test_manager_cannot_change_warehouse_after_packing_has_started(): void
    {
        [$manager, $order, , $newInventory] = $this->createOrderForAssignment();
        $oldWarehouseId = (int) $order->warehouse_id;
        $order->update(['status' => Order::STATUS_PACKING]);

        $this->actingAs($manager)
            ->put(route('pages.my_orders.monitoring.warehouse', $order), [
                'warehouse_id' => $newInventory->warehouse_id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame($oldWarehouseId, (int) $order->fresh()->warehouse_id);
        $this->assertDatabaseMissing('order_histories', [
            'order_id' => $order->id,
            'action' => 'manager_assign_packing_warehouse',
        ]);
    }

    /**
     * @return array{User, Order, Inventory, Inventory}
     */
    private function createOrderForAssignment(): array
    {
        $manager = User::factory()->create();
        $manager->roles()->attach(Role::query()->create(['name' => 'manager']));
        $sale = User::factory()->create();
        $sale->roles()->attach(Role::query()->create(['name' => 'sale']));
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'name' => 'Khách chọn kho',
            'status' => 'active',
        ]);
        $oldWarehouse = Warehouse::query()->create(['name' => 'Kho cũ', 'status' => true]);
        $newWarehouse = Warehouse::query()->create(['name' => 'Kho mới', 'status' => true]);
        $product = Product::query()->create([
            'user_id' => $sale->id,
            'name' => 'Sản phẩm chọn kho',
            'unit' => 'con',
            'status' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => '2.5 kg',
            'sku' => 'WAREHOUSE-ASSIGN-001',
            'kg' => 2.5,
        ]);
        $oldInventory = Inventory::query()->create([
            'warehouse_id' => $oldWarehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'reserved_quantity' => 10,
        ]);
        $newInventory = Inventory::query()->create([
            'warehouse_id' => $newWarehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'reserved_quantity' => 0,
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'warehouse_id' => $oldWarehouse->id,
            'code' => 'WAREHOUSE-ASSIGN-ORDER',
            'status' => Order::STATUS_READY_TO_PACK,
            'delivery_date' => now()->toDateString(),
            'total' => 500000,
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'unit_weight' => 2.5,
            'total_weight' => 25,
            'price' => 50000,
            'total' => 500000,
        ]);
        InventoryReservation::query()->create([
            'order_item_id' => $item->id,
            'inventory_id' => $oldInventory->id,
            'quantity' => 10,
        ]);

        return [$manager, $order, $oldInventory, $newInventory];
    }
}
