<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseDispatchSlip;
use App\Models\WarehouseDispatchSlipEntry;
use App\Models\WarehouseTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipperWarehouseTransferVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_shipper_sees_active_transfer_even_when_order_delivery_date_is_different(): void
    {
        $shipperRole = Role::create(['name' => 'shipper']);
        $shipper = User::factory()->create(['name' => 'Ship Dương kiểm thử']);
        $shipper->roles()->attach($shipperRole);
        $source = Warehouse::factory()->create();
        $target = Warehouse::factory()->create();
        $customer = Customer::create([
            'name' => 'Khách điều chuyển khác ngày',
            'phone' => '0900999888',
            'status' => 'active',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $shipper->id,
            'warehouse_id' => $source->id,
            'code' => 'TRANSFER-DIFFERENT-DATE',
            'status' => Order::STATUS_READY_TO_SHIP,
            'delivery_date' => now()->subDay()->toDateString(),
            'total' => 0,
        ]);
        $transfer = WarehouseTransfer::create([
            'order_id' => $order->id,
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'shipper_id' => $shipper->id,
            'status' => WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
        ]);
        $slip = WarehouseDispatchSlip::create([
            'business_date' => now()->toDateString(),
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'shipper_id' => $shipper->id,
            'status' => WarehouseDispatchSlip::STATUS_FINALIZED,
            'created_by' => $shipper->id,
            'finalized_by' => $shipper->id,
            'finalized_at' => now(),
        ]);
        WarehouseDispatchSlipEntry::create([
            'warehouse_dispatch_slip_id' => $slip->id,
            'warehouse_transfer_id' => $transfer->id,
            'snapshot' => ['type' => 'warehouse_transfer'],
        ]);

        $this->actingAs($shipper)
            ->get(route('shipper.warehouse-transfers', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('</i> Điều chuyển', false)
            ->assertDontSee('</i> Điều chuyển kho', false)
            ->assertSee('Danh sách phiếu điều chuyển')
            ->assertSee($slip->code)
            ->assertSee(route('shipper.warehouse-transfers.show', $slip), false)
            ->assertDontSee('TRANSFER-DIFFERENT-DATE');

        $this->actingAs($shipper)
            ->get(route('shipper.warehouse-transfers.show', $slip))
            ->assertOk()
            ->assertSee('TRANSFER-DIFFERENT-DATE')
            ->assertSee('Khách điều chuyển khác ngày')
            ->assertSee($slip->code)
            ->assertSee('data-bs-target="#transfer-details-'.$transfer->id.'"', false)
            ->assertSee('id="transfer-details-'.$transfer->id.'"', false)
            ->assertSee('Chấp nhận')
            ->assertSee('Chi tiết')
            ->assertSee(route('shipper.warehouse-transfers.pickup', $transfer), false)
            ->assertSee("'Accept': 'application/json'", false);

        $this->actingAs($shipper)
            ->postJson(route('shipper.warehouse-transfers.pickup', $transfer))
            ->assertOk()
            ->assertJsonPath('transfer_id', $transfer->id)
            ->assertJsonPath('status', WarehouseTransfer::STATUS_IN_TRANSIT);

        $this->assertDatabaseHas('warehouse_transfers', [
            'id' => $transfer->id,
            'status' => WarehouseTransfer::STATUS_IN_TRANSIT,
        ]);

        $this->actingAs($shipper)
            ->get(route('shipper.warehouse-transfers.show', $slip))
            ->assertOk()
            ->assertSee('Giao Hàng')
            ->assertSee('js-quick-deliver-form', false)
            ->assertSee(route('shipper.warehouse-transfers.deliver', $transfer), false);

        $this->actingAs($shipper)
            ->postJson(route('shipper.warehouse-transfers.deliver', $transfer))
            ->assertOk()
            ->assertJsonPath('transfer_id', $transfer->id)
            ->assertJsonPath('status', WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE);

        $this->assertDatabaseHas('warehouse_transfers', [
            'id' => $transfer->id,
            'status' => WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
        ]);
    }

    public function test_target_warehouse_sees_waiting_transfer_even_when_delivery_date_is_tomorrow(): void
    {
        $warehouseRole = Role::create(['name' => 'warehouse']);
        $warehouseUser = User::factory()->create();
        $warehouseUser->roles()->attach($warehouseRole);
        $source = Warehouse::factory()->create();
        $target = Warehouse::factory()->create();
        $warehouseUser->update(['warehouse_id' => $target->id]);
        $customer = Customer::create([
            'name' => 'Khách chờ kho nhận khác ngày',
            'phone' => '0900888777',
            'status' => 'active',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $warehouseUser->id,
            'warehouse_id' => $source->id,
            'code' => 'WAITING-RECEIVE-TOMORROW',
            'status' => Order::STATUS_READY_TO_SHIP,
            'delivery_date' => now()->addDay()->toDateString(),
            'total' => 0,
        ]);
        WarehouseTransfer::create([
            'order_id' => $order->id,
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'shipper_id' => $warehouseUser->id,
            'status' => WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
            'delivered_at' => now(),
            'packed_total_weight' => 50,
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.transfers.incoming', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('WAITING-RECEIVE-TOMORROW')
            ->assertSee('Khách chờ kho nhận khác ngày');
    }

    public function test_shipper_date_uses_dispatch_business_date_and_frozen_item_quantities(): void
    {
        $shipperRole = Role::create(['name' => 'shipper']);
        $shipper = User::factory()->create(['name' => 'Ship xem đúng ngày chuyển']);
        $shipper->roles()->attach($shipperRole);
        $source = Warehouse::factory()->create();
        $target = Warehouse::factory()->create();
        $customer = Customer::create([
            'name' => 'Khách snapshot điều chuyển',
            'status' => 'active',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $shipper->id,
            'warehouse_id' => $source->id,
            'code' => 'TRANSFER-SNAPSHOT-25',
            'status' => Order::STATUS_READY_TO_SHIP,
            'delivery_date' => '2026-08-30',
            'total' => 0,
        ]);
        $transfer = WarehouseTransfer::create([
            'order_id' => $order->id,
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'shipper_id' => $shipper->id,
            'status' => WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
            'packed_total_weight' => 33.6,
        ]);
        $slip = WarehouseDispatchSlip::create([
            'business_date' => '2026-08-25',
            'source_warehouse_id' => $source->id,
            'target_warehouse_id' => $target->id,
            'shipper_id' => $shipper->id,
            'status' => WarehouseDispatchSlip::STATUS_FINALIZED,
            'created_by' => $shipper->id,
            'finalized_by' => $shipper->id,
            'finalized_at' => now(),
        ]);
        WarehouseDispatchSlipEntry::create([
            'warehouse_dispatch_slip_id' => $slip->id,
            'warehouse_transfer_id' => $transfer->id,
            'snapshot' => [
                'type' => 'warehouse_transfer',
                'order' => [
                    'id' => $order->id,
                    'code' => $order->code,
                    'packed_weight' => 33.6,
                    'items' => [[
                        'product_name' => 'Vịt Nguyên Con snapshot',
                        'sku' => 'MOC-SNAPSHOT-28',
                        'size' => '2.8 kg',
                        'quantity' => 12,
                        'weight' => 33.6,
                    ]],
                ],
            ],
        ]);

        $this->actingAs($shipper)
            ->get(route('shipper.warehouse-transfers', ['date' => '2026-08-25']))
            ->assertOk()
            ->assertSee('Danh sách phiếu điều chuyển')
            ->assertSee($slip->code)
            ->assertSee(route('shipper.warehouse-transfers.show', $slip), false)
            ->assertDontSee('TRANSFER-SNAPSHOT-25');

        $this->actingAs($shipper)
            ->get(route('shipper.warehouse-transfers.show', $slip))
            ->assertOk()
            ->assertSee('Chi tiết phiếu điều chuyển')
            ->assertSee($slip->code)
            ->assertSee('TRANSFER-SNAPSHOT-25')
            ->assertSee('Ngày chuyển:')
            ->assertSee('25/08/2026')
            ->assertSee('Tổng số lượng chuyển:')
            ->assertSee('Vịt Nguyên Con snapshot')
            ->assertSee('MOC-SNAPSHOT-28')
            ->assertSee('SL: 12')
            ->assertSee('Cần nhận');

        $this->actingAs($shipper)
            ->get(route('shipper.warehouse-transfers', ['slip_id' => $slip->id]))
            ->assertRedirect(route('shipper.warehouse-transfers.show', $slip));

        $this->actingAs($shipper)
            ->get(route('shipper.warehouse-transfers', ['date' => '2026-08-26']))
            ->assertOk()
            ->assertDontSee('TRANSFER-SNAPSHOT-25');
    }
}
