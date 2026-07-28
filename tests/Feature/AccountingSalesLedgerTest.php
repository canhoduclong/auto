<?php

namespace Tests\Feature;

use App\Models\AccountingReconciliation;
use App\Models\AccountingSalesEntry;
use App\Models\AccountingSalesImportBatch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingSalesImportService;
use App\Services\AccountingSalesLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountingSalesLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_can_apply_commission_to_multiple_customers_and_recalculate_orders(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin']));
        $sale = User::factory()->create();
        $sale->roles()->attach(Role::create(['name' => 'sale']));
        $firstCustomer = Customer::create([
            'name' => 'Khách hoa hồng 1',
            'assigned_to' => $sale->id,
            'commission_percent' => 0,
            'status' => 'active',
        ]);
        $secondCustomer = Customer::create([
            'name' => 'Khách hoa hồng 2',
            'assigned_to' => $sale->id,
            'commission_percent' => 0,
            'status' => 'active',
        ]);
        $order = Order::create([
            'customer_id' => $firstCustomer->id,
            'user_id' => $sale->id,
            'total' => 1000000,
            'status' => Order::STATUS_COMPLETED,
            'commission_percent_snapshot' => 0,
            'commission_amount_snapshot' => 0,
        ]);
        DB::table('order_commissions')->insert([
            'order_id' => $order->id,
            'sale_user_id' => $sale->id,
            'customer_id' => $firstCustomer->id,
            'order_total' => 1000000,
            'commission_percent' => 0,
            'commission_amount' => 0,
            'status' => 'confirmed',
            'confirmed_by' => $admin->id,
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('accounting.commissions.bulk-update'), [
            'customer_ids' => [$firstCustomer->id, $secondCustomer->id],
            'commission_percent' => 2.5,
            'recalculate_existing' => 1,
            'note' => 'Áp dụng hàng loạt',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', ['id' => $firstCustomer->id, 'commission_percent' => 2.5]);
        $this->assertDatabaseHas('customers', ['id' => $secondCustomer->id, 'commission_percent' => 2.5]);
        $this->assertDatabaseHas('order_commissions', [
            'order_id' => $order->id,
            'commission_percent' => 2.5,
            'commission_amount' => 25000,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'commission_percent_snapshot' => 2.5,
            'commission_amount_snapshot' => 25000,
        ]);
        $this->assertSame(2, DB::table('accounting_customer_commissions')->count());
    }

    public function test_it_imports_vietnamese_accounting_numbers_and_maps_short_sale_name(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin']));
        $sale = User::factory()->create(['name' => 'Nhân viên Duệ', 'short_name' => 'Duệ']);
        $sale->roles()->attach(Role::create(['name' => 'sale']));
        $customer = Customer::create(['name' => 'Cty Phạm Gia Phát', 'customer_code' => '6024', 'status' => 'active']);
        $text = "Ngày tháng\tTháng\tMã KH\tKhách hàng\tNVKD\tDVT\tSL\tKg/con\tTổng\tĐơn giá\tTổng tiền\n"
            ."28/07/2026\t7\t6024\tCty Phạm Gia Phát\tDuệ\tCon\t51,0\t2,35\t120,0\t72.000\t8.640.000\n"
            ."28/07/2026\t7\t6024\tCty Phạm Gia Phát\tDuệ\tvat\t120,0\t1,00\t120,0\t\t-\n"
            ."28/07/2026\t7\t6024\tCty Phạm Gia Phát\tDuệ\tGiảm trừ\t1,0\t1,00\t1,0\t- 75.000\t- 75.000";

        $result = app(AccountingSalesImportService::class)->import($text, $admin);

        $this->assertTrue($result['imported']);
        $this->assertSame(3, AccountingSalesEntry::count());
        $this->assertDatabaseHas('accounting_sales_entries', [
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'unit_price' => 72000,
            'total_amount' => 8640000,
        ]);
        $this->assertDatabaseHas('accounting_sales_entries', [
            'unit' => 'Giảm trừ',
            'unit_price' => -75000,
            'total_amount' => -75000,
        ]);
        $order = Order::where('accounting_sales_import_batch_id', $result['batch_id'])->sole();
        $this->assertSame($sale->id, (int) $order->user_id);
        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
        $this->assertTrue($order->needs_operational_completion);
        $this->assertSame(8565000.0, (float) $order->accountingReconciliation->recognized_revenue);
        $this->assertSame(3, AccountingSalesEntry::where('order_id', $order->id)->count());
        $this->assertSame(3, $order->items()->count());
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'imported_name' => 'Vịt nguyên con',
            'quantity' => 51,
            'unit_weight' => 2.35,
            'total_weight' => 120,
            'total' => 8640000,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'imported_name' => 'VAT',
            'total' => 0,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'imported_name' => 'Giảm trừ',
            'price' => -75000,
            'total' => -75000,
        ]);

        $warehouse = Warehouse::create(['name' => 'Kho lịch sử', 'status' => true]);
        $shipper = User::factory()->create();
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $this->actingAs($admin)->put(route('admin.imported-sales-orders.update', $order), [
            'warehouse_id' => $warehouse->id,
            'shipper_id' => $shipper->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'warehouse_id' => $warehouse->id,
            'shipper_id' => $shipper->id,
            'needs_operational_completion' => 0,
        ]);
    }

    public function test_source_can_be_imported_again_after_all_imported_orders_were_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin']));
        $sale = User::factory()->create(['short_name' => 'Duệ']);
        $sale->roles()->attach(Role::create(['name' => 'sale']));
        Customer::create(['name' => 'Khách nhập lại', 'customer_code' => 'REIMPORT', 'status' => 'active']);
        $text = "Ngày tháng\tTháng\tMã KH\tKhách hàng\tNVKD\tDVT\tSL\tKg/con\tTổng\tĐơn giá\tTổng tiền\n"
            ."28/07/2026\t7\tREIMPORT\tKhách nhập lại\tDuệ\tCon\t10\t2,5\t25\t70.000\t1.750.000";
        $service = app(AccountingSalesImportService::class);

        $first = $service->import($text, $admin);
        Order::where('accounting_sales_import_batch_id', $first['batch_id'])->sole()->delete();
        AccountingSalesImportBatch::findOrFail($first['batch_id'])->update(['row_count' => 0, 'total_amount' => 0]);

        $second = $service->import($text, $admin);

        $this->assertTrue($second['imported']);
        $this->assertNotSame($first['batch_id'], $second['batch_id']);
        $this->assertSame(1, Order::where('accounting_sales_import_batch_id', $second['batch_id'])->count());
    }

    public function test_confirmed_order_sync_is_idempotent_and_balances_to_recognized_revenue(): void
    {
        $sale = User::factory()->create();
        $customer = Customer::create(['name' => 'Khách đồng bộ', 'customer_code' => 'SYNC1', 'status' => 'active']);
        $product = Product::create(['user_id' => $sale->id, 'name' => 'Vịt nguyên con', 'unit' => 'con', 'status' => true]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'total' => 3500000,
            'status' => 'completed',
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 20,
            'unit_weight' => 2.5,
            'total_weight' => 50,
            'price' => 71000,
            'total' => 3550000,
        ]);
        AccountingReconciliation::create([
            'order_id' => $order->id,
            'sale_id' => $sale->id,
            'total_amount' => 3500000,
            'recognized_revenue' => 3500000,
            'status' => AccountingReconciliation::STATUS_CONFIRMED,
            'confirmed_by' => $sale->id,
            'confirmed_at' => now(),
        ]);

        $service = app(AccountingSalesLedgerService::class);
        $service->syncOrder($order);
        $service->syncOrder($order->fresh());

        $this->assertSame(2, AccountingSalesEntry::where('order_id', $order->id)->count());
        $this->assertSame(3500000.0, (float) AccountingSalesEntry::where('order_id', $order->id)->sum('total_amount'));
        $this->assertDatabaseHas('accounting_sales_entries', [
            'order_item_id' => $item->id,
            'total_amount' => 3550000,
        ]);
        $this->assertDatabaseHas('accounting_sales_entries', [
            'unit' => 'Giảm trừ',
            'total_amount' => -50000,
        ]);
    }
}
