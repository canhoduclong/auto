<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentSellingPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_dashboard_and_order_picker_use_the_same_published_price(): void
    {
        $sale = User::factory()->create();
        $sale->roles()->attach(Role::create(['name' => 'sale']));
        $product = Product::factory()->create(['name' => 'Published duck price', 'status' => true]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'status' => true]);
        $published = $variant->priceRules()->create([
            'price' => 70000, 'min_price' => 60000, 'start_date' => now()->subDays(3)->toDateString(),
        ]);
        $variant->priceRules()->create([
            'price' => 0, 'min_price' => 0, 'start_date' => now()->toDateString(),
        ]);

        $this->assertSame($published->id, $variant->fresh()->latestPriceRule->id);
        $this->assertSame($published->id, $variant->fresh()->load('latestPriceRule')->latestPriceRule->id);
        $this->assertSame(70000.0, $variant->fresh()->final_price);

        $this->actingAs($sale)->withSession(['active_role' => 'sale']);
        $this->get(route('pages.my_dashboard'))
            ->assertOk()
            ->assertViewHas('productPriceBoard', fn ($board) => (float) $board->firstWhere('product_id', $product->id)['representative_price'] === 70000.0);
        $this->get(route('pages.product_list'))->assertOk()->assertSee('70.000đ');

        foreach (['products', 'variants'] as $view) {
            $response = $this->getJson(route('site.orders.variants.ajax', ['view' => $view]), ['X-Requested-With' => 'XMLHttpRequest']);
            $response->assertOk()->assertJsonPath('success', true);
            $this->assertStringContainsString('data-variant-price="70000"', $response->json('html'));
            $this->assertStringContainsString('data-variant-min-price="60000"', $response->json('html'));
        }
    }

    public function test_price_resolution_respects_dates_and_uses_latest_positive_price(): void
    {
        $variant = ProductVariant::factory()->create();
        $variant->priceRules()->create(['price' => 50000, 'start_date' => now()->subDays(4)->toDateString()]);
        $variant->priceRules()->create(['price' => 60000, 'start_date' => now()->subDays(2)->toDateString()]);
        $latest = $variant->priceRules()->create(['price' => 70000, 'start_date' => now()->subDays(2)->toDateString()]);
        $variant->priceRules()->create(['price' => 0, 'start_date' => now()->toDateString()]);
        $variant->priceRules()->create(['price' => 90000, 'start_date' => now()->addDay()->toDateString()]);
        $variant->priceRules()->create([
            'price' => 80000, 'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->subDay()->toDateString(),
        ]);

        $this->assertSame($latest->id, $variant->fresh()->load('latestPriceRule')->latestPriceRule->id);
        $this->assertSame(70000.0, $variant->fresh()->final_price);

        $variant->priceRules()->where('price', '>', 0)->whereDate('start_date', '<=', now())
            ->update(['end_date' => now()->subDay()->toDateString()]);
        $this->assertSame(0.0, $variant->fresh()->final_price);
        $this->assertSame(0.0, (float) $variant->fresh()->latestPriceRule->price);
    }
}
