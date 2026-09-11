<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\RoleScreenApiController;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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

    public function test_mobile_incoming_transfers_include_all_delivery_dates_and_prioritize_pending_receipts(): void
    {
        $warehouseRole = Role::create(['name' => 'warehouse']);
        $sourceWarehouse = Warehouse::factory()->create();
        $targetWarehouse = Warehouse::factory()->create();
        $warehouseUser = User::factory()->create(['warehouse_id' => $targetWarehouse->id]);
        $warehouseUser->roles()->attach($warehouseRole);
        $customer = Customer::create(['name' => 'Khách nhận điều chuyển', 'status' => 'active']);

        $completedOrder = Order::create([
            'customer_id' => $customer->id,
            'warehouse_id' => $sourceWarehouse->id,
            'code' => 'TRANSFER-COMPLETED',
            'status' => Order::STATUS_READY_TO_SHIP,
            'delivery_date' => now()->subDays(2)->toDateString(),
        ]);
        $pendingOrder = Order::create([
            'customer_id' => $customer->id,
            'warehouse_id' => $sourceWarehouse->id,
            'code' => 'TRANSFER-PENDING',
            'status' => Order::STATUS_READY_TO_SHIP,
            'delivery_date' => now()->subDay()->toDateString(),
        ]);
        WarehouseTransfer::create([
            'order_id' => $completedOrder->id,
            'source_warehouse_id' => $sourceWarehouse->id,
            'target_warehouse_id' => $targetWarehouse->id,
            'shipper_id' => $warehouseUser->id,
            'status' => WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
        ]);
        WarehouseTransfer::create([
            'order_id' => $pendingOrder->id,
            'source_warehouse_id' => $sourceWarehouse->id,
            'target_warehouse_id' => $targetWarehouse->id,
            'shipper_id' => $warehouseUser->id,
            'status' => WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
        ]);

        $request = Request::create('/api/mobile/screens/warehouse/incoming_transfers', 'GET', [
            'date' => now()->toDateString(),
        ]);
        $request->setUserResolver(fn () => $warehouseUser->load('roles'));

        $payload = app(RoleScreenApiController::class)
            ->show($request, 'warehouse', 'incoming_transfers')
            ->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertCount(2, $payload['data']['items']);
        $this->assertSame('TRANSFER-PENDING', $payload['data']['items'][0]['order_code']);
        $this->assertSame('TRANSFER-COMPLETED', $payload['data']['items'][1]['order_code']);
    }
}
