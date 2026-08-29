<?php

namespace Tests\Feature;

use App\Models\GoogleSheetInventorySync;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseOrdersHistoricalClosingStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_page_shows_historical_closing_stock_even_when_the_day_has_no_orders(): void
    {
        Carbon::setTestNow('2026-08-29 12:00:00');

        $warehouse = Warehouse::query()->create(['name' => 'Kho Long An', 'status' => true]);
        $role = Role::query()->create(['name' => 'warehouse']);
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach($role);
        $product = Product::factory()->create(['name' => 'Vịt nguyên con']);
        $variant = $product->variants()->create([
            'name' => '2.5 kg',
            'sku' => 'MOC-2.5',
            'status' => true,
        ]);
        $inventory = Inventory::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 90,
            'reserved_quantity' => 0,
        ]);

        // This correction was entered on the 29th but belongs to the closing
        // snapshot of the 28th, so it must remain included on the 28th.
        $sync = GoogleSheetInventorySync::query()->create([
            'warehouse_id' => $warehouse->id,
            'spreadsheet_id' => 'historical-stock-test',
            'sheet_id' => 123,
            'inventory_date' => '2026-08-28',
            'sync_number' => 1,
            'created_by' => $user->id,
            'status' => 'completed',
            'snapshot' => [(string) $variant->id => 70],
            'changes' => [],
        ]);
        InventoryMovement::query()->create([
            'inventory_id' => $inventory->id,
            'quantity' => -10,
            'type' => 'google_sheet_adjustment',
            'reference_id' => $sync->id,
            'reference_type' => GoogleSheetInventorySync::class,
            'user_id' => $user->id,
            'created_at' => '2026-08-29 08:00:00',
            'updated_at' => '2026-08-29 08:00:00',
        ]);

        // A genuine next-day receipt must be reversed from the 28th snapshot.
        $nextDayDocument = InventoryDocument::query()->create([
            'type' => 'import',
            'warehouse_id' => $warehouse->id,
            'document_date' => '2026-08-29',
            'user_id' => $user->id,
        ]);
        InventoryMovement::query()->create([
            'inventory_id' => $inventory->id,
            'quantity' => 20,
            'type' => 'import',
            'reference_id' => $nextDayDocument->id,
            'reference_type' => InventoryDocument::class,
            'user_id' => $user->id,
            'created_at' => '2026-08-29 09:00:00',
            'updated_at' => '2026-08-29 09:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('warehouse.orders', ['date' => '2026-08-28']))
            ->assertOk()
            ->assertSee('Tồn cuối đã chốt')
            ->assertSee('2.5 kg')
            ->assertSee('>70<', false)
            ->assertDontSee('Không có dữ liệu tồn cuối cho ngày này.');
    }
}
