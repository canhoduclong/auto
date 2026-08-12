<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
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
}
