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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPriceBoundsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_use_minimum_minus_12000_but_not_below_it(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'assigned_to' => $sale->id,
            'name' => 'Khách bổ sung đơn cũ',
            'status' => 'active',
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

        $this->actingAs($sale)->withSession(['active_role' => 'sale']);
        $payload = ['customer_id' => $customer->id, 'items' => [[
            'variant_id' => $variant->id, 'quantity' => 1,
            'unit_discount_type' => 'decrease', 'unit_discount' => 22000,
        ]]];
        $this->postJson(route('pages.my_orders.monitoring.store'), $payload)
            ->assertCreated()->assertJsonPath('order.total', 78000);
        $payload['items'][0]['unit_discount'] = 23000;
        $this->postJson(route('pages.my_orders.monitoring.store'), $payload)->assertStatus(422);
        $this->assertSame(90000.0, (float) $variant->latestPriceRule->min_price);
        $this->assertSame(0.0, \App\Support\OrderPriceBounds::minimum(5000));
    }
}
