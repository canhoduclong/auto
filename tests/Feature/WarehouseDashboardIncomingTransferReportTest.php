<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseDashboardIncomingTransferReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_target_warehouse_with_pending_request_sees_incoming_order_report(): void
    {
        $warehouseRole = Role::create(['name' => 'warehouse']);
        $sourceWarehouse = Warehouse::factory()->create();
        $targetWarehouse = Warehouse::factory()->create();
        $sourceUser = User::factory()->create(['warehouse_id' => $sourceWarehouse->id]);
        $targetUser = User::factory()->create(['warehouse_id' => $targetWarehouse->id]);
        $sourceUser->roles()->attach($warehouseRole);
        $targetUser->roles()->attach($warehouseRole);
        $customer = Customer::create(['name' => 'Khách điều chuyển kho', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sourceUser->id,
            'warehouse_id' => $sourceWarehouse->id,
            'code' => 'ORD-INCOMING-DASHBOARD',
            'status' => Order::STATUS_READY_TO_SHIP,
            'total' => 0,
        ]);
        $transfer = WarehouseTransfer::create([
            'order_id' => $order->id,
            'source_warehouse_id' => $sourceWarehouse->id,
            'target_warehouse_id' => $targetWarehouse->id,
            'shipper_id' => $sourceUser->id,
            'status' => WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
        ]);

        $sourceResponse = $this->actingAs($sourceUser)->get(route('warehouse.dashboard'));
        $sourceResponse->assertOk()->assertDontSee('data-task-key="incoming-orders"', false);
        $this->assertSame(0, $sourceResponse->viewData('stats')['transfers_incoming']);

        $targetResponse = $this->actingAs($targetUser)->get(route('warehouse.dashboard'));
        $targetResponse->assertOk()->assertSee('data-task-key="incoming-orders"', false);
        $this->assertSame(1, $targetResponse->viewData('stats')['transfers_incoming']);

        $transfer->update([
            'status' => WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
            'received_by' => $targetUser->id,
            'received_at' => now(),
        ]);

        $completedResponse = $this->actingAs($targetUser)->get(route('warehouse.dashboard'));
        $completedResponse->assertOk()->assertDontSee('data-task-key="incoming-orders"', false);
        $this->assertSame(0, $completedResponse->viewData('stats')['transfers_incoming']);
    }
}
