<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPriceRule;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Services\CustomerPriorityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringOrderAdditionalChargesTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_order_adds_vat_and_customer_shipping_without_using_assigned_shipping_fee(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);

        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'assigned_to' => $sale->id,
            'name' => 'Khách có VAT',
            'status' => 'active',
            'shipping_fee' => 99000,
        ]);
        app(CustomerPriorityService::class)->attachSale($customer, $sale->id, 1, 'test');

        $product = Product::factory()->create(['is_priced_by_kg' => false]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_priced_by_kg' => false,
            'kg' => 1,
        ]);
        ProductPriceRule::query()->create([
            'product_variant_id' => $variant->id,
            'price' => 100000,
            'min_price' => 90000,
            'created_by' => $sale->id,
        ]);

        $response = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->postJson(route('pages.my_orders.monitoring.store'), [
                'customer_id' => $customer->id,
                'items' => [[
                    'variant_id' => $variant->id,
                    'quantity' => 2,
                ]],
                'charge_vat' => true,
                'vat_percent' => 10,
                'collect_customer_shipping_fee' => true,
                'customer_shipping_fee' => 15000,
            ]);

        $response->assertCreated()
            ->assertJsonPath('order.total', 235000);

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertTrue($order->charge_vat);
        $this->assertSame(10.0, (float) $order->vat_percent);
        $this->assertSame(20000.0, (float) $order->vat_amount);
        $this->assertTrue($order->collect_customer_shipping_fee);
        $this->assertSame(15000.0, (float) $order->customer_shipping_fee);
        $this->assertFalse($order->charge_shipping_fee);
        $this->assertSame(0.0, (float) $order->shipping_fee);
        $this->assertSame(235000.0, (float) $order->total);
    }

    public function test_selected_additional_charges_require_positive_values(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);

        $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->postJson(route('pages.my_orders.monitoring.store'), [
                'customer_id' => 1,
                'items' => [['variant_id' => 1, 'quantity' => 1]],
                'charge_vat' => true,
                'vat_percent' => 0,
                'collect_customer_shipping_fee' => true,
                'customer_shipping_fee' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['vat_percent', 'customer_shipping_fee']);
    }
}
