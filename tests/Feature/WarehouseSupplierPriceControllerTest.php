<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPriceLog;
use App\Models\ProductPriceRule;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\SupplierProductPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseSupplierPriceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_today_sale_price_button_updates_all_variants(): void
    {
        $this->withoutMiddleware();

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $category = Category::create([
            'name' => 'Test Category 2',
            'description' => 'Test',
            'parent_id' => null,
        ]);

        $product = Product::create([
            'name' => 'Test Product 2',
            'slug' => 'test-product-2-' . uniqid(),
            'description' => 'Test product',
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'status' => true,
            'unit' => 'cai',
            'kg' => 1,
            'is_priced_by_kg' => false,
        ]);

        $variantOne = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-101',
            'name' => 'Variant 101',
            'slug' => 'variant-101-' . uniqid(),
            'size' => 1,
            'quality' => '1',
            'stock' => 0,
            'kg' => 1,
            'is_priced_by_kg' => false,
        ]);

        $variantTwo = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-102',
            'name' => 'Variant 102',
            'slug' => 'variant-102-' . uniqid(),
            'size' => 2,
            'quality' => '1',
            'stock' => 0,
            'kg' => 1,
            'is_priced_by_kg' => false,
        ]);

        $supplier = Supplier::create([
            'name' => 'Supplier B',
            'phone' => '0900000001',
            'address' => 'Hanoi',
            'is_active' => true,
        ]);

        SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'active' => true,
            'price_calculation_type' => SupplierProduct::TYPE_COMPONENT_BASED,
        ]);

        SupplierProductPrice::create([
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'effective_date' => now()->toDateString(),
            'price_calculation_type' => SupplierProduct::TYPE_COMPONENT_BASED,
            'purchase_price' => 0,
            'material_price' => 10000,
            'processing_cost' => 2000,
            'other_cost' => 1000,
            'min_price' => 13000,
            'suggested_margin' => 3000,
            'today_sale_price' => 16000,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('warehouse.supplier-prices.apply-today-sale-price', [$supplier, $product]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('product_price_rules', [
            'product_variant_id' => $variantOne->id,
            'price' => 16000,
            'min_price' => 13000,
            'created_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('product_price_rules', [
            'product_variant_id' => $variantTwo->id,
            'price' => 16000,
            'min_price' => 13000,
            'created_by' => $admin->id,
        ]);

        $this->assertSame(2, ProductPriceLog::query()->where('new_price', 16000)->count());
    }

    public function test_store_updates_supplier_price_and_applies_sale_price_to_all_variants(): void
    {
        $this->withoutMiddleware();

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $category = Category::create([
            'name' => 'Test Category',
            'description' => 'Test',
            'parent_id' => null,
        ]);

        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product-' . uniqid(),
            'description' => 'Test product',
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'status' => true,
            'unit' => 'cai',
            'kg' => 1,
            'is_priced_by_kg' => false,
        ]);

        $variantOne = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-001',
            'name' => 'Variant 1',
            'slug' => 'variant-1-' . uniqid(),
            'size' => 1,
            'quality' => '1',
            'stock' => 0,
            'kg' => 1,
            'is_priced_by_kg' => false,
        ]);

        $variantTwo = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-002',
            'name' => 'Variant 2',
            'slug' => 'variant-2-' . uniqid(),
            'size' => 2,
            'quality' => '1',
            'stock' => 0,
            'kg' => 1,
            'is_priced_by_kg' => false,
        ]);

        $supplier = Supplier::create([
            'name' => 'Supplier A',
            'phone' => '0900000000',
            'address' => 'Hanoi',
            'is_active' => true,
        ]);

        SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'active' => true,
            'price_calculation_type' => SupplierProduct::TYPE_COMPONENT_BASED,
        ]);

        $response = $this->actingAs($admin)->post(route('warehouse.supplier-prices.store'), [
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'effective_date' => now()->toDateString(),
            'price_calculation_type' => SupplierProduct::TYPE_COMPONENT_BASED,
            'material_price' => 12000,
            'processing_cost' => 2000,
            'other_cost' => 1000,
            'suggested_margin' => 3000,
            'today_sale_price' => 18000,
            'note' => 'Điều chỉnh từ kho',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('supplier_product_prices', [
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'today_sale_price' => 18000,
            'created_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('product_price_rules', [
            'product_variant_id' => $variantOne->id,
            'price' => 18000,
            'min_price' => 15000,
            'created_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('product_price_rules', [
            'product_variant_id' => $variantTwo->id,
            'price' => 18000,
            'min_price' => 15000,
            'created_by' => $admin->id,
        ]);

        $this->assertSame(2, ProductPriceRule::query()->where('price', 18000)->count());
        $this->assertSame(2, ProductPriceLog::query()->where('new_price', 18000)->count());
        $this->assertSame(18000.0, (float) ProductVariant::findOrFail($variantOne->id)->final_price);
        $this->assertSame(18000.0, (float) ProductVariant::findOrFail($variantTwo->id)->final_price);
    }
}