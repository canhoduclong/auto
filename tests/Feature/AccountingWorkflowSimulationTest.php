<?php

namespace Tests\Feature;

use App\Models\AccountingReconciliation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\AccountingWorkflowSimulationService;
use App\Services\AccountingSalesImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingWorkflowSimulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_order_can_be_adjusted_to_available_inventory_and_continue_packing(): void
    {
        $accounting = User::factory()->create();
        $accounting->roles()->attach(Role::create(['name' => 'accounting']));
        $sale = User::factory()->create();
        $sale->roles()->attach(Role::create(['name' => 'sale']));
        $warehouse = Warehouse::create(['name' => 'Kho kiểm tra tồn', 'status' => true]);
        $customer = Customer::create(['name' => 'Khách thiếu hàng', 'status' => 'active']);
        $product = Product::create(['user_id' => $sale->id, 'name' => 'Vịt size kiểm tra', 'unit' => 'con', 'status' => true]);
        $variant = $product->variants()->create(['name' => '2.5 kg', 'size' => 2.5, 'kg' => 2.5, 'stock' => 5]);
        $service = app(AccountingWorkflowSimulationService::class);
        $service->stockIn($variant->id, $warehouse->id, 5, 50000, now()->toDateString(), $accounting);
        $order = $service->createOrder([
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 8,
            'price' => 80000,
        ], $accounting);

        $before = $service->inventoryStatus($order);
        $this->assertFalse($before['sufficient']);
        $this->assertSame(3.0, $before['items'][0]['shortage']);

        $stocktake = $service->stocktakeForWorkflow($warehouse->id, [[
            'product_variant_id' => $variant->id,
            'expected_quantity' => 5,
            'counted_quantity' => 6,
        ]], now()->toDateString(), $accounting);
        $this->assertDatabaseHas('inventory_stocktakes', ['id' => $stocktake->id, 'status' => 'completed']);
        $this->assertDatabaseHas('inventory_stocktake_items', ['stocktake_id' => $stocktake->id, 'system_quantity' => 5, 'counted_quantity' => 6, 'difference' => 1]);
        $this->assertSame(2.0, $service->inventoryStatus($order->fresh())['items'][0]['shortage']);

        $item = $order->items()->sole();
        $after = $service->adjustOrderToInventory($order, [[
            'item_id' => $item->id,
            'product_variant_id' => $variant->id,
            'quantity' => 6,
        ]], $accounting);

        $this->assertTrue($after['sufficient']);
        $this->assertDatabaseHas('order_items', ['id' => $item->id, 'quantity' => 6]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'stock_sufficient' => 1]);
        $service->advanceOrders([$order->id], 'leader_approve', $accounting);
        $service->advanceOrders([$order->id], 'manager_approve', $accounting);
        $this->assertSame(1, $service->advanceOrders([$order->id], 'warehouse_confirm', $accounting));
        $this->assertSame(Order::STATUS_PACKING, $order->fresh()->status);

        $this->actingAs($accounting)
            ->get(route('accounting.workflow-simulation.index', ['date' => now()->toDateString(), 'step' => 2]))
            ->assertOk()
            ->assertSee('Kiểm kê kho để hoàn thiện đơn')
            ->assertSee('Hoàn tất kiểm kê')
            ->assertSee('Tồn kho hiện tại')
            ->assertSee('Đơn thiếu hàng cần chỉnh');
    }

    public function test_bulk_simulation_moves_stock_delivers_and_confirms_revenue(): void
    {
        $accounting = User::factory()->create();
        $accounting->roles()->attach(Role::create(['name' => 'accounting']));
        $sale = User::factory()->create();
        $sale->roles()->attach(Role::create(['name' => 'sale']));
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $source = Warehouse::create(['name' => 'Kho Long An', 'status' => true]);
        $target = Warehouse::create(['name' => 'Kho Chiến Lược', 'status' => true]);
        $customer = Customer::create(['name' => 'Khách mô phỏng', 'status' => 'active', 'commission_percent' => 2]);
        $product = Product::create(['user_id' => $sale->id, 'name' => 'Vịt nguyên con', 'unit' => 'con', 'status' => true]);
        $variant = $product->variants()->create(['name' => '2.5 kg', 'size' => 2.5, 'kg' => 2.5, 'stock' => 10]);
        $service = app(AccountingWorkflowSimulationService::class);
        $service->stockInMany([[
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'unit_cost' => 50000,
        ]], $source->id, now()->toDateString(), $accounting);
        $order = $service->createOrder([
            'date' => now()->toDateString(), 'customer_id' => $customer->id,
            'sale_id' => $sale->id, 'warehouse_id' => $source->id,
            'product_variant_id' => $variant->id, 'quantity' => 5,
            'price' => 80000,
        ], $accounting);
        $this->assertSame(1, $service->advanceOrders([$order->id], 'leader_approve', $accounting));
        $this->assertSame(1, $service->advanceOrders([$order->id], 'manager_approve', $accounting));
        $this->assertSame(1, $service->advanceOrders([$order->id], 'warehouse_confirm', $accounting));
        $this->assertSame(1, $service->advanceOrders([$order->id], 'complete_packing', $accounting));
        $this->assertSame(1, $service->createTransfers([$order->id], $target->id, $shipper->id, $accounting));
        $transfer = WarehouseTransfer::where('order_id', $order->id)->sole();
        $this->assertSame(1, $service->pickupTransfers(collect([$transfer]), $accounting));
        $this->assertSame(1, $service->deliverTransfers(collect([$transfer->fresh()]), $accounting));
        $this->assertSame(1, $service->receiveTransfers(collect([$transfer->fresh()]), $accounting));
        $this->assertSame(1, $service->assignOrders([$order->id], $shipper->id, $accounting));
        $this->assertSame(1, $service->deliverOrders([$order->id], 'paid', $accounting));
        $this->assertSame(1, $service->confirmOrders([$order->id], $accounting));

        $this->assertDatabaseHas('warehouse_transfers', ['id' => $transfer->id, 'status' => WarehouseTransfer::STATUS_RECEIVED_COMPLETED]);
        $this->assertDatabaseHas('inventories', ['product_variant_id' => $variant->id, 'warehouse_id' => $source->id, 'quantity' => 5]);
        $this->assertDatabaseHas('inventories', ['product_variant_id' => $variant->id, 'warehouse_id' => $target->id, 'quantity' => 5]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_COMPLETED, 'payment_status' => 'paid']);
        $this->assertDatabaseHas('accounting_reconciliations', ['order_id' => $order->id, 'status' => AccountingReconciliation::STATUS_CONFIRMED, 'recognized_revenue' => 1000000]);
        $this->assertDatabaseHas('order_commissions', ['order_id' => $order->id, 'commission_amount' => 20000, 'status' => 'confirmed']);

        $bulkText = implode("\t", ['Ngày tháng', 'Tháng', 'Mã KH', 'Khách hàng', 'NVKD', 'DVT', 'SL', 'Kg/con', 'Tổng', 'Đơn giá', 'Tổng tiền'])."\n"
            .implode("\t", [now()->format('d/m/Y'), now()->month, '9001', $customer->name, $sale->name, 'Con', '2,0', '2,50', '5,0', '80.000', '400.000'])."\n"
            .implode("\t", [now()->format('d/m/Y'), now()->month, '9001', $customer->name, $sale->name, 'shiper', '1,0', '1,00', '1,0', '30.000', '30.000']);
        $bulkResult = app(AccountingSalesImportService::class)->importPendingOrders(
            $bulkText,
            $accounting,
            [],
            now()->toDateString(),
            $source->id
        );
        $this->assertTrue($bulkResult['imported']);
        $this->assertSame(1, $bulkResult['orders_created']);
        $bulkOrder = Order::query()->where('accounting_sales_import_batch_id', $bulkResult['batch_id'])->sole();
        $this->assertSame(Order::STATUS_PENDING_LEADER_APPROVAL, $bulkOrder->status);
        $this->assertNotNull($bulkOrder->daily_sequence);
        $this->assertSame(430000.0, (float) $bulkOrder->total);
        $this->assertSame(30000.0, (float) $bulkOrder->shipping_fee);
        $this->assertSame(5.0, (float) $bulkOrder->total_weight);
        $this->assertCount(2, $bulkOrder->items);

        $this->actingAs($accounting)
            ->get(route('accounting.workflow-simulation.index', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Nhập kho nhiều sản phẩm')
            ->assertSee('Chọn nhiều sản phẩm')
            ->assertSee('Lên từng đơn')
            ->assertSee('Lên đơn hàng loạt từ Excel / Google Sheets')
            ->assertSee('Trưởng phòng duyệt')
            ->assertSee('Kế toán xác nhận');
    }
}
