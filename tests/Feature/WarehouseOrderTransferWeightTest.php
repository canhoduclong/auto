<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderTransfer;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseOrderTransferWeightTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_orders_packed_in_selected_range_are_listed_only_for_their_warehouse(): void
    {
        $role = Role::create(['name' => 'warehouse']);
        $source = Warehouse::factory()->create();
        $other = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $source->id]);
        $user->roles()->attach($role);
        $customer = Customer::create(['name' => 'Khách đơn cũ vừa đóng', 'status' => 'active']);
        $ids = [];
        foreach ([$source->id, $other->id] as $warehouseId) {
            $order = Order::create([
                'customer_id' => $customer->id, 'user_id' => $user->id,
                'warehouse_id' => $warehouseId, 'status' => Order::STATUS_READY_TO_SHIP,
                'delivery_date' => '2026-07-22',
            ]);
            $order->forceFill(['created_at' => '2026-07-21 10:00:00'])->saveQuietly();
            $order->histories()->create([
                'action' => 'complete_packing', 'user_id' => $user->id, 'role' => 'warehouse',
                'status_before' => Order::STATUS_PACKING, 'status_after' => Order::STATUS_READY_TO_SHIP,
                'created_at' => '2026-09-07 10:00:00',
            ]);
            $ids[] = $order->id;
        }
        $this->actingAs($user)->get(route('warehouse.order-transfers', [
            'from_date' => '2026-09-07', 'to_date' => '2026-09-07',
        ]))->assertOk()->assertViewHas('orders', fn ($orders) => $orders->pluck('id')->all() === [$ids[0]]);
        $this->get(route('warehouse.order-transfers', [
            'from_date' => '2026-09-08', 'to_date' => '2026-09-08',
        ]))->assertOk()->assertViewHas('orders', fn ($orders) => $orders->isEmpty());
    }

    public function test_all_packed_orders_are_listed_including_legacy_completion_history(): void
    {
        $role = Role::create(['name' => 'warehouse']);
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach($role);
        $customer = Customer::create(['name' => 'Khách đủ danh sách', 'status' => 'active']);
        $ids = [];
        for ($i = 0; $i < 25; $i++) {
            $order = Order::create([
                'customer_id' => $customer->id, 'user_id' => $user->id,
                'warehouse_id' => $warehouse->id, 'status' => Order::STATUS_READY_TO_SHIP,
                'delivery_date' => '2026-07-22',
            ]);
            $order->forceFill(['created_at' => '2026-07-21 10:00:00'])->saveQuietly();
            $order->histories()->create([
                'action' => 'warehouse_complete_packing', 'user_id' => $user->id, 'role' => 'warehouse',
                'status_before' => Order::STATUS_PACKING, 'status_after' => Order::STATUS_READY_TO_SHIP,
                'created_at' => '2026-09-07 10:00:00',
            ]);
            $ids[] = $order->id;
        }
        $packing = $order->replicate();
        $packing->forceFill(['code' => 'STILL-PACKING', 'status' => Order::STATUS_PACKING, 'created_at' => '2026-09-07 10:00:00'])->saveQuietly();
        $this->actingAs($user)->get(route('warehouse.order-transfers', [
            'from_date' => '2026-09-07', 'to_date' => '2026-09-07', 'orders_page' => 2,
        ]))->assertOk()->assertViewHas('orders', fn ($orders) => $orders->pluck('id')->all() === $ids)
            ->assertDontSee('STILL-PACKING');
    }

    public function test_imported_packed_order_is_visible_by_creation_date_even_with_old_delivery_date(): void
    {
        $role = Role::create(['name' => 'warehouse']);
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach($role);
        $customer = Customer::create(['name' => 'Quán Gò Vấp', 'status' => 'active']);
        $batch = \App\Models\AccountingSalesImportBatch::create([
            'source_hash' => 'transfer-old-delivery-date', 'raw_text' => '', 'imported_by' => $user->id,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id, 'user_id' => $user->id,
            'warehouse_id' => $warehouse->id, 'status' => Order::STATUS_READY_TO_SHIP,
            'delivery_date' => '2026-07-22', 'accounting_sales_import_batch_id' => $batch->id,
        ]);
        $order->forceFill(['created_at' => '2026-09-07 13:18:00'])->saveQuietly();
        $this->actingAs($user)->get(route('warehouse.order-transfers', [
            'from_date' => '2026-09-07', 'to_date' => '2026-09-07',
        ]))->assertOk()->assertViewHas('orders', fn ($orders) => $orders->contains('id', $order->id));
    }

    public function test_batch_order_transfer_snapshots_packed_weight_for_future_loss_calculation(): void
    {
        $warehouseRole = Role::create(['name' => 'warehouse']);
        $shipperRole = Role::create(['name' => 'shipper']);
        $source = Warehouse::factory()->create();
        $target = Warehouse::factory()->create();
        $warehouseUser = User::factory()->create(['warehouse_id' => $source->id]);
        $warehouseUser->roles()->attach($warehouseRole);
        $shipper = User::factory()->create();
        $shipper->roles()->attach($shipperRole);
        $customer = Customer::create([
            'name' => 'Khách kiểm tra kg điều chuyển',
            'phone' => '0900777666',
            'status' => 'active',
        ]);
        $variant = ProductVariant::factory()->create(['kg' => 2.5]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $warehouseUser->id,
            'warehouse_id' => $source->id,
            'code' => 'ORDER-TRANSFER-WEIGHT',
            'status' => Order::STATUS_READY_TO_SHIP,
            'total' => 0,
        ]);
        $order->items()->create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'price' => 0,
            'packed_weight' => 7.35,
            'total_weight' => 7.5,
            'unit_weight' => 2.5,
        ]);

        $unassigned = $order->replicate();
        $unassigned->fill(['code' => 'TRANSFER-NO-WAREHOUSE', 'warehouse_id' => null])->save();
        $otherWarehouse = $order->replicate();
        $otherWarehouse->fill(['code' => 'TRANSFER-OTHER-WAREHOUSE', 'warehouse_id' => $target->id])->save();

        $this->actingAs($warehouseUser)->get(route('warehouse.order-transfers'))
            ->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->pluck('id')->all() === [$order->id])
            ->assertDontSee('TRANSFER-NO-WAREHOUSE')
            ->assertDontSee('TRANSFER-OTHER-WAREHOUSE');

        $this->post(route('warehouse.order-transfers.store'), [
            'shipper_id' => $shipper->id,
            'warehouse_id' => $target->id,
            'order_ids' => $order->id.','.$unassigned->id,
        ])->assertSessionHasErrors('order_ids');
        $this->assertNull($order->fresh()->order_transfer_id);
        $this->assertDatabaseCount('warehouse_transfers', 0);

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.order-transfers.store'), [
                'shipper_id' => $shipper->id,
                'warehouse_id' => $target->id,
                'order_ids' => (string) $order->id,
            ])
            ->assertRedirect(route('warehouse.order-transfers'));

        $this->post(route('warehouse.order-transfers.store'), [
            'shipper_id' => $shipper->id,
            'warehouse_id' => $target->id,
            'order_ids' => (string) $order->id,
        ])->assertSessionHasErrors('order_ids')
            ->assertSessionHas('errors', fn ($errors) => str_contains($errors->first('order_ids'), 'đã thuộc phiếu điều chuyển'));
        $this->assertDatabaseCount('warehouse_transfers', 1);

        $this->assertDatabaseHas('warehouse_transfers', [
            'order_id' => $order->id,
            'shipper_id' => $shipper->id,
            'status' => WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
            'packed_total_weight' => 7.35,
        ]);

        $firstGroupId = $order->fresh()->order_transfer_id;
        $nextOrder = $order->fresh()->replicate();
        $nextOrder->fill(['code' => 'SEPARATE-TRANSFER'])->forceFill(['order_transfer_id' => null])->save();
        $this->post(route('warehouse.order-transfers.store'), [
            'shipper_id' => $shipper->id,
            'warehouse_id' => $target->id,
            'order_ids' => (string) $nextOrder->id,
        ])->assertRedirect(route('warehouse.order-transfers'));
        $this->assertNotEquals($firstGroupId, $nextOrder->fresh()->order_transfer_id);
        $this->assertSame($firstGroupId, $order->fresh()->order_transfer_id);
        $this->assertSame(1, Order::where('order_transfer_id', $firstGroupId)->count());
        $this->assertDatabaseCount('order_transfers', 2);
    }

    public function test_single_order_transfer_request_creates_a_new_slip_for_each_order(): void
    {
        $warehouseRole = Role::create(['name' => 'warehouse']);
        $shipperRole = Role::create(['name' => 'shipper']);
        $source = Warehouse::factory()->create();
        $target = Warehouse::factory()->create();
        $warehouseUser = User::factory()->create(['warehouse_id' => $source->id]);
        $warehouseUser->roles()->attach($warehouseRole);
        $shipper = User::factory()->create();
        $shipper->roles()->attach($shipperRole);
        $customer = Customer::create(['name' => 'Khách tạo phiếu riêng', 'status' => 'active']);

        $orders = collect(['SINGLE-TRANSFER-1', 'SINGLE-TRANSFER-2'])->map(function (string $code) use ($customer, $source, $warehouseUser) {
            return Order::create([
                'customer_id' => $customer->id,
                'user_id' => $warehouseUser->id,
                'warehouse_id' => $source->id,
                'code' => $code,
                'status' => Order::STATUS_READY_TO_SHIP,
            ]);
        });

        foreach ($orders as $order) {
            $this->actingAs($warehouseUser)
                ->post(route('warehouse.orders.transfer-request', $order), [
                    'target_warehouse_id' => $target->id,
                    'shipper_id' => $shipper->id,
                ])
                ->assertSessionHas('success');
        }

        $transferIds = $orders->map(fn (Order $order) => $order->fresh()->order_transfer_id);
        $this->assertCount(2, $transferIds->unique());
        $this->assertSame(2, OrderTransfer::query()->count());
        $this->assertSame(2, WarehouseTransfer::query()->count());
        $this->assertSame(1, Order::where('order_transfer_id', $transferIds->first())->count());
        $this->assertSame(1, Order::where('order_transfer_id', $transferIds->last())->count());
    }
}
