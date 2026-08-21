<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPriceRule;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\SupplierProductPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringOrderSupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_view_can_attach_and_detach_an_order_supplier(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'name' => 'Khách danh sách gọn',
            'status' => 'active',
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'LIST-SUPPLIER-001',
            'status' => Order::STATUS_ORDER_PLACED,
            'total' => 6700000,
            'delivery_date' => now()->toDateString(),
        ]);
        $supplier = Supplier::query()->create([
            'name' => 'San Hà',
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'name' => 'Vịt quay',
            'is_priced_by_kg' => true,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 40,
            'unit_weight' => 2.5,
            'is_priced_by_kg' => true,
            'price' => 67000,
            'total' => 6700000,
        ]);
        SupplierProductPrice::query()->create([
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'effective_date' => now()->toDateString(),
            'price_calculation_type' => SupplierProductPrice::TYPE_COMPONENT_BASED,
            'material_price' => 56000,
            'processing_cost' => 3000,
            'other_cost' => 1000,
            'min_price' => 60000,
            'today_sale_price' => 67000,
            'created_by' => $sale->id,
        ]);

        $listResponse = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('pages.my_orders.monitoring', [
                'tab' => 'today',
                'date' => now()->toDateString(),
                'date_field' => 'business_date',
                'view' => 'list',
            ]));

        $listResponse->assertOk()
            ->assertSee('Khách danh sách gọn')
            ->assertSee('Chưa gắn')
            ->assertSee('San Hà')
            ->assertSee(route('pages.my_orders.monitoring.supplier', $order), false);

        $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->put(route('pages.my_orders.monitoring.supplier', $order), ['supplier_id' => $supplier->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'supplier_id' => $supplier->id,
        ]);

        $profitResponse = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('pages.my_orders.monitoring', [
                'tab' => 'today',
                'date' => now()->toDateString(),
                'date_field' => 'business_date',
                'view' => 'list',
            ]));

        $profitResponse->assertOk()
            ->assertSee('Lợi nhuận theo nhà cung cấp')
            ->assertSee('6.700.000đ')
            ->assertSee('6.000.000đ')
            ->assertSee('700.000đ')
            ->assertSee('10,4%');

        $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->put(route('pages.my_orders.monitoring.supplier', $order), ['supplier_id' => null])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'supplier_id' => null,
        ]);
    }

    public function test_profit_uses_one_system_price_for_supplier_foam_box_on_order_business_date(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'name' => 'Khách có thùng xốp',
            'status' => 'active',
        ]);
        $supplier = Supplier::query()->create(['name' => 'San Hà', 'is_active' => true]);
        $duck = Product::factory()->create(['name' => 'Vịt nguyên con', 'is_priced_by_kg' => true]);
        $foamBox = Product::factory()->create(['name' => 'Thùng xốp', 'is_priced_by_kg' => false]);
        $foamBoxVariant = ProductVariant::query()->create([
            'product_id' => $foamBox->id,
            'name' => 'Thùng xốp 1 cái',
            'size' => '1.00',
            'kg' => 1,
            'is_priced_by_kg' => false,
        ]);
        SupplierProduct::query()->create([
            'supplier_id' => $supplier->id,
            'product_id' => $foamBox->id,
            'active' => true,
            'price_calculation_type' => SupplierProductPrice::TYPE_DIRECT_PURCHASE,
        ]);
        ProductPriceRule::query()->create([
            'product_variant_id' => $foamBoxVariant->id,
            'price' => 80000,
            'min_price' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now(),
        ]);
        ProductPriceRule::query()->create([
            'product_variant_id' => $foamBoxVariant->id,
            'price' => 70000,
            'min_price' => 0,
            'start_date' => now(),
        ]);

        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'supplier_id' => $supplier->id,
            'code' => 'PROFIT-WITH-FOAM-001',
            'status' => Order::STATUS_ORDER_PLACED,
            'total' => 6780000,
            'delivery_date' => now()->toDateString(),
        ]);
        $order->items()->create([
            'product_id' => $duck->id,
            'quantity' => 40,
            'unit_weight' => 2.5,
            'is_priced_by_kg' => true,
            'price' => 67000,
            'total' => 6700000,
        ]);
        $order->items()->create([
            'product_id' => $foamBox->id,
            'product_variant_id' => $foamBoxVariant->id,
            'quantity' => 1,
            'unit_weight' => 1,
            'is_priced_by_kg' => false,
            'price' => 80000,
            'total' => 80000,
        ]);
        SupplierProductPrice::query()->create([
            'supplier_id' => $supplier->id,
            'product_id' => $duck->id,
            'effective_date' => now()->toDateString(),
            'price_calculation_type' => SupplierProductPrice::TYPE_COMPONENT_BASED,
            'material_price' => 60000,
            'min_price' => 60000,
            'today_sale_price' => 67000,
            'created_by' => $sale->id,
        ]);

        $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('pages.my_orders.monitoring', [
                'tab' => 'today',
                'date' => now()->toDateString(),
                'date_field' => 'business_date',
                'view' => 'list',
            ]))
            ->assertOk()
            ->assertSee('6.780.000đ')
            ->assertSee('6.070.000đ')
            ->assertSee('710.000đ')
            ->assertSee('Vịt nguyên con: 67.000đ')
            ->assertSee('Thùng xốp: 80.000đ')
            ->assertDontSee('67.000–80.000')
            ->assertDontSee('Thiếu giá nhập: Thùng xốp');
    }
}
