<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\GoogleSheetInventorySync;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseGoogleSheetInventoryResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reset_completed_sheet_syncs_in_a_date_range(): void
    {
        $warehouse = Warehouse::query()->create(['name' => 'Kho reset', 'status' => true]);
        $adminRole = Role::query()->create(['name' => 'admin']);
        $admin = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $admin->roles()->attach($adminRole);
        $product = Product::factory()->create();
        $variant = $product->variants()->create(['name' => 'M 2.5', 'sku' => 'RESET-2.5']);
        $inventory = Inventory::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 19,
            'reserved_quantity' => 0,
        ]);

        $sync27 = $this->sync($warehouse, $admin, $variant->id, '2026-08-27', 1, 10);
        $sync28 = $this->sync($warehouse, $admin, $variant->id, '2026-08-28', 1, 5);
        $sync29 = $this->sync($warehouse, $admin, $variant->id, '2026-08-29', 1, 4);

        $this->actingAs($admin)
            ->delete(route('admin.google-sheet-inventory-reset.destroy'), [
                'warehouse_id' => $warehouse->id,
                'from_date' => '2026-08-27',
                'to_date' => '2026-08-28',
                'reset_reason' => 'Nhập lại dữ liệu kiểm thử',
                'confirm_reset' => '1',
            ])
            ->assertRedirect(route('admin.google-sheet-inventory-reset.index', [
                'from_date' => '2026-08-27',
                'to_date' => '2026-08-28',
                'warehouse_id' => $warehouse->id,
            ]))
            ->assertSessionHas('success');

        $this->assertSame(4.0, (float) $inventory->fresh()->quantity);
        $this->assertSame(4.0, (float) $variant->fresh()->stock);
        $this->assertSame('reset', $sync27->fresh()->status);
        $this->assertSame('reset', $sync28->fresh()->status);
        $this->assertSame('completed', $sync29->fresh()->status);
        $this->assertSame($admin->id, $sync27->fresh()->reset_by);
        $this->assertSame('Nhập lại dữ liệu kiểm thử', $sync27->fresh()->reset_reason);
        $this->assertSame(-15.0, (float) InventoryMovement::query()
            ->where('inventory_id', $inventory->id)
            ->where('type', 'google_sheet_reset')
            ->sum('quantity'));
    }

    public function test_warehouse_user_cannot_reset_sheet_syncs(): void
    {
        $warehouse = Warehouse::query()->create(['name' => 'Kho thường', 'status' => true]);
        $role = Role::query()->create(['name' => 'warehouse']);
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->delete(route('admin.google-sheet-inventory-reset.destroy'), [
                'from_date' => '2026-08-27',
                'to_date' => '2026-08-28',
                'confirm_reset' => '1',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('error', 'Bạn không có quyền truy cập khu vực này.');
    }

    public function test_reset_range_is_available_on_admin_layout_page(): void
    {
        $warehouse = Warehouse::query()->create(['name' => 'Kho Admin', 'status' => true]);
        $adminRole = Role::query()->create(['name' => 'admin']);
        $admin = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $admin->roles()->attach($adminRole);

        $this->actingAs($admin)
            ->get(route('admin.google-sheet-inventory-reset.index', [
                'warehouse_id' => $warehouse->id,
                'from_date' => '2026-08-27',
                'to_date' => '2026-08-28',
            ]))
            ->assertOk()
            ->assertSee('Reset dữ liệu tồn kho Google Sheet')
            ->assertSee('Chức năng quản trị dành riêng cho Admin')
            ->assertSee(route('admin.google-sheet-inventory-reset.destroy'), false);
    }

    public function test_admin_can_clear_one_day_before_importing_stock_and_import_columns_again(): void
    {
        $warehouse = Warehouse::query()->create(['name' => 'Kho Clear ngày', 'status' => true]);
        $adminRole = Role::query()->create(['name' => 'admin']);
        $admin = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $admin->roles()->attach($adminRole);
        $product = Product::factory()->create();
        $variant = $product->variants()->create(['name' => 'M 2.5', 'sku' => 'CLEAR-2.5']);
        $inventory = Inventory::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'reserved_quantity' => 5,
        ]);
        $sync = $this->sync($warehouse, $admin, $variant->id, '2026-08-25', 1, 6);
        $customer = Customer::query()->create([
            'user_id' => $admin->id,
            'name' => 'Khách chờ kho đóng lại',
            'status' => 'active',
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'CLEAR-DAY-RESERVATION',
            'status' => Order::STATUS_APPROVED,
            'total' => 0,
        ]);
        $order->forceFill(['created_at' => '2026-08-25 08:00:00'])->saveQuietly();
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'price' => 0,
            'total' => 0,
            'is_priced_by_kg' => false,
        ]);
        $reservation = InventoryReservation::query()->create([
            'order_item_id' => $item->id,
            'inventory_id' => $inventory->id,
            'quantity' => 5,
            'reserved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('warehouse.google-sheet-inventory.clear-day'), [
                'warehouse_id' => $warehouse->id,
                'date' => '2026-08-25',
                'confirmation_date' => '2026-08-25',
                'clear_reason' => 'Đọc lại hai cột',
                'confirm_clear' => '1',
            ])
            ->assertRedirect(route('warehouse.google-sheet-inventory.index', [
                'date' => '2026-08-25',
                'warehouse_id' => $warehouse->id,
            ]))
            ->assertSessionHas('success');

        $this->assertSame(4.0, (float) $inventory->fresh()->quantity);
        $this->assertSame(0.0, (float) $inventory->fresh()->reserved_quantity);
        $this->assertDatabaseMissing('inventory_reservations', ['id' => $reservation->id]);
        $this->assertSame(Order::STATUS_APPROVED, $order->fresh()->status);
        $this->assertSame('reset', $sync->fresh()->status);
        $this->assertStringContainsString('Clear ngày', (string) $sync->fresh()->reset_reason);
    }

    private function sync(
        Warehouse $warehouse,
        User $user,
        int $variantId,
        string $date,
        int $number,
        float $delta
    ): GoogleSheetInventorySync {
        return GoogleSheetInventorySync::query()->create([
            'warehouse_id' => $warehouse->id,
            'spreadsheet_id' => 'reset-range-test',
            'sheet_id' => 456,
            'inventory_date' => $date,
            'sync_number' => $number,
            'created_by' => $user->id,
            'status' => 'completed',
            'total_positive_delta' => max(0, $delta),
            'total_negative_delta' => abs(min(0, $delta)),
            'applied_rows_count' => 1,
            'snapshot' => [(string) $variantId => $delta],
            'changes' => [[
                'product_variant_id' => $variantId,
                'delta' => $delta,
            ]],
        ]);
    }
}
