<?php

namespace Tests\Feature;

use App\Models\AccountingReconciliation;
use App\Models\AccountingSalesEntry;
use App\Models\AccountingSalesImportBatch;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminOrderDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_order_and_remove_sale_revenue_commission_and_inventory_effects(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin']));
        $sale = User::factory()->create();
        $sale->roles()->attach(Role::create(['name' => 'sale']));
        $customer = Customer::create(['name' => 'Khách cần xóa đơn', 'status' => 'active']);
        $warehouse = Warehouse::create(['name' => 'Kho kiểm thử', 'status' => true]);
        $product = Product::create(['user_id' => $sale->id, 'name' => 'Vịt nguyên con', 'unit' => 'con', 'status' => true]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'name' => '2.5 kg', 'sku' => 'TEST-2.5', 'kg' => 2.5]);
        $inventory = Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 90,
            'reserved_quantity' => 10,
        ]);
        $batch = AccountingSalesImportBatch::create([
            'imported_by' => $admin->id,
            'source_hash' => hash('sha256', 'delete-order-test'),
            'row_count' => 1,
            'total_amount' => 1000000,
            'raw_text' => 'test',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'HIS-TEST-DELETE',
            'total' => 1000000,
            'status' => Order::STATUS_COMPLETED,
            'accounting_sales_import_batch_id' => $batch->id,
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'unit_weight' => 2.5,
            'total_weight' => 25,
            'price' => 40000,
            'total' => 1000000,
        ]);
        InventoryReservation::create(['order_item_id' => $item->id, 'inventory_id' => $inventory->id, 'quantity' => 10]);
        InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'quantity' => -10,
            'type' => 'export',
            'reference_id' => $order->id,
            'reference_type' => Order::class,
            'user_id' => $admin->id,
        ]);
        $reconciliation = AccountingReconciliation::create([
            'order_id' => $order->id,
            'sale_id' => $sale->id,
            'total_amount' => 1000000,
            'recognized_revenue' => 1000000,
            'status' => AccountingReconciliation::STATUS_CONFIRMED,
            'confirmed_by' => $admin->id,
            'confirmed_at' => now(),
        ]);
        AccountingSalesEntry::create([
            'entry_date' => now()->toDateString(),
            'entry_month' => now()->month,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'sale_id' => $sale->id,
            'sale_name' => $sale->name,
            'unit' => 'Con',
            'quantity' => 10,
            'unit_weight' => 2.5,
            'total_quantity' => 25,
            'unit_price' => 40000,
            'total_amount' => 1000000,
            'source' => AccountingSalesEntry::SOURCE_IMPORT,
            'source_key' => 'delete-test-entry',
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'accounting_reconciliation_id' => $reconciliation->id,
            'import_batch_id' => $batch->id,
        ]);
        DB::table('order_commissions')->insert([
            'order_id' => $order->id,
            'sale_user_id' => $sale->id,
            'customer_id' => $customer->id,
            'order_total' => 1000000,
            'commission_percent' => 2,
            'commission_amount' => 20000,
            'status' => 'confirmed',
            'confirmed_by' => $admin->id,
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->delete(route('site.orders.admin-delete', $order), [
            'reason' => 'Đơn được nhập nhầm',
        ])->assertRedirect();

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('accounting_sales_entries', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('order_commissions', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('accounting_reconciliations', ['order_id' => $order->id]);
        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'quantity' => 100, 'reserved_quantity' => 0]);
        $this->assertDatabaseMissing('inventory_movements', ['reference_type' => Order::class, 'reference_id' => $order->id]);
        $this->assertDatabaseHas('accounting_sales_import_batches', ['id' => $batch->id, 'row_count' => 0, 'total_amount' => 0]);
        $this->assertDatabaseHas('admin_deleted_orders', [
            'order_id' => $order->id,
            'sale_user_id' => $sale->id,
            'recognized_revenue' => 1000000,
            'commission_amount' => 20000,
            'reason' => 'Đơn được nhập nhầm',
            'deleted_by' => $admin->id,
        ]);
    }
}
