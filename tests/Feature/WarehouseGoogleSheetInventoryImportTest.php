<?php

namespace Tests\Feature;

use App\Models\GoogleSheetInventorySync;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GoogleSheetsInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class WarehouseGoogleSheetInventoryImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_later_sync_only_applies_selected_deltas_and_keeps_unselected_changes_pending(): void
    {
        $warehouse = Warehouse::query()->create(['name' => 'Kho Long An', 'status' => true]);
        $role = Role::query()->create(['name' => 'warehouse']);
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach($role);
        $product = Product::factory()->create(['name' => 'Vịt Nguyên Con']);
        $variantOne = $this->variant($product, '2.0 kg', 'MOC - 2.0');
        $variantTwo = $this->variant($product, '2.1 kg', 'MOC - 2.1');

        $firstPreview = $this->preview([
            $this->row($variantOne, 9, 'M 2', 12),
            $this->row($variantTwo, 10, 'M 2,1', 0),
        ]);
        $changedPreview = $this->preview([
            $this->row($variantOne, 9, 'M 2', 8),
            $this->row($variantTwo, 10, 'M 2,1', 5),
        ]);
        $this->mock(GoogleSheetsInventoryService::class, function (MockInterface $mock) use ($warehouse, $firstPreview, $changedPreview): void {
            $mock->shouldReceive('preview')
                ->times(3)
                ->withArgs(fn ($actualWarehouse, $date) => $actualWarehouse->is($warehouse) && $date === '2026-08-26')
                ->andReturn($firstPreview, $changedPreview, $changedPreview);
        });

        $basePayload = ['date' => '2026-08-26', 'confirm_import' => '1'];
        $this->actingAs($user)->post(route('warehouse.google-sheet-inventory.store'), $basePayload + [
            'selected_variant_ids' => [$variantOne->id],
        ])->assertRedirect(route('warehouse.google-sheet-inventory.index', [
            'date' => '2026-08-26',
            'warehouse_id' => $warehouse->id,
        ]));

        $this->assertSame(12.0, $this->inventoryQuantity($warehouse, $variantOne));
        $this->assertDatabaseHas('google_sheet_inventory_syncs', [
            'warehouse_id' => $warehouse->id,
            'sync_number' => 1,
            'total_positive_delta' => 12,
            'total_negative_delta' => 0,
        ]);

        // Chỉ chọn sản phẩm mới. Dòng giảm của sản phẩm cũ phải được giữ lại cho lần sau.
        $this->actingAs($user)->post(route('warehouse.google-sheet-inventory.store'), $basePayload + [
            'selected_variant_ids' => [$variantTwo->id],
        ])->assertSessionHas('success');

        $this->assertSame(12.0, $this->inventoryQuantity($warehouse, $variantOne));
        $this->assertSame(5.0, $this->inventoryQuantity($warehouse, $variantTwo));
        $secondSync = GoogleSheetInventorySync::query()->where('sync_number', 2)->firstOrFail();
        $this->assertSame(12.0, (float) $secondSync->snapshot[(string) $variantOne->id]);
        $this->assertSame(5.0, (float) $secondSync->snapshot[(string) $variantTwo->id]);

        // Sheet vẫn là 8: lần thứ ba hệ thống còn nhận ra chênh lệch -4 và chỉ điều chỉnh phần đó.
        $this->actingAs($user)->post(route('warehouse.google-sheet-inventory.store'), $basePayload + [
            'selected_variant_ids' => [$variantOne->id],
        ])->assertSessionHas('success');

        $this->assertSame(8.0, $this->inventoryQuantity($warehouse, $variantOne));
        $this->assertSame(2, InventoryDocument::query()->count());
        $this->assertSame(3, GoogleSheetInventorySync::query()->count());
        $this->assertDatabaseHas('inventory_adjustments', [
            'inventory_id' => Inventory::query()->where('warehouse_id', $warehouse->id)->where('product_variant_id', $variantOne->id)->value('id'),
            'quantity' => -4,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'type' => 'google_sheet_adjustment',
            'quantity' => -4,
            'reference_type' => GoogleSheetInventorySync::class,
        ]);
    }

    public function test_warehouse_can_save_inventory_spreadsheet_link_and_sheet_id(): void
    {
        $warehouse = Warehouse::query()->create(['name' => 'Kho cấu hình Sheet', 'status' => true]);
        $role = Role::query()->create(['name' => 'warehouse']);
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->post(route('warehouse.google-sheet-inventory.configuration'), [
                'warehouse_id' => $warehouse->id,
                'date' => '2026-08-30',
                'spreadsheet_source' => 'https://docs.google.com/spreadsheets/d/inventorySheet_123456789/edit?gid=987654321',
                'sheet_id' => 987654321,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('warehouse.google-sheet-inventory.index', [
                'date' => '2026-08-30',
                'warehouse_id' => $warehouse->id,
            ]));

        $prefix = 'warehouse.google_sheet_inventory.'.$warehouse->id.'.';
        $this->assertSame('inventorySheet_123456789', Setting::get($prefix.'spreadsheet_id'));
        $this->assertSame('987654321', Setting::get($prefix.'sheet_id'));
    }

    public function test_warehouse_can_write_daily_inventory_to_saved_google_sheet(): void
    {
        $warehouse = Warehouse::query()->create(['name' => 'Kho ghi Sheet', 'status' => true]);
        $role = Role::query()->create(['name' => 'warehouse']);
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach($role);
        $this->mock(GoogleSheetsInventoryService::class, function (MockInterface $mock) use ($warehouse): void {
            $mock->shouldReceive('writeDailyInventory')
                ->once()
                ->withArgs(fn ($actualWarehouse, $date) => $actualWarehouse->is($warehouse) && $date === '2026-08-30')
                ->andReturn([
                    'rows' => 8,
                    'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/test/edit',
                    'sheet_name' => 'TK-NM 08.2026',
                    'stock_column' => 12,
                ]);
        });

        $this->actingAs($user)
            ->post(route('warehouse.google-sheet-inventory.write-daily'), [
                'warehouse_id' => $warehouse->id,
                'date' => '2026-08-30',
                'confirm_write' => '1',
            ])
            ->assertRedirect(route('warehouse.google-sheet-inventory.index', [
                'date' => '2026-08-30',
                'warehouse_id' => $warehouse->id,
            ]))
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'Đã ghi 8 dòng tồn kho'));
    }

    private function variant(Product $product, string $name, string $sku): ProductVariant
    {
        return ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => $name,
            'sku' => $sku,
            'size' => str($name)->before(' ')->toString(),
            'stock' => 0,
        ]);
    }

    /** @return array<string, mixed> */
    private function row(ProductVariant $variant, int $sheetRow, string $sheetCode, float $quantity): array
    {
        return [
            'sheet_row' => $sheetRow,
            'sheet_code' => $sheetCode,
            'normalized_code' => str($sheetCode)->lower()->replace(',', '.')->toString(),
            'quantity' => $quantity,
            'matched' => true,
            'variant_id' => $variant->id,
            'variant_name' => $variant->name,
            'variant_sku' => $variant->sku,
            'match_method' => 'inventory_name',
            'match_error' => null,
        ];
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function preview(array $rows): array
    {
        $rows = collect($rows);

        return [
            'spreadsheet_id' => 'sheet-test',
            'sheet_id' => 943551638,
            'sheet_name' => 'TK-NM 08.2026',
            'selected_date' => '2026-08-26',
            'rows' => $rows,
            'import_rows' => $rows->where('quantity', '>', 0)->values(),
            'unmatched_positive_rows' => collect(),
            'has_blocking_errors' => false,
            'total_quantity' => (float) $rows->sum('quantity'),
        ];
    }

    private function inventoryQuantity(Warehouse $warehouse, ProductVariant $variant): float
    {
        return (float) Inventory::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $variant->id)
            ->value('quantity');
    }
}
