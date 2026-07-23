<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyCustomerOrderCreateUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_form_uses_grouped_products_and_selling_price_stepper(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);

        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'assigned_to' => $sale->id,
            'name' => 'Khách hàng thử nghiệm',
            'phone' => '0900000000',
            'address' => 'Hồ Chí Minh',
        ]);

        $response = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('my_customer.order.create', $customer));

        $response->assertOk()
            ->assertSee('Chọn sản phẩm và biến thể')
            ->assertSee(route('site.orders.variants.ajax'), false)
            ->assertSee("view: 'products'", false)
            ->assertSee('monitor-product-choice', false)
            ->assertSee('monitor-variant-option', false)
            ->assertSee('selling-price-decrease', false)
            ->assertSee('selling-price-increase', false)
            ->assertDontSee('<th class="text-center">CK giá</th>', false);
    }
}
