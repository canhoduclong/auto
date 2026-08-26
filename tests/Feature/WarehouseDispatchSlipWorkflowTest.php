<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryDocument;
use App\Models\Order;
use App\Models\OrderTransfer;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseDispatchSlip;
use App\Models\WarehouseInventoryTransfer;
use App\Models\WarehouseTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseDispatchSlipWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_warehouse_can_group_existing_transfers_without_duplicate_inventory_posting(): void
    {
        $warehouseRole = Role::create(['name' => 'warehouse']);
        $shipperRole = Role::create(['name' => 'shipper']);
        $source = Warehouse::create(['name' => 'Kho nguồn']);
        $target = Warehouse::create(['name' => 'Kho nhận']);
        $warehouseUser = User::factory()->create(['warehouse_id' => $source->id]);
        $warehouseUser->roles()->attach($warehouseRole);
        $shipper = User::factory()->create(['name' => 'Tài xế A']);
        $shipper->roles()->attach($shipperRole);
        $customer = Customer::create(['name' => 'Khách A', 'status' => 'active']);

        $orderTransfer = OrderTransfer::create([
            'shipper_id' => $shipper->id,
            'warehouse_id' => $target->id,
            'created_by' => $warehouseUser->id,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $warehouseUser->id,
            'warehouse_id' => $source->id,
            'code' => 'ORD-DISPATCH-1',
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);
        $order->forceFill(['order_transfer_id' => $orderTransfer->id])->save();
        WarehouseTransfer::create([
            'order_id' => $order->id,
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'shipper_id' => $shipper->id,
            'status' => WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
            'packed_total_weight' => 25,
        ]);
        $inventoryTransfer = WarehouseInventoryTransfer::create([
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'requested_by' => $warehouseUser->id,
            'status' => WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE,
            'requested_at' => now(),
        ]);
        $documentCount = InventoryDocument::count();

        $response = $this->actingAs($warehouseUser)->post(route('warehouse.dispatch-slips.store'), [
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'shipper_id' => $shipper->id,
            'business_date' => now()->toDateString(),
            'order_transfer_ids' => [$orderTransfer->id],
            'inventory_transfer_ids' => [$inventoryTransfer->id],
        ]);
        $response->assertSessionHasNoErrors();

        $slip = WarehouseDispatchSlip::query()->sole();
        $response->assertRedirect(route('warehouse.dispatch-slips.show', $slip));
        $this->assertStringStartsWith('PXKT-'.now()->format('Ymd').'-', $slip->code);
        $this->assertSame(2, $slip->entries()->count());
        $this->assertSame($documentCount, InventoryDocument::count(), 'Phiếu tổng không được hạch toán tồn kho lần hai.');

        $movement = WarehouseTransfer::query()->sole();
        $this->actingAs($shipper)
            ->post(route('shipper.warehouse-transfers.pickup', $movement))
            ->assertSessionHas('error', 'Phiếu xuất kho tổng '.$slip->code.' chưa được kho xuất chốt.');
        $this->assertSame(WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP, $movement->fresh()->status);

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.dispatch-slips.finalize', $slip))
            ->assertSessionHas('success');
        $this->assertSame(WarehouseDispatchSlip::STATUS_FINALIZED, $slip->fresh()->status);
        $this->assertTrue($slip->fresh()->entries->every(fn ($entry) => ! empty($entry->snapshot)));
    }

    public function test_destination_warehouse_can_view_and_print_linked_receipt_summary(): void
    {
        $warehouseRole = Role::create(['name' => 'warehouse']);
        $source = Warehouse::create(['name' => 'Kho nguồn']);
        $target = Warehouse::create(['name' => 'Kho nhận']);
        $sourceUser = User::factory()->create(['warehouse_id' => $source->id]);
        $targetUser = User::factory()->create(['warehouse_id' => $target->id]);
        $sourceUser->roles()->attach($warehouseRole);
        $targetUser->roles()->attach($warehouseRole);
        $shipper = User::factory()->create();

        $slip = WarehouseDispatchSlip::create([
            'business_date' => now(),
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'shipper_id' => $shipper->id,
            'status' => WarehouseDispatchSlip::STATUS_FINALIZED,
            'created_by' => $sourceUser->id,
            'finalized_by' => $sourceUser->id,
            'finalized_at' => now(),
        ]);

        $this->actingAs($targetUser)
            ->get(route('warehouse.dispatch-slips.show', $slip))
            ->assertOk()
            ->assertSee($slip->code)
            ->assertSee('0/0 mục đã tiếp nhận');

        $this->actingAs($targetUser)
            ->get(route('warehouse.dispatch-slips.print-import', $slip))
            ->assertOk()
            ->assertSee('PHIẾU NHẬP KHO TỔNG')
            ->assertSee($slip->code);
    }

    public function test_direct_order_transfer_is_loaded_and_can_be_added_to_a_dispatch_slip(): void
    {
        $warehouseRole = Role::create(['name' => 'warehouse']);
        $shipperRole = Role::create(['name' => 'shipper']);
        $source = Warehouse::create(['name' => 'Kho nguồn']);
        $target = Warehouse::create(['name' => 'Kho nhận']);
        $warehouseUser = User::factory()->create(['warehouse_id' => $source->id]);
        $warehouseUser->roles()->attach($warehouseRole);
        $shipper = User::factory()->create(['name' => 'Tài xế trực tiếp']);
        $shipper->roles()->attach($shipperRole);
        $customer = Customer::create(['name' => 'Khách trực tiếp', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $warehouseUser->id,
            'warehouse_id' => $source->id,
            'shipper_id' => $shipper->id,
            'code' => 'ORD-DIRECT-1',
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);
        $transfer = WarehouseTransfer::create([
            'order_id' => $order->id,
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'shipper_id' => $shipper->id,
            'status' => WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
            'packed_total_weight' => 12.5,
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.dispatch-slips.index'))
            ->assertOk()
            ->assertSee('Phiếu điều chuyển đơn')
            ->assertSee('Đơn giao tài xế riêng')
            ->assertSee('ORD-DIRECT-1')
            ->assertSee('SL: 0');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.dispatch-slips.store'), [
                'source_warehouse_id' => $source->id,
                'target_warehouse_id' => $target->id,
                'shipper_id' => $shipper->id,
                'business_date' => now()->toDateString(),
                'warehouse_transfer_ids' => [$transfer->id],
            ])
            ->assertSessionHasNoErrors();

        $slip = WarehouseDispatchSlip::query()->sole();
        $this->assertSame($transfer->id, $slip->entries()->sole()->warehouse_transfer_id);

        $this->actingAs($shipper)
            ->post(route('shipper.warehouse-transfers.pickup', $transfer))
            ->assertSessionHas('error', 'Phiếu xuất kho tổng '.$slip->code.' chưa được kho xuất chốt.');
    }

    public function test_source_warehouse_can_edit_and_delete_a_draft_dispatch_slip(): void
    {
        $warehouseRole = Role::create(['name' => 'warehouse']);
        $shipperRole = Role::create(['name' => 'shipper']);
        $source = Warehouse::create(['name' => 'Kho nguồn']);
        $target = Warehouse::create(['name' => 'Kho nhận']);
        $warehouseUser = User::factory()->create(['warehouse_id' => $source->id]);
        $warehouseUser->roles()->attach($warehouseRole);
        $shipper = User::factory()->create(['name' => 'Tài xế quản trị']);
        $shipper->roles()->attach($shipperRole);
        $customer = Customer::create(['name' => 'Khách quản trị', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $warehouseUser->id,
            'warehouse_id' => $source->id,
            'code' => 'ORD-MANAGE-1',
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);
        $transfer = WarehouseTransfer::create([
            'order_id' => $order->id,
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'shipper_id' => $shipper->id,
            'status' => WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
            'packed_total_weight' => 8,
        ]);
        $slip = WarehouseDispatchSlip::create([
            'business_date' => now(),
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'shipper_id' => $shipper->id,
            'status' => WarehouseDispatchSlip::STATUS_DRAFT,
            'created_by' => $warehouseUser->id,
        ]);
        $slip->entries()->create(['warehouse_transfer_id' => $transfer->id]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.dispatch-slips.index'))
            ->assertOk()
            ->assertSee(route('warehouse.dispatch-slips.edit', $slip))
            ->assertSee('Xóa');

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.dispatch-slips.edit', $slip))
            ->assertOk()
            ->assertSee('ORD-MANAGE-1');

        $this->actingAs($warehouseUser)
            ->put(route('warehouse.dispatch-slips.update', $slip), [
                'target_warehouse_id' => $target->id,
                'shipper_id' => $shipper->id,
                'business_date' => now()->subDay()->toDateString(),
                'notes' => 'Đã cập nhật ghi chú',
                'warehouse_transfer_ids' => [$transfer->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('warehouse.dispatch-slips.show', $slip));
        $this->assertSame('Đã cập nhật ghi chú', $slip->fresh()->notes);
        $this->assertSame($transfer->id, $slip->fresh()->entries()->sole()->warehouse_transfer_id);

        $this->actingAs($warehouseUser)
            ->delete(route('warehouse.dispatch-slips.destroy', $slip))
            ->assertRedirect(route('warehouse.dispatch-slips.index'));
        $this->assertDatabaseMissing('warehouse_dispatch_slips', ['id' => $slip->id]);
        $this->assertNull($transfer->fresh()->dispatchEntry);
    }
}
