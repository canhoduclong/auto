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

class MyOrderPriceEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_uses_current_list_price_as_the_base_for_the_saved_selling_price(): void
    {
        [$sale, $customer, $order, $variant] = $this->makeEditableOrder();

        $response = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('site.orders.edit', $order));

        $response->assertOk();
        $response->assertSee('data-price="22000"', false);
        $response->assertSee('name="item_discount_type[' . $variant->id . ']" value="decrease"', false);
        $response->assertSee('name="item_discount[' . $variant->id . ']" value="4000"', false);
        $response->assertSee('18.000đ');
    }

    public function test_saving_the_price_shown_by_the_edit_form_does_not_trigger_a_false_min_price_error(): void
    {
        [$sale, $customer, $order, $variant] = $this->makeEditableOrder();

        $response = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->put(route('site.orders.update', $order), [
                'customer_id' => $customer->id,
                'recipient_name' => 'Khach hang',
                'recipient_phone' => '0900000000',
                'recipient_address' => 'Dia chi giao hang',
                'item_discount' => [$variant->id => 4000],
                'item_discount_type' => [$variant->id => 'decrease'],
                'items' => [[
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                ]],
            ]);

        $response->assertSessionDoesntHaveErrors('item_discount.' . $variant->id);
        $this->assertSame(18000.0, (float) $order->fresh()->items()->firstOrFail()->price);
    }

    public function test_direct_edit_adds_preserves_and_removes_customer_charges(): void
    {
        [$sale, $customer, $order, $variant] = $this->makeEditableOrder();
        $payload = [
            'customer_id' => $customer->id,
            'recipient_name' => 'Khach hang',
            'recipient_phone' => '0900000000',
            'recipient_address' => 'Dia chi giao hang',
            'items' => [['variant_id' => $variant->id, 'quantity' => 1]],
        ];
        $this->actingAs($sale)->withSession(['active_role' => 'sale']);
        $this->put(route('site.orders.update', $order), $payload + [
            'charge_vat' => 1, 'vat_percent' => 10,
            'collect_customer_shipping_fee' => 1, 'customer_shipping_fee' => 15000,
        ])->assertSessionHasNoErrors();
        $order->refresh();
        $this->assertTrue($order->charge_vat);
        $this->assertSame(2200.0, (float) $order->vat_amount);
        $this->assertSame(39200.0, (float) $order->total);
        // Receivables are only recognized after accounting reconciliation.
        $this->assertSame(0.0, (float) $order->amount_due);

        // Other edit forms that omit these fields must keep the saved fees.
        $this->put(route('site.orders.update', $order), $payload)->assertSessionHasNoErrors();
        $this->assertSame(39200.0, (float) $order->fresh()->total);

        $this->put(route('site.orders.update', $order), $payload + [
            'charge_vat' => 1, 'vat_percent' => 0,
            'collect_customer_shipping_fee' => 1, 'customer_shipping_fee' => -1,
        ])->assertSessionHasErrors(['vat_percent', 'customer_shipping_fee']);
        $this->assertSame(39200.0, (float) $order->fresh()->total);

        $this->put(route('site.orders.update', $order), $payload + [
            'charge_vat' => 0, 'collect_customer_shipping_fee' => 0,
        ])->assertSessionHasNoErrors();
        $order->refresh();
        $this->assertFalse($order->charge_vat);
        $this->assertFalse($order->collect_customer_shipping_fee);
        $this->assertSame(0.0, (float) $order->vat_amount);
        $this->assertSame(0.0, (float) $order->customer_shipping_fee);
        $this->assertSame(22000.0, (float) $order->total);
    }

    private function makeEditableOrder(): array
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);

        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'assigned_to' => $sale->id,
            'name' => 'Khach hang',
            'phone' => '0900000000',
            'address' => 'Dia chi giao hang',
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
            'price' => 22000,
            'min_price' => 18000,
            'created_by' => $sale->id,
        ]);

        $order = Order::query()->create([
            'user_id' => $sale->id,
            'customer_id' => $customer->id,
            'code' => 'EDIT-PRICE-TEST',
            'status' => 'pending',
            'recipient_name' => 'Khach hang',
            'recipient_phone' => '0900000000',
            'recipient_address' => 'Dia chi giao hang',
            'subtotal_amount' => 20000,
            'total' => 18000,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'price' => 18000,
            'base_price' => 20000,
            'unit_discount' => 2000,
            'discount_type' => 'decrease',
            'discount_total' => 2000,
            'unit_weight' => 1,
            'is_priced_by_kg' => false,
            'total_weight' => 1,
            'total' => 18000,
        ]);

        return [$sale, $customer, $order, $variant];
    }
}
