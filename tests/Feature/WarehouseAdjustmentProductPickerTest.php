<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseAdjustmentProductPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_grouped_picker_requires_product_selection_and_only_returns_in_stock_variants(): void
    {
        $warehouse = Warehouse::factory()->create();
        $warehouseRole = Role::query()->create(['name' => 'warehouse']);
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach($warehouseRole);
        $product = Product::factory()->create([
            'name' => 'Vịt nguyên con',
            'status' => true,
        ]);
        $availableVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Loại 2.5 kg',
            'sku' => 'VIT-25',
            'status' => true,
        ]);
        $unavailableVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Loại 3.0 kg',
            'sku' => 'VIT-30',
            'status' => true,
        ]);

        Inventory::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $availableVariant->id,
            'quantity' => 10,
            'reserved_quantity' => 2,
        ]);
        Inventory::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $unavailableVariant->id,
            'quantity' => 2,
            'reserved_quantity' => 2,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role' => 'warehouse'])
            ->getJson(route('orders.ajax_variant_search', [
                'view' => 'products',
                'in_stock_only' => 1,
                'search' => 'Vịt nguyên con',
            ]));

        $response->assertOk()->assertJson(['success' => true]);

        $html = (string) $response->json('html');
        $this->assertStringContainsString('monitor-product-choice', $html);
        $this->assertStringContainsString('Vịt nguyên con', $html);
        $this->assertStringContainsString('VIT-25', $html);
        $this->assertStringNotContainsString('VIT-30', $html);
        $this->assertMatchesRegularExpression('/monitor-product-variants[^>]*hidden/', $html);
    }
}
