<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPriceRule;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReconciliationCurrentPriceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_uses_company_price_effective_when_order_was_created_and_lists_discount_separately(): void
    {
        $accountant = User::factory()->create();
        $accountant->roles()->attach(Role::query()->create(['name' => 'accounting']));
        $sale = User::factory()->create();
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'name' => 'Khách tra soát giá',
            'status' => 'active',
        ]);
        $product = Product::factory()->create(['name' => 'Vịt bọng không đầu chân', 'is_priced_by_kg' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'VB-dauchan',
            'size' => '2.50',
            'is_priced_by_kg' => true,
            'kg' => 2.5,
        ]);
        ProductPriceRule::query()->create([
            'product_variant_id' => $variant->id,
            'price' => 69000,
            'min_price' => 60000,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-19',
            'created_by' => $sale->id,
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'RECON-CURRENT-PRICE',
            'status' => Order::STATUS_DELIVERED,
            'charge_shipping_fee' => true,
            'shipping_fee' => 80000,
            'collect_customer_shipping_fee' => true,
            'customer_shipping_fee' => 80000,
            'vat_amount' => 0,
            'total' => 8160000,
        ]);
        $order->forceFill(['created_at' => '2026-09-19 10:00:00'])->saveQuietly();
        ProductPriceRule::query()->create([
            'product_variant_id' => $variant->id,
            'price' => 72000,
            'min_price' => 62000,
            'start_date' => '2026-09-20',
            'created_by' => $sale->id,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 50,
            'unit_weight' => 2.5,
            'total_weight' => 125,
            'actual_weight' => 120,
            'is_priced_by_kg' => true,
            'base_price' => 69000,
            'price' => 68000,
            'unit_discount' => 1000,
            'discount_type' => 'decrease',
            'discount_total' => 125000,
            'total' => 8500000,
        ]);
        $this->assertSame('69000.00', $order->items()->firstOrFail()->company_price_at_order);

        $this->actingAs($accountant)
            ->getJson(route('accounting.reconciliation.detail', $order))
            ->assertOk()
            ->assertJsonPath('items.0.unit_price', 69000)
            ->assertJsonPath('items.0.price_effective_date', '2026-09-19')
            ->assertJsonPath('items.0.company_price_at_order', 69000)
            ->assertJsonPath('items.0.pricing_quantity', 120)
            ->assertJsonPath('items.0.line_total', 8280000)
            ->assertJsonPath('items.0.discount_total', 120000)
            ->assertJsonPath('order.current_goods_total', 8280000)
            ->assertJsonPath('order.current_item_discount_total', 120000)
            ->assertJsonPath('order.customer_shipping_fee', 80000)
            ->assertJsonMissingPath('order.shipping_fee')
            ->assertJsonMissingPath('delivery.shipping_fee')
            ->assertJsonPath('order.current_calculated_total', 8240000);

        $this->actingAs($accountant)
            ->get(route('accounting.reconciliation'))
            ->assertOk()
            ->assertSee('Tổng Giảm giá sản phẩm')
            ->assertSee('Phí giao hàng thu khách')
            ->assertDontSee('Phí ship')
            ->assertSee('recon-product-discount-row');
    }

    public function test_detail_derives_legacy_discount_from_actual_sale_price(): void
    {
        $accountant = User::factory()->create();
        $accountant->roles()->attach(Role::query()->create(['name' => 'accounting']));
        $sale = User::factory()->create();
        $customer = Customer::query()->create(['name' => 'BÁNH MỲ THIÊN KÝ', 'status' => 'active']);
        $product = Product::factory()->create(['name' => 'Vịt Nguyên Con', 'is_priced_by_kg' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_priced_by_kg' => true,
            'kg' => 2.6,
        ]);
        ProductPriceRule::query()->create([
            'product_variant_id' => $variant->id,
            'price' => 68000,
            'min_price' => 60000,
            'start_date' => now()->subDay()->toDateString(),
            'created_by' => $sale->id,
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'ORD-LEGACY-SALE-PRICE',
            'status' => Order::STATUS_DELIVERED,
            'total' => 3432000,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 20,
            'unit_weight' => 2.6,
            'total_weight' => 52,
            'actual_weight' => 52,
            'is_priced_by_kg' => true,
            'base_price' => 66000,
            'price' => 66000,
            'unit_discount' => 0,
            'discount_type' => 'decrease',
            'discount_total' => 0,
            'total' => 3432000,
        ]);

        $this->actingAs($accountant)
            ->getJson(route('accounting.reconciliation.detail', $order))
            ->assertOk()
            ->assertJsonPath('items.0.unit_price', 68000)
            ->assertJsonPath('items.0.unit_discount', 2000)
            ->assertJsonPath('items.0.discount_total', 104000)
            ->assertJsonPath('order.current_goods_total', 3536000)
            ->assertJsonPath('order.current_item_discount_total', 104000)
            ->assertJsonPath('order.current_calculated_total', 3432000)
            ->assertJsonPath('order.recognized_revenue', 3432000);
    }
}
