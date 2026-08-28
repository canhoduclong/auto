<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GoogleSheetsInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class WarehouseGoogleSheetInventoryImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_import_requires_explicit_confirmation_and_records_import_number(): void
    {
        $warehouse = Warehouse::query()->create(['name' => 'Kho Long An', 'status' => true]);
        $role = Role::query()->create(['name' => 'warehouse']);
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach($role);
        $product = Product::factory()->create(['name' => 'Vịt Nguyên Con']);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => '2.0 kg',
            'sku' => 'MOC - 2.00',
            'size' => '2.00',
            'stock' => 0,
        ]);

        $preview = [
            'spreadsheet_id' => 'sheet-test',
            'sheet_id' => 943551638,
            'sheet_name' => 'TK-NM 08.2026',
            'import_rows' => collect([[
                'sheet_row' => 9,
                'sheet_code' => 'M 2',
                'quantity' => 12.0,
                'variant_id' => $variant->id,
            ]]),
            'unmatched_positive_rows' => collect(),
            'has_blocking_errors' => false,
            'total_quantity' => 12.0,
        ];
        $this->mock(GoogleSheetsInventoryService::class, function (MockInterface $mock) use ($warehouse, $preview): void {
            $mock->shouldReceive('preview')->times(3)->withArgs(fn ($actualWarehouse, $date) => $actualWarehouse->is($warehouse) && $date === '2026-08-26')->andReturn($preview);
        });

        $payload = ['date' => '2026-08-26', 'confirm_import' => '1'];
        $firstResponse = $this->actingAs($user)->post(route('warehouse.google-sheet-inventory.store'), $payload);

        $document = InventoryDocument::query()->firstOrFail();
        $firstResponse->assertRedirect(route('warehouse.stock-in.show', $document));
        $this->assertSame(12.0, (float) Inventory::query()->where('warehouse_id', $warehouse->id)->where('product_variant_id', $variant->id)->value('quantity'));
        $this->assertDatabaseHas('inventory_document_items', [
            'inventory_document_id' => $document->id,
            'product_variant_id' => $variant->id,
            'quantity' => 12,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'reference_id' => $document->id,
            'reference_type' => InventoryDocument::class,
            'quantity' => 12,
            'type' => 'import',
        ]);

        $this->actingAs($user)->post(route('warehouse.google-sheet-inventory.store'), $payload)
            ->assertRedirect(route('warehouse.google-sheet-inventory.index', ['date' => '2026-08-26', 'warehouse_id' => $warehouse->id]))
            ->assertSessionHas('warning');
        $this->assertSame(1, InventoryDocument::query()->count());
        $this->assertSame(12.0, (float) Inventory::query()->firstOrFail()->quantity);

        $this->actingAs($user)->post(route('warehouse.google-sheet-inventory.store'), $payload + ['allow_duplicate' => '1'])
            ->assertRedirect();
        $this->assertSame(2, InventoryDocument::query()->count());
        $this->assertSame(24.0, (float) Inventory::query()->firstOrFail()->quantity);
        $this->assertStringContainsString('lần nhập 2', (string) InventoryDocument::query()->latest('id')->value('notes'));
    }
}
