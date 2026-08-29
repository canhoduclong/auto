<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\GoogleSheetInventorySync;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderTransfer;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseDispatchSlip;
use App\Models\WarehouseDispatchSlipEntry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDailyRebuildTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_rebuild_a_day_and_is_redirected_to_sheet_resync(): void
    {
        Carbon::setTestNow('2026-08-29 10:00:00');
        $adminRole = Role::query()->create(['name' => 'admin']);
        $saleRole = Role::query()->create(['name' => 'sale']);
        $admin = User::factory()->create();
        $sale = User::factory()->create();
        $shipper = User::factory()->create();
        $admin->roles()->attach($adminRole);
        $sale->roles()->attach($saleRole);
        $warehouse = Warehouse::query()->create(['name' => 'Kho làm lại', 'status' => true]);
        $targetWarehouse = Warehouse::query()->create(['name' => 'Kho nhận', 'status' => true]);
        $customer = Customer::query()->create(['name' => 'Khách làm lại', 'status' => 'active']);
        $variant = ProductVariant::factory()->create(['stock' => 125]);
        $inventory = Inventory::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 125,
            'reserved_quantity' => 0,
        ]);

        $cancelled = $this->order($customer, $sale, $warehouse, $variant, 'REBUILD-CANCEL', Order::STATUS_CANCELLED, 3);
        $delivered = $this->order($customer, $sale, $warehouse, $variant, 'REBUILD-DELIVERED', Order::STATUS_DELIVERED, 5);
        $delivered->update(['shipper_id' => $shipper->id, 'delivered_at' => now(), 'collected_amount' => 500000]);

        $export = InventoryDocument::query()->create([
            'type' => 'export',
            'document_date' => '2026-08-25',
            'warehouse_id' => $warehouse->id,
            'notes' => 'Xuất kho cho đơn #REBUILD-DELIVERED',
            'user_id' => $shipper->id,
        ]);
        $export->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'unit_cost' => 0,
        ]);
        InventoryMovement::query()->create([
            'inventory_id' => $inventory->id,
            'quantity' => -5,
            'type' => 'export',
            'reference_id' => $export->id,
            'reference_type' => InventoryDocument::class,
            'user_id' => $shipper->id,
        ]);

        $sync = GoogleSheetInventorySync::query()->create([
            'warehouse_id' => $warehouse->id,
            'spreadsheet_id' => 'daily-rebuild-test',
            'sheet_id' => 100,
            'inventory_date' => '2026-08-25',
            'sync_number' => 1,
            'created_by' => $admin->id,
            'status' => 'completed',
            'snapshot' => [(string) $variant->id => 30],
            'changes' => [['product_variant_id' => $variant->id, 'delta' => 30]],
            'applied_rows_count' => 1,
        ]);
        $orderTransfer = OrderTransfer::query()->create([
            'shipper_id' => $shipper->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $admin->id,
        ]);
        $delivered->forceFill(['order_transfer_id' => $orderTransfer->id])->save();
        $slip = WarehouseDispatchSlip::query()->create([
            'business_date' => '2026-08-25',
            'source_warehouse_id' => $warehouse->id,
            'target_warehouse_id' => $targetWarehouse->id,
            'shipper_id' => $shipper->id,
            'created_by' => $admin->id,
        ]);
        WarehouseDispatchSlipEntry::query()->create([
            'warehouse_dispatch_slip_id' => $slip->id,
            'order_transfer_id' => $orderTransfer->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.daily-rebuild.execute'), [
                'date' => '2026-08-25',
                'warehouse_id' => $warehouse->id,
                'confirmation_date' => '2026-08-25',
                'reason' => 'Làm lại số liệu kiểm thử',
                'confirm_rebuild' => '1',
            ])
            ->assertRedirect(route('warehouse.google-sheet-inventory.index', [
                'date' => '2026-08-25',
                'warehouse_id' => $warehouse->id,
            ]))
            ->assertSessionHas('warning');

        $this->assertSame(Order::STATUS_READY_TO_PACK, $cancelled->fresh()->status);
        $this->assertSame(Order::STATUS_READY_TO_PACK, $delivered->fresh()->status);
        $this->assertNull($delivered->fresh()->shipper_id);
        $this->assertNull($delivered->fresh()->delivered_at);
        $this->assertSame(100.0, (float) $inventory->fresh()->quantity);
        $this->assertSame(8, (int) $inventory->fresh()->reserved_quantity);
        $this->assertSame(8, (int) InventoryReservation::query()->sum('quantity'));
        $this->assertSame('reset', $sync->fresh()->status);
        $this->assertDatabaseMissing('inventory_documents', ['id' => $export->id]);
        $this->assertDatabaseMissing('order_transfers', ['id' => $orderTransfer->id]);
        $this->assertDatabaseMissing('warehouse_dispatch_slips', ['id' => $slip->id]);
        $this->assertDatabaseHas('admin_daily_rebuilds', [
            'business_date' => '2026-08-25',
            'warehouse_id' => $warehouse->id,
            'orders_restored_count' => 2,
            'inventory_syncs_reset_count' => 1,
        ]);
    }

    private function order(
        Customer $customer,
        User $sale,
        Warehouse $warehouse,
        ProductVariant $variant,
        string $code,
        string $status,
        int $quantity
    ): Order {
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'warehouse_id' => $warehouse->id,
            'code' => $code,
            'status' => $status,
            'total' => 100000 * $quantity,
        ]);
        $order->forceFill(['created_at' => '2026-08-25 08:00:00'])->saveQuietly();
        $order->items()->create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'price' => 100000,
            'total' => 100000 * $quantity,
            'is_priced_by_kg' => false,
        ]);

        return $order->fresh('items');
    }
}
